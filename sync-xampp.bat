@echo off
echo Sincronizando MedConecta para o XAMPP...
robocopy "%~dp0" "C:\xampp\htdocs\MedConecta" /MIR /XD .git node_modules
echo.
echo Pronto! Abra: http://localhost/MedConecta/public/
echo Use Ctrl+F5 no navegador para atualizar sem cache.
pause
