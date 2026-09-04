@echo off
setlocal

set "PHP_EXE=C:\xampp\php\php.exe"
set "SCRIPT=C:\xampp\htdocs\intranet\cron\notificar_pendencias.php"

if not exist "%PHP_EXE%" (
    echo [ERRO] PHP nao encontrado em %PHP_EXE%
    exit /b 2
)

if not exist "%SCRIPT%" (
    echo [ERRO] Script nao encontrado em %SCRIPT%
    exit /b 3
)

"%PHP_EXE%" "%SCRIPT%"
set "RESULTADO=%ERRORLEVEL%"
exit /b %RESULTADO%
