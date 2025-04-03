# MySQL Database Import Script for XAMPP
# Run as Administrator

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "Please run this script as Administrator" -ForegroundColor Red
    pause
    exit
}

# Functions for colored output
function Write-Status ($message) {
    Write-Host "[*] $message" -ForegroundColor Yellow
}

function Write-Success ($message) {
    Write-Host "[+] $message" -ForegroundColor Green
}

function Write-Error ($message) {
    Write-Host "[-] $message" -ForegroundColor Red
}

# Print header
Write-Host "================================="
Write-Host "MySQL Database Import Script"
Write-Host "For XAMPP Installation"
Write-Host "================================="
Write-Host ""

# Find XAMPP installation
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Error "XAMPP installation not found in C:\xampp"
    $xamppPath = Read-Host "Please enter your XAMPP installation path"
}

# Verify MySQL path
$mysqlPath = "$xamppPath\mysql\bin\mysql.exe"
if (-not (Test-Path $mysqlPath)) {
    Write-Error "MySQL not found in XAMPP installation"
    pause
    exit 1
}

# Database credentials
$dbUser = "root"
$dbPass = ""
$dbName = "vacation_management"

# Create database
Write-Status "Creating database..."
$createDbCmd = "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& $mysqlPath -u $dbUser -e $createDbCmd

if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to create database"
    pause
    exit 1
}
Write-Success "Database created successfully"

# Import schema
Write-Status "Importing schema..."
$schemaPath = Join-Path $PSScriptRoot "schema_mysql.sql"
if (-not (Test-Path $schemaPath)) {
    Write-Error "Schema file not found: $schemaPath"
    pause
    exit 1
}

& $mysqlPath -u $dbUser $dbName -e "source $schemaPath"

if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to import schema"
    pause
    exit 1
}
Write-Success "Schema imported successfully"

# Verify tables
Write-Status "Verifying database tables..."
$tables = & $mysqlPath -u $dbUser $dbName -e "SHOW TABLES;"

if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to verify tables"
    pause
    exit 1
}

Write-Host "`nDatabase tables:"
$tables | ForEach-Object { Write-Host "- $_" }

Write-Host "`nDatabase setup completed successfully!" -ForegroundColor Green
Write-Host "`nNext steps:"
Write-Host "1. Configure database connection in config.php"
Write-Host "2. Update admin email in users table"
Write-Host "3. Change default admin password after first login"
Write-Host "`nDefault login:"
Write-Host "Username: admin"
Write-Host "Password: Admin123!"

# Optional: Test connection
Write-Status "`nTesting database connection..."
try {
    $testQuery = "SELECT COUNT(*) FROM users;"
    $result = & $mysqlPath -u $dbUser $dbName -e $testQuery
    Write-Success "Database connection test successful"
} catch {
    Write-Error "Database connection test failed: $_"
}

# Create backup
Write-Status "Creating initial backup..."
$backupDir = Join-Path $PSScriptRoot "backups"
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = Join-Path $backupDir "initial_backup_$timestamp.sql"
$mysqldump = "$xamppPath\mysql\bin\mysqldump.exe"

if (Test-Path $mysqldump) {
    & $mysqldump -u $dbUser $dbName > $backupFile
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Initial backup created: $backupFile"
    } else {
        Write-Error "Failed to create backup"
    }
} else {
    Write-Error "mysqldump not found: $mysqldump"
}

Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")