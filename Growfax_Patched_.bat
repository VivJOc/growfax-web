@echo off
setlocal EnableDelayedExpansion
TITLE Growfax Hosts Patcher

:: ==== Admin Elevation ====
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] This script requires Administrator privileges.
    echo     Restarting with elevated permissions...
    powershell -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

:: ==== Paths ====
set "HOSTS_FILE=%windir%\System32\drivers\etc\hosts"
set "BACKUP_DIR=%~dp0backups"
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: ==== Growtopia entries ====
set "GROW1=15.235.232.75 growtopia1.com"
set "GROW2=15.235.232.75 growtopia2.com"
set "GROW3=15.235.232.75 www.growtopia1.com"
set "GROW4=15.235.232.75 www.growtopia2.com"

:: ==== Default Windows hosts template ====
setlocal DisableDelayedExpansion
set "DEFAULT_HOSTS=#
# Copyright (c) 1993-2009 Microsoft Corp.
#
# This is a sample HOSTS file used
# by Microsoft TCP/IP for Windows.
#
# localhost name resolution is handled within DNS itself.
#       127.0.0.1       localhost
#       ::1             localhost
127.0.0.1       localhost
::1             localhost
"
endlocal & set "DEFAULT_HOSTS=%DEFAULT_HOSTS%"

:MENU
cls
echo ================================================
echo                 GROWFAX PATCHER
echo ================================================
echo [1] Patch (add Growtopia hosts)
echo [2] Reset to Default Windows hosts
echo [3] Restore from Backup
echo [4] View Hosts File
echo [5] Exit
echo.
set /p CHOICE= Choose an option (1-5): 

if "%CHOICE%"=="1" goto PATCH
if "%CHOICE%"=="2" goto RESETDEFAULT
if "%CHOICE%"=="3" goto RESTORE
if "%CHOICE%"=="4" goto VIEW
if "%CHOICE%"=="5" exit /b
goto MENU

:GetTS
for /f "usebackq tokens=1 delims=." %%T in (`wmic os get LocalDateTime ^| find "."`) do set ts=%%T
set "TS=%ts:~0,8%-%ts:~8,6%"
goto :eof

:BackupNow
call :GetTS
set "BACKUP_FILE=%BACKUP_DIR%\hosts_%TS%.bak"
echo Creating backup: %BACKUP_FILE%
copy /y "%HOSTS_FILE%" "%BACKUP_FILE%" >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] Failed to create backup. Make sure the hosts file exists and this script is run as Administrator.
    pause
)
goto :eof

:PATCH
cls
echo ==== APPLY PATCH ====
echo.
set /p CONFIRM=Are you sure you want to patch Growtopia hosts? (Y/N): 
if /i not "%CONFIRM%"=="Y" (
    echo Canceled.
    pause
    goto MENU
)

call :BackupNow

echo Adding Growtopia entries if missing...
call :AddIfMissing "%GROW1%"
call :AddIfMissing "%GROW2%"
call :AddIfMissing "%GROW3%"
call :AddIfMissing "%GROW4%"

echo.
echo [✓] Patch completed successfully.
echo Backup created before changes.
pause
goto MENU

:RESETDEFAULT
cls
echo ==== RESET TO DEFAULT WINDOWS HOSTS ====
echo.
echo WARNING: This will overwrite your hosts file with the default Windows template.
echo All custom entries will be lost.
echo.
set /p CONFIRM=Are you sure you want to reset to default? (Y/N): 
if /i not "%CONFIRM%"=="Y" (
    echo Canceled.
    pause
    goto MENU
)

call :BackupNow

echo Writing default template to "%HOSTS_FILE%" ...
(
    echo %DEFAULT_HOSTS%
) > "%HOSTS_FILE%"

if %errorlevel% equ 0 (
    echo [✓] Successfully reset to default.
) else (
    echo [x] Failed to write hosts file. Try running manually.
)
pause
goto MENU

:RESTORE
cls
echo ==== RESTORE BACKUP ====
echo.
echo Backup folder: %BACKUP_DIR%
echo.
dir /b "%BACKUP_DIR%\hosts_*.bak"
echo.
set /p REST=Enter backup file name (example: hosts_20251029-204512.bak): 
if "%REST%"=="" (
    echo Canceled.
    pause
    goto MENU
)
set /p CONFIRM=Are you sure you want to restore this backup? (Y/N): 
if /i not "%CONFIRM%"=="Y" (
    echo Canceled.
    pause
    goto MENU
)
if exist "%BACKUP_DIR%\%REST%" (
    copy /y "%BACKUP_DIR%\%REST%" "%HOSTS_FILE%" >nul 2>&1
    if %errorlevel% equ 0 (
        echo [✓] Restore successful.
    ) else (
        echo [x] Failed to restore file.
    )
) else (
    echo [x] File not found: %BACKUP_DIR%\%REST%
)
pause
goto MENU

:VIEW
cls
echo ==== HOSTS FILE CONTENT ====
echo (Press CTRL+C to cancel/exit)
echo.
type "%HOSTS_FILE%"
echo.
pause
goto MENU

:AddIfMissing
setlocal
set "LINE=%~1"
findstr /x /c:"%LINE%" "%HOSTS_FILE%" >nul 2>&1
if %errorlevel% equ 0 (
    echo [=] Already exists: %LINE%
) else (
    echo [+] Adding: %LINE%
    >> "%HOSTS_FILE%" echo %LINE%
)
endlocal
goto :eof
