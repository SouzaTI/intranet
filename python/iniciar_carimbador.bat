@echo off
setlocal
title Portal de Assinaturas - Carimbador PDF
color 0A

echo ============================================================
echo  PORTAL DE ASSINATURAS - SERVICO DE CARIMBO
echo ============================================================
echo.

set "ASSINATURAS_DIR=%~dp0..\uploads\assinaturas"
set "CARIMBADOR_PORT=5055"
set "PYTHONUNBUFFERED=1"

cd /d "%~dp0"

echo Pasta do Python:
echo %CD%
echo.
echo Pasta dos documentos:
echo %ASSINATURAS_DIR%
echo.

where py >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Python encontrado pelo comando PY.
    echo.
    py -3 api_carimbo.py
    goto FINAL
)

where python >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Python encontrado pelo comando PYTHON.
    echo.
    python api_carimbo.py
    goto FINAL
)

echo [ERRO] Python nao foi encontrado no PATH do Windows.
echo.
echo Verifique a instalacao do Python e marque:
echo "Add Python to PATH"
echo.

:FINAL
echo.
echo ============================================================
echo  O SERVICO FOI ENCERRADO OU OCORREU UM ERRO
echo ============================================================
echo.
pause
endlocal