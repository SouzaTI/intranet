@echo off
setlocal
title Teste do Carimbador PDF - Consolidacao

echo ============================================================
echo  TESTE SEGURO DO CARIMBADOR - DUAS ASSINATURAS
echo ============================================================
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0teste_carimbo_consolidado.ps1"

echo.
echo ============================================================
echo  TESTE ENCERRADO
echo ============================================================
pause
endlocal
