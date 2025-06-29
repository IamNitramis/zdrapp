@echo off
cd /d C:\xampp

:: Spustí Apache na pozadí
start "" /min apache_start.bat

:: Spustí MySQL na pozadí
start "" /min mysql_start.bat

exit
