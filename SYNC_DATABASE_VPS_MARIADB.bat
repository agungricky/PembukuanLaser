@echo off
setlocal EnableExtensions DisableDelayedExpansion
title SYNC VPS KE LARAGON - PASSWORD SSH

set "SSH_HOST=103.76.121.130"
set "SSH_PORT=20107"
set "SSH_USER=admin"
set "SSH_PASSWORD=Laser2026"
set "SSH_HOSTKEY=ssh-ed25519 255 PhW/j4XxesPzfGASoo6vSGCHOKaNXe84/+T0WMLYV5o"
set "SERVER_DB=admin_pembukuan"
set "SERVER_MY_CNF=/home/admin/.my.cnf"
set "LOCAL_DB=pembukuan"
set "TEST_DB=pembukuan_sync_test"
set "LOCAL_USER=root"
set "LOCAL_HOST=127.0.0.1"
set "LOCAL_PORT=3306"
set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin"
set "WINSCP=C:\Program Files\WinSCP\WinSCP.com"
if not exist "%WINSCP%" set "WINSCP=C:\Program Files (x86)\WinSCP\WinSCP.com"

if not exist "%WINSCP%" goto :MISSING
if not exist "%MYSQL_BIN%\mysql.exe" goto :MISSING
if not exist "%MYSQL_BIN%\mysqldump.exe" goto :MISSING

for /f "usebackq delims=" %%A in (`powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd"`) do set "TODAY=%%A"
for /f "usebackq delims=" %%A in (`powershell -NoProfile -Command "Get-Date -Format HH-mm-ss-fff"`) do set "NOW=%%A"
if not defined TODAY goto :FAIL
if not defined NOW goto :FAIL
set "BACKUP=%USERPROFILE%\Documents\SingkronDatabase\%TODAY%\%NOW%"
mkdir "%BACKUP%" 2>nul
if not exist "%BACKUP%\" goto :FAIL
set "REMOTE_SQL=/home/admin/sync_laragon_%TODAY%_%NOW%.sql"
set "SERVER_SQL=%BACKUP%\database_server.sql"
set "LOCAL_SQL=%BACKUP%\backup_lokal.sql"
set "LOG=%BACKUP%\winscp.log"

echo ==============================================
echo    SYNC VPS KE LARAGON - SSH PASSWORD
echo ==============================================
echo SSH: %SSH_USER%@%SSH_HOST%:%SSH_PORT%
echo VPS DB: %SERVER_DB%
echo Lokal : %LOCAL_DB%
echo Backup: %BACKUP%
echo.
echo PERINGATAN: %LOCAL_DB% dan %TEST_DB% akan dihapus/dibuat ulang.
choice /C YN /M "Lanjutkan"
if errorlevel 2 goto :CANCEL

echo.
echo [1/6] Export dan download VPS...
"%WINSCP%" /nointeractiveinput /log="%LOG%" /command ^
 "option batch abort" ^
 "option confirm off" ^
 "open sftp://%SSH_USER%:%SSH_PASSWORD%@%SSH_HOST%:%SSH_PORT%/ -hostkey=""%SSH_HOSTKEY%"" -timeout=120" ^
 "call mariadb-dump --defaults-extra-file=%SERVER_MY_CNF% --single-transaction --quick --no-tablespaces --routines --triggers --events %SERVER_DB% > %REMOTE_SQL% && test -s %REMOTE_SQL% && chmod 600 %REMOTE_SQL%" ^
 "get %REMOTE_SQL% ""%SERVER_SQL%""" ^
 "call rm -f %REMOTE_SQL%" ^
 "exit"
if errorlevel 1 goto :FAIL
if not exist "%SERVER_SQL%" goto :FAIL
for %%F in ("%SERVER_SQL%") do if %%~zF EQU 0 goto :FAIL

echo [2/6] Backup lokal...
"%MYSQL_BIN%\mysqldump.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" --single-transaction --quick --no-tablespaces --routines --triggers --events "%LOCAL_DB%" > "%LOCAL_SQL%"
if errorlevel 1 goto :FAIL
for %%F in ("%LOCAL_SQL%") do if %%~zF EQU 0 goto :FAIL

echo [3/6] Membuat database uji...
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" -e "DROP DATABASE IF EXISTS %TEST_DB%; CREATE DATABASE %TEST_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 goto :FAIL

echo [4/6] Uji import...
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" "%TEST_DB%" < "%SERVER_SQL%"
if errorlevel 1 goto :FAIL
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" -e "DROP DATABASE IF EXISTS %TEST_DB%;"
if errorlevel 1 goto :FAIL

echo [5/6] Reset database lokal...
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" -e "DROP DATABASE IF EXISTS %LOCAL_DB%; CREATE DATABASE %LOCAL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 goto :RESTORE

echo [6/6] Import database VPS...
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" "%LOCAL_DB%" < "%SERVER_SQL%"
if errorlevel 1 goto :RESTORE
echo.
echo SUKSES. Database lokal telah disinkronkan.
goto :END

:RESTORE
echo.
echo IMPORT/RESET GAGAL. Mencoba memulihkan backup lokal...
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" -e "DROP DATABASE IF EXISTS %LOCAL_DB%; CREATE DATABASE %LOCAL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 goto :FAIL
"%MYSQL_BIN%\mysql.exe" -h "%LOCAL_HOST%" -P %LOCAL_PORT% -u "%LOCAL_USER%" "%LOCAL_DB%" < "%LOCAL_SQL%"
if errorlevel 1 goto :FAIL
echo Backup lokal berhasil dipulihkan.
goto :FAIL

:MISSING
echo ERROR: WinSCP atau executable MySQL Laragon tidak ditemukan.
goto :END
:FAIL
echo.
echo GAGAL. Periksa output di atas dan file backup/log.
goto :END
:CANCEL
echo Dibatalkan.
:END
echo.
echo Folder backup: %BACKUP%
pause
endlocal
