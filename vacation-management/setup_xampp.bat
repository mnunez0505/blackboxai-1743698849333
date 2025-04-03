@echo off
setlocal enabledelayedexpansion

:: Colors for output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "NC=[0m"

:: Print header
echo ================================
echo Vacation Management System Setup
echo XAMPP Windows Installation
echo ================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo %RED%Please run this script as Administrator%NC%
    pause
    exit /b 1
)

:: Find XAMPP installation
if exist "C:\xampp" (
    set "XAMPP_PATH=C:\xampp"
) else (
    echo %RED%XAMPP installation not found in C:\xampp%NC%
    echo Please enter your XAMPP installation path:
    set /p XAMPP_PATH=
)

:: Verify XAMPP path
if not exist "%XAMPP_PATH%\php\php.exe" (
    echo %RED%Invalid XAMPP installation path%NC%
    pause
    exit /b 1
)

:: Create necessary directories
echo %YELLOW%Creating directories...%NC%
mkdir "%XAMPP_PATH%\htdocs\vacation-management\uploads" 2>nul
mkdir "%XAMPP_PATH%\htdocs\vacation-management\logs" 2>nul
mkdir "%XAMPP_PATH%\htdocs\vacation-management\cache" 2>nul
echo %GREEN%Directories created successfully%NC%

:: Set directory permissions
echo %YELLOW%Setting directory permissions...%NC%
icacls "%XAMPP_PATH%\htdocs\vacation-management\uploads" /grant Everyone:(OI)(CI)F
icacls "%XAMPP_PATH%\htdocs\vacation-management\logs" /grant Everyone:(OI)(CI)F
icacls "%XAMPP_PATH%\htdocs\vacation-management\cache" /grant Everyone:(OI)(CI)F
echo %GREEN%Permissions set successfully%NC%

:: Check PHP version
echo %YELLOW%Checking PHP version...%NC%
"%XAMPP_PATH%\php\php.exe" -v | findstr /R "PHP [7-9]\."
if %errorLevel% neq 0 (
    echo %RED%PHP version 7.4 or higher is required%NC%
    pause
    exit /b 1
)

:: Check PHP extensions
echo %YELLOW%Checking PHP extensions...%NC%
set "REQUIRED_EXTENSIONS=pdo pdo_oci curl json mbstring openssl"
for %%e in (%REQUIRED_EXTENSIONS%) do (
    "%XAMPP_PATH%\php\php.exe" -m | findstr /I /C:"%%e" >nul
    if !errorLevel! neq 0 (
        echo %RED%Required extension not found: %%e%NC%
    ) else (
        echo %GREEN%Found extension: %%e%NC%
    )
)

:: Copy configuration file
echo %YELLOW%Setting up configuration...%NC%
if not exist "%XAMPP_PATH%\htdocs\vacation-management\config.php" (
    copy "%XAMPP_PATH%\htdocs\vacation-management\config.example.php" "%XAMPP_PATH%\htdocs\vacation-management\config.php"
    echo %GREEN%Configuration file created%NC%
) else (
    echo %YELLOW%Configuration file already exists%NC%
)

:: Configure Apache
echo %YELLOW%Configuring Apache...%NC%
findstr /C:"mod_rewrite.so" "%XAMPP_PATH%\apache\conf\httpd.conf" >nul
if !errorLevel! neq 0 (
    echo %RED%Please enable mod_rewrite in Apache configuration%NC%
) else (
    echo %GREEN%mod_rewrite is enabled%NC%
)

:: Create scheduled task for automation
echo %YELLOW%Setting up scheduled task...%NC%
schtasks /query /tn "VacationManagement_AutoVacation" >nul 2>&1
if !errorLevel! neq 0 (
    schtasks /create /tn "VacationManagement_AutoVacation" /tr "'%XAMPP_PATH%\php\php.exe' '%XAMPP_PATH%\htdocs\vacation-management\cron\auto_vacation.php'" /sc daily /st 00:00 /ru SYSTEM
    if !errorLevel! equ 0 (
        echo %GREEN%Scheduled task created successfully%NC%
    ) else (
        echo %RED%Failed to create scheduled task%NC%
    )
) else (
    echo %YELLOW%Scheduled task already exists%NC%
)

:: Run system test
echo %YELLOW%Running system test...%NC%
"%XAMPP_PATH%\php\php.exe" "%XAMPP_PATH%\htdocs\vacation-management\tests\test_system.php"

echo.
echo ================================
echo Installation Complete!
echo ================================
echo.
echo Next steps:
echo 1. Configure database settings in config.php
echo 2. Import database schema
echo 3. Configure email settings
echo 4. Update default admin password
echo.
echo Default login:
echo Username: admin
echo Password: Admin123!
echo.
echo Access the system at:
echo http://localhost/vacation-management/
echo.

pause