
@echo off
setlocal EnableExtensions

title SYNC DATABASE SERVER KE LARAGON

REM ==================================================
REM KONFIGURASI MYSQL LARAGON
REM ==================================================

set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin"

set "LOCAL_DB=pembukuan"
set "LOCAL_USER=root"
set "LOCAL_HOST=127.0.0.1"
set "LOCAL_PORT=3306"

REM ==================================================
REM KONFIGURASI SERVER
REM ==================================================

set "WINSCP_SITE=Laser"
set "SERVER_DB=kingplat_laravel"
set "SERVER_SQL=/home/kingplat/kingplat_laravel_backup.sql"

REM ==================================================
REM KONFIGURASI FOLDER BACKUP
REM ==================================================

set "BACKUP_DIR=C:\BackupDatabase"

set "SQL_FILE=%BACKUP_DIR%\kingplat_laravel_backup.sql"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "TIMESTAMP=%%i"

if not defined TIMESTAMP (
    echo ERROR: Gagal mendapatkan waktu backup.
    pause
    exit /b 1
)

set "LOCAL_BACKUP=%BACKUP_DIR%\backup_lokal_%TIMESTAMP%.sql"

REM ==================================================
REM MEMBUAT FOLDER BACKUP
REM ==================================================

if not exist "%BACKUP_DIR%\" (
    mkdir "%BACKUP_DIR%"
)

if not exist "%BACKUP_DIR%\" (
    echo.
    echo ERROR: Folder backup tidak dapat dibuat.
    pause
    exit /b 1
)

REM ==================================================
REM MENENTUKAN LOKASI WINSCP
REM ==================================================

if exist "C:\Program Files\WinSCP\WinSCP.com" (
    set "WINSCP=C:\Program Files\WinSCP\WinSCP.com"
) else (
    set "WINSCP=C:\Program Files (x86)\WinSCP\WinSCP.com"
)

if not exist "%WINSCP%" (
    echo.
    echo ERROR: WinSCP.com tidak ditemukan.
    pause
    exit /b 1
)

REM ==================================================
REM MEMERIKSA MYSQL LARAGON
REM ==================================================

if not exist "%MYSQL_BIN%\mysql.exe" (
    echo.
    echo ERROR: mysql.exe tidak ditemukan.
    pause
    exit /b 1
)

if not exist "%MYSQL_BIN%\mysqldump.exe" (
    echo.
    echo ERROR: mysqldump.exe tidak ditemukan.
    pause
    exit /b 1
)

echo.
echo ==============================================
echo       SYNC DATABASE SERVER KE LARAGON
echo ==============================================
echo.
echo Server : %SERVER_DB%
echo Lokal  : %LOCAL_DB%
echo Backup : %BACKUP_DIR%
echo.
echo ==============================================
echo.

REM ==================================================
REM 1. MEMBUAT DUMP SERVER DAN DOWNLOAD
REM ==================================================

echo [1/5] Membuat dump database server...
echo.

REM Menghapus file download lama agar tidak
REM dianggap sebagai hasil download terbaru.

if exist "%SQL_FILE%" (
    del /q "%SQL_FILE%"
)

if exist "%SQL_FILE%" (
    echo.
    echo ERROR: File SQL lama gagal dihapus.
    echo DATABASE LOKAL BELUM DIUBAH.
    pause
    exit /b 1
)

REM PENTING:
REM Tidak memakai /ini=nul agar profil Laser terbaca.

"%WINSCP%" /command ^
    "option batch abort" ^
    "option confirm off" ^
    "open %WINSCP_SITE%" ^
    "call mysqldump --defaults-extra-file=/home/kingplat/.my.cnf --single-transaction --quick --no-tablespaces %SERVER_DB% > %SERVER_SQL%" ^
    "get ""%SERVER_SQL%"" ""%SQL_FILE%""" ^
    "exit"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo GAGAL MEMBUAT ATAU DOWNLOAD DATABASE
    echo DATABASE LOKAL BELUM DIUBAH
    echo ==============================================
    echo.
    echo Periksa koneksi WinSCP dan profil Laser.
    pause
    exit /b 1
)

