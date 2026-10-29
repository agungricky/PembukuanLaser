
@echo off
setlocal EnableExtensions DisableDelayedExpansion

title Sync Database Server ke Laragon

REM ==================================================
REM 1. KONFIGURASI DATABASE
REM ==================================================

set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin"

set "LOCAL_DB=pembukuan"
set "LOCAL_USER=root"
set "LOCAL_HOST=127.0.0.1"
set "LOCAL_PORT=3306"

set "SERVER_DB=kingplat_laravel"
set "SERVER_SQL=/home/kingplat/kingplat_laravel_backup.sql"

REM ==================================================
REM 2. AMBIL TANGGAL DAN JAM OTOMATIS
REM ==================================================

for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd'"`) do set "TANGGAL=%%i"

for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "Get-Date -Format 'HH-mm-ss-fff'"`) do set "JAM=%%i"

if not defined TANGGAL (
    echo ERROR: Gagal membaca tanggal Windows.
    pause
    exit /b 1
)

if not defined JAM (
    echo ERROR: Gagal membaca jam Windows.
    pause
    exit /b 1
)

REM ==================================================
REM 3. DETEKSI FOLDER DOCUMENTS WINDOWS
REM ==================================================

set "DOCUMENTS_DIR="

for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('MyDocuments')"`) do set "DOCUMENTS_DIR=%%i"

set "BACKUP_ROOT="
set "BACKUP_DIR="

REM Coba folder Documents yang terdeteksi
if defined DOCUMENTS_DIR (
    set "BACKUP_ROOT=%DOCUMENTS_DIR%\SingkronDatabase"
    call :BUAT_FOLDER
)

REM Alternatif jika Documents gagal
if not defined BACKUP_DIR (
    echo Documents tidak bisa digunakan.
    echo Mencoba C:\SingkronDatabase...
    set "BACKUP_ROOT=C:\SingkronDatabase"
    call :BUAT_FOLDER
)

REM Alternatif folder di samping BAT
if not defined BACKUP_DIR (
    echo Folder C: tidak bisa digunakan.
    echo Mencoba folder di lokasi BAT...
    set "BACKUP_ROOT=%~dp0SingkronDatabase"
    call :BUAT_FOLDER
)

if not defined BACKUP_DIR (
    echo.
    echo ==============================================
    echo ERROR: SEMUA LOKASI BACKUP GAGAL
    echo DATABASE LOKAL BELUM DIUBAH
    echo ==============================================
    pause
    exit /b 1
)

set "SQL_FILE=%BACKUP_DIR%\kingplat_laravel_backup.sql"
set "LOCAL_BACKUP=%BACKUP_DIR%\backup_lokal_sebelum_sync.sql"

echo.
echo ==============================================
echo      SYNC DATABASE SERVER KE LOCAL
echo ==============================================
echo.
echo Tanggal       : %TANGGAL%
echo Jam           : %JAM%
echo Server        : %SERVER_DB%
echo Database Lokal: %LOCAL_DB%
echo.
echo Folder Backup:
echo %BACKUP_DIR%
echo.
echo ==============================================
echo.

REM ==================================================
REM 4. DETEKSI WINSCP
REM ==================================================

if exist "C:\Program Files\WinSCP\WinSCP.com" (
    set "WINSCP=C:\Program Files\WinSCP\WinSCP.com"
) else (
    set "WINSCP=C:\Program Files (x86)\WinSCP\WinSCP.com"
)

if not exist "%WINSCP%" (
    echo ERROR: WinSCP.com tidak ditemukan.
    pause
    exit /b 1
)

REM ==================================================
REM 5. VALIDASI MYSQL LARAGON
REM ==================================================

if not exist "%MYSQL_BIN%\mysql.exe" (
    echo ERROR: mysql.exe tidak ditemukan.
    echo %MYSQL_BIN%
    pause
    exit /b 1
)

if not exist "%MYSQL_BIN%\mysqldump.exe" (
    echo ERROR: mysqldump.exe tidak ditemukan.
    pause
    exit /b 1
)

REM ==================================================
REM 6. DOWNLOAD DATABASE SERVER
REM ==================================================

echo [1/5] Membuat dump database server...
echo.

