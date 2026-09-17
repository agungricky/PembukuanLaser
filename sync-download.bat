@echo off
setlocal

title Sync Database Server ke Laragon

REM ==================================================
REM KONFIGURASI
REM ==================================================

set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin"

set "LOCAL_DB=pembukuan"
set "LOCAL_USER=root"
set "LOCAL_HOST=127.0.0.1"
set "LOCAL_PORT=3306"

set "SQL_FILE=C:\Users\Geen\Documents\kingplat_laravel_backup.sql"
set "LOCAL_BACKUP=C:\Users\Geen\Documents\backup_lokal_sebelum_sync.sql"

REM ==================================================
REM CARI WINSCP
REM ==================================================

if exist "C:\Program Files\WinSCP\WinSCP.com" (
    set "WINSCP=C:\Program Files\WinSCP\WinSCP.com"
) else (
    set "WINSCP=C:\Program Files (x86)\WinSCP\WinSCP.com"
)

echo.
echo ==============================================
echo     SYNC DATABASE SERVER -^> LOCAL
echo ==============================================
echo.


REM ==================================================
REM 1. BUAT DUMP SERVER + DOWNLOAD
REM ==================================================

echo [1/5] Membuat database dump terbaru di server...
echo.

"%WINSCP%" ^
    /command ^
    "option batch abort" ^
    "option confirm off" ^
    "open Laser" ^
    "call mysqldump --defaults-extra-file=/home/kingplat/.my.cnf --single-transaction --quick --no-tablespaces kingplat_laravel > /home/kingplat/kingplat_laravel_backup.sql" ^
    "get /home/kingplat/kingplat_laravel_backup.sql C:\Users\Geen\Documents\" ^
    "exit"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo GAGAL MEMBUAT / DOWNLOAD DATABASE SERVER
    echo DATABASE LOKAL BELUM DIUBAH
    echo ==============================================
    pause
    exit /b 1
)

echo.
echo Download database server berhasil.
echo.


REM ==================================================
REM 2. CEK FILE SQL
REM ==================================================

echo [2/5] Mengecek file database server...

if not exist "%SQL_FILE%" (
    echo.
    echo ERROR: File database tidak ditemukan.
    echo %SQL_FILE%
    echo.
    echo DATABASE LOKAL BELUM DIUBAH
    pause
    exit /b 1
)

echo File ditemukan.
echo.


REM ==================================================
REM 3. BACKUP DATABASE LOKAL
REM ==================================================

echo [3/5] Backup database lokal saat ini...
echo.

"%MYSQL_BIN%\mysqldump.exe" ^
    -h %LOCAL_HOST% ^
    -P %LOCAL_PORT% ^
    -u %LOCAL_USER% ^
    --single-transaction ^
    --quick ^
    --no-tablespaces ^
    %LOCAL_DB% > "%LOCAL_BACKUP%"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo BACKUP DATABASE LOKAL GAGAL
    echo DATABASE LOKAL BELUM DIUBAH
    echo ==============================================
    pause
    exit /b 1
)

echo Backup lokal berhasil.
echo.


REM ==================================================
REM 4. RESET DATABASE LOKAL
REM ==================================================

echo [4/5] Reset database lokal...
echo.

"%MYSQL_BIN%\mysql.exe" ^
    -h %LOCAL_HOST% ^
    -P %LOCAL_PORT% ^
    -u %LOCAL_USER% ^
    -e "DROP DATABASE IF EXISTS %LOCAL_DB%; CREATE DATABASE %LOCAL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo RESET DATABASE LOKAL GAGAL
    echo ==============================================
    pause
    exit /b 1
)

echo Database lokal berhasil dibuat ulang.
echo.


REM ==================================================
REM 5. IMPORT DATABASE SERVER
REM ==================================================

echo [5/5] Import database server ke Laragon...
echo Proses ini mungkin membutuhkan beberapa saat...
echo.

"%MYSQL_BIN%\mysql.exe" ^
    -h %LOCAL_HOST% ^
    -P %LOCAL_PORT% ^
    -u %LOCAL_USER% ^
    %LOCAL_DB% < "%SQL_FILE%"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo IMPORT DATABASE GAGAL
    echo ==============================================
    echo.
    echo Backup database lokal sebelumnya ada di:
    echo %LOCAL_BACKUP%
    echo.
    pause
    exit /b 1
)

echo.
echo ==============================================
echo       DATABASE BERHASIL DISINKRONKAN
echo ==============================================
echo.
echo Server:
echo kingplat_laravel
echo.
echo Lokal:
echo %LOCAL_DB%
echo.
echo Backup lokal lama:
echo %LOCAL_BACKUP%
echo.
echo ==============================================
echo.

pause