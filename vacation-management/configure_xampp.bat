@echo off
setlocal enabledelayedexpansion

:: Colors for output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "NC=[0m"

:: Print header
echo ================================
echo XAMPP Configuration Script
echo Vacation Management System
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
if not exist "%XAMPP_PATH%\apache\conf\httpd.conf" (
    echo %RED%Invalid XAMPP installation path%NC%
    pause
    exit /b 1
)

:: Configure Apache
echo %YELLOW%Configuring Apache...%NC%

:: Enable required modules
echo %YELLOW%Enabling required modules...%NC%
set "HTTPD_CONF=%XAMPP_PATH%\apache\conf\httpd.conf"
set "TEMP_CONF=%TEMP%\httpd.conf.tmp"

:: Backup original config
copy "%HTTPD_CONF%" "%HTTPD_CONF%.bak" >nul
echo %GREEN%Created backup of httpd.conf%NC%

:: Enable mod_rewrite
type "%HTTPD_CONF%" | findstr /v /c:"#LoadModule rewrite_module" > "%TEMP_CONF%"
echo LoadModule rewrite_module modules/mod_rewrite.so >> "%TEMP_CONF%"
move /y "%TEMP_CONF%" "%HTTPD_CONF%" >nul
echo %GREEN%Enabled mod_rewrite%NC%

:: Configure directory permissions
echo %YELLOW%Configuring directory permissions...%NC%
set "HTDOCS_PATH=%XAMPP_PATH%\htdocs\vacation-management"

:: Create .htaccess from template
if exist "%HTDOCS_PATH%\.htaccess.xampp" (
    copy "%HTDOCS_PATH%\.htaccess.xampp" "%HTDOCS_PATH%\.htaccess" /y >nul
    echo %GREEN%Created .htaccess from template%NC%
) else (
    echo %RED%.htaccess.xampp template not found%NC%
)

:: Update Apache configuration for directory
echo ^<Directory "%HTDOCS_PATH%"^> >> "%HTTPD_CONF%"
echo     AllowOverride All >> "%HTTPD_CONF%"
echo     Require all granted >> "%HTTPD_CONF%"
echo ^</Directory^> >> "%HTTPD_CONF%"
echo %GREEN%Added directory configuration to httpd.conf%NC%

:: Set file permissions
echo %YELLOW%Setting file permissions...%NC%
icacls "%HTDOCS_PATH%" /grant "IUSR:(OI)(CI)R" /T
icacls "%HTDOCS_PATH%" /grant "IIS_IUSRS:(OI)(CI)R" /T
icacls "%HTDOCS_PATH%\uploads" /grant "IUSR:(OI)(CI)M" /T
icacls "%HTDOCS_PATH%\logs" /grant "IUSR:(OI)(CI)M" /T
echo %GREEN%Set file permissions%NC%

:: Restart Apache
echo %YELLOW%Restarting Apache...%NC%
net stop Apache2.4
net start Apache2.4
echo %GREEN%Apache restarted%NC%

:: Test configuration
echo %YELLOW%Testing configuration...%NC%
"%XAMPP_PATH%\apache\bin\httpd.exe" -t
if %errorLevel% equ 0 (
    echo %GREEN%Apache configuration test passed%NC%
) else (
    echo %RED%Apache configuration test failed%NC%
    echo %YELLOW%Restoring backup configuration...%NC%
    copy "%HTTPD_CONF%.bak" "%HTTPD_CONF%" /y >nul
    net stop Apache2.4
    net start Apache2.4
    echo %GREEN%Restored original configuration%NC%
    pause
    exit /b 1
)

echo.
echo %GREEN%Configuration completed successfully!%NC%
echo.
echo Next steps:
echo 1. Test the application at http://localhost/vacation-management
echo 2. Check Apache error logs if you encounter issues
echo 3. Configure database settings in config.php
echo.

pause