echo.
echo Download database server berhasil.
echo.

REM ==================================================
REM 2. VALIDASI FILE SQL
REM ==================================================

echo [2/5] Memeriksa file database server...
echo.

if not exist "%SQL_FILE%" (
    echo.
    echo ERROR: File database server tidak ditemukan.
    echo %SQL_FILE%
    echo.
    echo DATABASE LOKAL BELUM DIUBAH.
    pause
    exit /b 1
)

for %%F in ("%SQL_FILE%") do (
    if %%~zF LSS 100 (
        echo.
        echo ERROR: File SQL kosong atau terlalu kecil.
        echo DATABASE LOKAL BELUM DIUBAH.
        pause
        exit /b 1
    )
)

echo File SQL ditemukan.
echo File: %SQL_FILE%
echo.

REM ==================================================
REM 3. BACKUP DATABASE LOKAL
REM ==================================================

echo [3/5] Backup database lokal saat ini...
echo.

"%MYSQL_BIN%\mysqldump.exe" ^
    -h "%LOCAL_HOST%" ^
    -P %LOCAL_PORT% ^
    -u "%LOCAL_USER%" ^
    --single-transaction ^
    --quick ^
    --no-tablespaces ^
    "%LOCAL_DB%" > "%LOCAL_BACKUP%"

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
    if %%~zF LSS 100 (
        echo.
        echo ERROR: File backup lokal terlalu kecil.
        echo DATABASE LOKAL BELUM DIUBAH.
        pause
        exit /b 1
    )
)

echo.
echo Backup database lokal berhasil.
echo.
echo Lokasi:
echo %LOCAL_BACKUP%
echo.

REM ==================================================
REM 4. RESET DATABASE LOKAL
REM ==================================================

echo [4/5] Reset database lokal...
echo.

"%MYSQL_BIN%\mysql.exe" ^
    -h "%LOCAL_HOST%" ^
    -P %LOCAL_PORT% ^
    -u "%LOCAL_USER%" ^
    -e "DROP DATABASE IF EXISTS %LOCAL_DB%; CREATE DATABASE %LOCAL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo RESET DATABASE LOKAL GAGAL
    echo ==============================================
    echo.
    echo Database lokal mungkin sudah berubah.
    echo Backup sebelumnya:
    echo %LOCAL_BACKUP%
    pause
    exit /b 1
)

echo.
echo Database lokal berhasil dibuat ulang.
echo.

REM ==================================================
REM 5. IMPORT DATABASE SERVER KE LARAGON
REM ==================================================

echo [5/5] Import database server ke Laragon...
echo.

"%MYSQL_BIN%\mysql.exe" ^
    -h "%LOCAL_HOST%" ^
    -P %LOCAL_PORT% ^
    -u "%LOCAL_USER%" ^
    "%LOCAL_DB%" < "%SQL_FILE%"

if errorlevel 1 (
    echo.
    echo ==============================================
    echo IMPORT DATABASE SERVER GAGAL
    echo ==============================================
    echo.
    echo Database lokal mungkin terisi sebagian.
    echo.
    echo Backup database lokal sebelumnya:
    echo %LOCAL_BACKUP%
    echo.
    echo Gunakan backup tersebut untuk restore.
    pause
    exit /b 1
)

REM ==================================================
REM SELESAI
REM ==================================================

echo.
echo ==============================================
echo        DATABASE BERHASIL DISINKRONKAN
echo ==============================================
echo.
echo DATABASE SERVER:
echo %SERVER_DB%
echo.
echo DATABASE LOKAL:
echo %LOCAL_DB%
echo.
echo FILE SQL SERVER:
echo %SQL_FILE%
echo.
echo BACKUP DATABASE LOKAL SEBELUM SYNC:
echo %LOCAL_BACKUP%
echo.
echo ==============================================
echo           PROSES SELESAI
echo ==============================================
echo.

pause
exit /b 0
