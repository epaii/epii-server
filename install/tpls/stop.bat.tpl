@echo off
echo Stopping nginx...
taskkill /F /IM nginx.exe > nul
echo Stopping PHP FastCGI...
taskkill /F /IM php-cgi-spawner.exe > nul
taskkill /F /IM php-cgi.exe > nul

pause