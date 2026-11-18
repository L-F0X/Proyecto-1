@echo off
setlocal

set "PROJECT_DIR=%~dp0"
set "PHP_EXE=%PROJECT_DIR%..\..\php\php.exe"

if not exist "%PHP_EXE%" (
    where php >nul 2>nul
    if errorlevel 1 (
        echo No se encontro PHP. Instala XAMPP en la ruta esperada o agrega PHP al PATH.
        echo Ruta revisada: "%PROJECT_DIR%..\..\php\php.exe"
        pause
        exit /b 1
    )
    set "PHP_EXE=php"
)

echo Servidor local: http://127.0.0.1:8000
echo Asegurate de iniciar MySQL en XAMPP antes de usar la aplicacion.
echo Para detener el servidor, presiona Ctrl+C.
"%PHP_EXE%" -S 127.0.0.1:8000 -t "%PROJECT_DIR%"

echo.
echo El servidor se detuvo.
pause
