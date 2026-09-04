$ErrorActionPreference = "Stop"

$PastaTeste = $PSScriptRoot
$UrlBase = "http://127.0.0.1:5055"

function Mostrar-Etapa([string]$Texto) {
    Write-Host "`n$Texto" -ForegroundColor Cyan
}

try {
    Mostrar-Etapa "[1/5] Verificando o servico..."
    $Status = Invoke-RestMethod -Uri "$UrlBase/teste" -Method Get -TimeoutSec 10

    if (-not $Status.ok) {
        throw "O servico respondeu, mas informou que nao esta pronto."
    }

    Write-Host "[OK] Servico online - versao: $($Status.versao)" -ForegroundColor Green
    if ($Status.versao -ne "4.0-consolidado") {
        throw "Versao incorreta. Esperado: 4.0-consolidado. Atual: $($Status.versao)"
    }

    Mostrar-Etapa "[2/5] Procurando um PDF na pasta de testes..."
    $PdfOriginal = Get-ChildItem -LiteralPath $PastaTeste -File -Filter "*.pdf" |
        Where-Object { $_.Name -notlike "RESULTADO_TESTE_*" } |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if (-not $PdfOriginal) {
        throw "Nenhum PDF encontrado em: $PastaTeste"
    }

    Write-Host "[OK] PDF selecionado: $($PdfOriginal.Name)" -ForegroundColor Green

    $DataArquivo = Get-Date -Format "yyyyMMdd_HHmmss"
    $PastaAutorizada = [string]$Status.diretorio
    if (-not (Test-Path -LiteralPath $PastaAutorizada -PathType Container)) {
        throw "A pasta autorizada informada pelo servico nao existe: $PastaAutorizada"
    }

    $PdfTemporario = Join-Path $PastaAutorizada "TESTE_CARIMBO_$DataArquivo.pdf"
    $PdfResultado = Join-Path $PastaTeste "RESULTADO_TESTE_$DataArquivo.pdf"
    Copy-Item -LiteralPath $PdfOriginal.FullName -Destination $PdfTemporario
    Write-Host "[OK] Copia temporaria criada. O original nao sera alterado." -ForegroundColor Green

    $Assinaturas = @(
        @{
            assinante_nome  = "ASSINANTE TESTE 1"
            assinante_setor = "TECNOLOGIA DA INFORMACAO"
            assinante_email = "teste1@empresa.local"
            ordem           = 1
            ip_origem       = "127.0.0.1"
        },
        @{
            assinante_nome  = "ASSINANTE TESTE 2"
            assinante_setor = "RECURSOS HUMANOS"
            assinante_email = "teste2@empresa.local"
            ordem           = 2
            ip_origem       = "127.0.0.1"
        }
    )

    $Numero = 0
    foreach ($Assinatura in $Assinaturas) {
        $Numero++
        Mostrar-Etapa "[$($Numero + 2)/5] Aplicando assinatura ficticia $Numero de 2..."

        $Dados = @{
            caminho_entrada = $PdfTemporario
            caminho_saida   = $PdfTemporario
            assinante_nome  = $Assinatura.assinante_nome
            assinante_setor = $Assinatura.assinante_setor
            assinante_email = $Assinatura.assinante_email
            ordem           = $Assinatura.ordem
            ip_origem       = $Assinatura.ip_origem
            data_hora       = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        }

        $Json = $Dados | ConvertTo-Json -Compress
        $Resposta = Invoke-RestMethod `
            -Uri "$UrlBase/api/carimbar" `
            -Method Post `
            -ContentType "application/json; charset=utf-8" `
            -Body ([System.Text.Encoding]::UTF8.GetBytes($Json)) `
            -TimeoutSec 60

        if (-not $Resposta.ok) {
            throw "A assinatura ficticia $Numero nao foi aplicada."
        }

        Write-Host "[OK] Assinatura ficticia $Numero aplicada." -ForegroundColor Green
    }

    Copy-Item -LiteralPath $PdfTemporario -Destination $PdfResultado
    Remove-Item -LiteralPath $PdfTemporario -Force

    Mostrar-Etapa "[5/5] Teste concluido com sucesso."
    Write-Host "Resultado: $PdfResultado" -ForegroundColor Green
    Write-Host "`nConfira se os dois assinantes aparecem juntos na mesma pagina final." -ForegroundColor Yellow
    Write-Host "Nenhum banco, envelope ou e-mail foi utilizado neste teste." -ForegroundColor Yellow

    Start-Process -FilePath $PdfResultado
}
catch {
    Write-Host "`n[ERRO] $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "`nConfirme se:" -ForegroundColor Yellow
    Write-Host "- o carimbador esta aberto na porta 5055;"
    Write-Host "- a pagina /teste mostra a versao 4.0-consolidado;"
    Write-Host "- existe pelo menos um PDF nesta pasta;"
    Write-Host "- a pasta indicada por ASSINATURAS_DIR existe e permite gravacao."
    exit 1
}
