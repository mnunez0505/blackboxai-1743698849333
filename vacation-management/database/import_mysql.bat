@echo off
setlocal enabledelayedexpansion

:: Colors for output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "NC=[0m"

:: Print header
echo ================================
echo MySQL Database Import Script
echo For XAMPP Installation
echo ================================
echo.

:: Find XAMPP installation
if exist "C:\xampp" (
    set "XAMPP_PATH=C:\xampp"
) else (
    echo %RED%XAMPP installation not found in C:\xampp%NC%
    echo Please enter your XAMPP installation path:
    set /p XAMPP_PATH=
)

:: Verify MySQL path
if not exist "%XAMPP_PATH%\mysql\bin\mysql.exe" (
    echo %RED%MySQL not found in XAMPP installation%NC%
    pause
    exit /b 1
)

:: Database credentials
set "DB_USER=root"
set "DB_PASS="
set "DB_NAME=vacation_management"

:: Create database
echo %YELLOW%Creating database...%NC%
"%XAMPP_PATH%\mysql\bin\mysql.exe" -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo %RED%Failed to create database%NC%
    pause
    exit /b 1
)
echo %GREEN%Database created successfully%NC%

:: Import schema
echo %YELLOW%Importing schema...%NC%
"%XAMPP_PATH%\mysql\bin\mysql.exe" -u %DB_USER% %DB_NAME% < schema_mysql.sql
if %errorlevel% neq 0 (
    echo %RED%Failed to import schema%NC%
    pause
    exit /b 1
)
echo %GREEN%Schema imported successfully%NC%

:: Verify tables
echo %YELLOW%Verifying database tables...%NC%
"%XAMPP_PATH%\mysql\bin\mysql.exe" -u %DB_USER% %DB_NAME% -e "SHOW TABLES;"
if %errorlevel% neq 0 (
    echo %RED%Failed to verify tables%NC%
    pause
    exit /b 1
)

echo.
echo %GREEN%Database setup completed successfully!%NC%
echo.
echo Next steps:
echo 1. Configure database connection in config.php
echo 2. Update admin email in users table
echo 3. Change default admin password after first login
echo.
echo Default login:
echo Username: admin
echo Password: Admin123!
echo.

pause