"%WINSCP%" ^
    /command ^
    "option batch abort" ^
    "option confirm off" ^
    "open Laser" ^
    "call mysqldump --defaults-extra-file=/home/kingplat/.my.cnf --single-transaction --quick --no-tablespaces --routines --triggers --events %SERVER_DB% > %SERVER_SQL% && test -s %SERVER_SQL%" ^
    "get %SERVER_SQL% ""%SQL_FILE%""" ^
    "exit"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo GAGAL DOWNLOAD DATABASE SERVER
    echo DATABASE LOKAL BELUM DIUBAH
    echo ==============================================
    pause
    exit /b 1
)

echo.
echo Download database server berhasil.
echo.

REM ==================================================
REM 7. VALIDASI FILE DATABASE SERVER
REM ==================================================

echo [2/5] Validasi file SQL server...

if not exist "%SQL_FILE%" (
    echo ERROR: File SQL tidak ditemukan.
    echo DATABASE LOKAL BELUM DIUBAH
    pause
    exit /b 1
)

for %%F in ("%SQL_FILE%") do (
    if %%~zF EQU 0 (
        echo ERROR: File SQL kosong.
        echo DATABASE LOKAL BELUM DIUBAH
        pause
        exit /b 1
    )
)

echo Validasi file SQL berhasil.
echo.

REM ==================================================
REM 8. BACKUP DATABASE LOKAL
REM ==================================================

echo [3/5] Backup database lokal...
echo.

"%MYSQL_BIN%\mysqldump.exe" ^
    -h %LOCAL_HOST% ^
    -P %LOCAL_PORT% ^
    -u %LOCAL_USER% ^
    --single-transaction ^
    --quick ^
    --no-tablespaces ^
    --routines ^
    --triggers ^
    --events ^
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

for %%F in ("%LOCAL_BACKUP%") do (
    if %%~zF EQU 0 (
        echo ERROR: Backup lokal kosong.
        echo DATABASE LOKAL BELUM DIUBAH
        pause
        exit /b 1
    )
)

echo Backup database lokal berhasil.
echo.

REM ==================================================
REM 9. RESET DATABASE LOKAL
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
    echo Periksa kondisi database lokal.
    echo Backup tersedia di:
    echo %LOCAL_BACKUP%
    pause
    exit /b 1
)

echo Database lokal berhasil dibuat ulang.
echo.

REM ==================================================
REM 10. IMPORT DATABASE SERVER
REM ==================================================

echo [5/5] Import database server ke Laragon...
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
    echo Database lokal mungkin belum lengkap.
    echo.
    echo Backup database lokal:
    echo %LOCAL_BACKUP%
    echo.
    echo Database server:
    echo %SQL_FILE%
    echo.
    echo Restore manual diperlukan jika ingin kembali.
    pause
    exit /b 1
)

REM ==================================================
REM 11. SELESAI
REM ==================================================

echo.
echo ==============================================
echo       DATABASE BERHASIL DISINKRONKAN
echo ==============================================
echo.
echo Database Server : %SERVER_DB%
echo Database Lokal  : %LOCAL_DB%
echo Tanggal         : %TANGGAL%
echo Jam             : %JAM%
echo.
echo Folder Backup:
echo %BACKUP_DIR%
echo.
echo File Database Server:
echo %SQL_FILE%
echo.
echo File Backup Lokal:
echo %LOCAL_BACKUP%
echo.
echo ==============================================
echo.

pause
exit /b 0

REM ==================================================
REM FUNGSI MEMBUAT FOLDER DAN TEST IZIN TULIS
REM ==================================================

:BUAT_FOLDER
set "CANDIDATE=%BACKUP_ROOT%\%TANGGAL%\%JAM%"

if not exist "%CANDIDATE%\" (
    mkdir "%CANDIDATE%" 2>nul
)

if not exist "%CANDIDATE%\" (
    exit /b 1
)

REM Pastikan folder bisa ditulisi
type nul > "%CANDIDATE%\write_test.tmp" 2>nul

if errorlevel 1 (
    exit /b 1
)

del /q "%CANDIDATE%\write_test.tmp" >nul 2>nul

set "BACKUP_DIR=%CANDIDATE%"
exit /b 0
