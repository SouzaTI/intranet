from __future__ import annotations

import os
from datetime import datetime
from io import BytesIO
from pathlib import Path

from flask import Flask, jsonify, request
from pypdf import PdfReader, PdfWriter
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas

app = Flask(__name__)

VERSAO_CARIMBADOR = "4.0-consolidado"
MARCADOR_PAGINA_ASSINATURAS = "REGISTRO CONSOLIDADO DE ASSINATURAS"
MARCADOR_PAGINA_LEGADA = "REGISTRO DE ASSINATURA INTERNA"
ASSINATURAS_POR_PAGINA = 5


def raiz_permitida() -> Path:
    valor = os.getenv("ASSINATURAS_DIR", "").strip()
    if not valor:
        raise RuntimeError("Defina ASSINATURAS_DIR com a pasta uploads/assinaturas da intranet.")
    return Path(valor).resolve()


def caminho_seguro(valor: str, raiz: Path) -> Path:
    caminho = Path(valor).resolve()
    if caminho != raiz and raiz not in caminho.parents:
        raise ValueError("Caminho fora da pasta autorizada.")
    return caminho


def desenhar_bloco_assinatura(
    pdf: canvas.Canvas, slot: int, nome: str, setor: str, email: str,
    ordem: int, data_hora: str, ip: str
) -> None:
    largura, altura = A4
    topo = altura - 112 - (slot * 132)
    pdf.setFillColorRGB(0.06, 0.09, 0.16)
    pdf.setFont("Helvetica-Bold", 10)
    pdf.drawString(54, topo, f"ASSINATURA {slot + 1}")
    pdf.setFont("Helvetica", 9)
    pdf.drawString(54, topo - 20, f"Etapa: {ordem}")
    pdf.drawString(150, topo - 20, f"Data e hora: {data_hora}")
    pdf.drawString(54, topo - 40, f"Assinante: {nome[:92]}")
    pdf.drawString(54, topo - 60, f"Setor: {setor[:100]}")
    pdf.drawString(54, topo - 80, f"E-mail: {email[:115]}")
    pdf.drawString(54, topo - 100, f"IP registrado: {ip[:45]}")
    pdf.setStrokeColorRGB(0.75, 0.78, 0.82)
    pdf.line(54, topo - 116, largura - 54, topo - 116)


def camada_assinatura(
    slot: int, nome: str, setor: str, email: str, ordem: int,
    data_hora: str, ip: str, incluir_cabecalho: bool
) -> BytesIO:
    buffer = BytesIO()
    largura, altura = A4
    pdf = canvas.Canvas(buffer, pagesize=A4)
    if incluir_cabecalho:
        pdf.setTitle("Registro consolidado de assinaturas")
        pdf.setFillColorRGB(0.06, 0.09, 0.16)
        pdf.setFont("Helvetica-Bold", 16)
        pdf.drawString(54, altura - 58, "REGISTRO DE ASSINATURAS INTERNAS")
        pdf.setFont("Helvetica", 8)
        pdf.setFillColorRGB(0.35, 0.39, 0.46)
        pdf.drawString(54, altura - 78, MARCADOR_PAGINA_ASSINATURAS)
        pdf.drawRightString(largura - 54, 35, "Documento processado pelo Portal Interno de Assinaturas")
    desenhar_bloco_assinatura(pdf, slot, nome, setor, email, ordem, data_hora, ip)
    pdf.save()
    buffer.seek(0)
    return buffer


@app.get("/teste")
def teste():
    try:
        raiz = raiz_permitida()
        return jsonify(ok=True, status="online", versao=VERSAO_CARIMBADOR, diretorio=str(raiz))
    except Exception as exc:
        return jsonify(ok=False, msg=str(exc)), 500


@app.post("/api/carimbar")
def carimbar():
    dados = request.get_json(silent=True) or {}
    obrigatorios = ["caminho_entrada", "caminho_saida", "assinante_nome", "ordem"]
    if any(not dados.get(campo) for campo in obrigatorios):
        return jsonify(ok=False, msg="Campos obrigatórios ausentes."), 400

    try:
        raiz = raiz_permitida()
        entrada = caminho_seguro(str(dados["caminho_entrada"]), raiz)
        saida = caminho_seguro(str(dados["caminho_saida"]), raiz)
        if not entrada.is_file() or entrada.suffix.lower() != ".pdf" or saida.suffix.lower() != ".pdf":
            raise ValueError("PDF de entrada ou saída inválido.")
        saida.parent.mkdir(parents=True, exist_ok=True)

        data_hora = str(dados.get("data_hora") or datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
        leitor = PdfReader(str(entrada))
        escritor = PdfWriter()
        for pagina in leitor.pages:
            escritor.add_page(pagina)

        nome = str(dados["assinante_nome"])
        setor = str(dados.get("assinante_setor") or "Não informado")
        email = str(dados.get("assinante_email") or "Não informado")
        ordem = int(dados["ordem"])
        ip = str(dados.get("ip_origem", "N/D"))

        texto_ultima = ""
        if leitor.pages:
            try:
                texto_ultima = leitor.pages[-1].extract_text() or ""
            except Exception:
                texto_ultima = ""

        pagina_consolidada = MARCADOR_PAGINA_ASSINATURAS in texto_ultima
        pagina_legada = MARCADOR_PAGINA_LEGADA in texto_ultima and not pagina_consolidada
        if pagina_consolidada:
            ocupados = texto_ultima.count("ASSINATURA ")
        elif pagina_legada:
            # Compatibilidade com PDFs que receberam a primeira assinatura
            # usando a versão antiga, que criava uma página por pessoa.
            ocupados = 1 + texto_ultima.count("ASSINATURA ")
        else:
            ocupados = 0

        if (pagina_consolidada or pagina_legada) and ocupados < ASSINATURAS_POR_PAGINA:
            camada = PdfReader(camada_assinatura(
                ocupados, nome, setor, email, ordem, data_hora, ip, False
            ))
            escritor.pages[-1].merge_page(camada.pages[0])
        else:
            nova_pagina = PdfReader(camada_assinatura(
                0, nome, setor, email, ordem, data_hora, ip, True
            ))
            escritor.add_page(nova_pagina.pages[0])

        temporario = saida.with_suffix(".tmp")
        with temporario.open("wb") as arquivo:
            escritor.write(arquivo)
        temporario.replace(saida)
        return jsonify(ok=True, caminho_saida=str(saida))
    except Exception as exc:
        app.logger.exception("Falha ao carimbar PDF")
        return jsonify(ok=False, msg=str(exc)), 500


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=int(os.getenv("CARIMBADOR_PORT", "5055")), debug=False)
