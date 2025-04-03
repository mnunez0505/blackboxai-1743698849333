# Vacation Management System Setup Script for XAMPP (PowerShell)
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
Write-Host "Vacation Management System Setup"
Write-Host "XAMPP Windows Installation"
Write-Host "================================="
Write-Host ""

# Find XAMPP installation
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Error "XAMPP installation not found in C:\xampp"
    $xamppPath = Read-Host "Please enter your XAMPP installation path"
}

# Verify XAMPP path
if (-not (Test-Path "$xamppPath\php\php.exe")) {
    Write-Error "Invalid XAMPP installation path"
    pause
    exit
}

# Create necessary directories
Write-Status "Creating directories..."
$directories = @(
    "$xamppPath\htdocs\vacation-management\uploads",
    "$xamppPath\htdocs\vacation-management\logs",
    "$xamppPath\htdocs\vacation-management\cache"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Success "Created directory: $dir"
    } else {
        Write-Host "Directory already exists: $dir" -ForegroundColor Yellow
    }
}

# Set directory permissions
Write-Status "Setting directory permissions..."
foreach ($dir in $directories) {
    $acl = Get-Acl $dir
    $accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        "Everyone",
        "FullControl",
        "ContainerInherit,ObjectInherit",
        "None",
        "Allow"
    )
    $acl.SetAccessRule($accessRule)
    Set-Acl $dir $acl
    Write-Success "Set permissions for: $dir"
}

# Check PHP version
Write-Status "Checking PHP version..."
$phpVersion = & "$xamppPath\php\php.exe" -v | Select-String -Pattern "PHP ([0-9]+\.[0-9]+)"
if ($phpVersion -match "PHP ([0-9]+\.[0-9]+)") {
    $version = [float]$matches[1]
    if ($version -ge 7.4) {
        Write-Success "PHP version $version is compatible"
    } else {
        Write-Error "PHP version $version is not compatible (7.4+ required)"
    }
} else {
    Write-Error "Could not determine PHP version"
}

# Check PHP extensions
Write-Status "Checking PHP extensions..."
$requiredExtensions = @("pdo", "pdo_oci", "curl", "json", "mbstring", "openssl")
$installedExtensions = & "$xamppPath\php\php.exe" -m

foreach ($ext in $requiredExtensions) {
    if ($installedExtensions -contains $ext) {
        Write-Success "Found extension: $ext"
    } else {
        Write-Error "Missing extension: $ext"
    }
}

# Copy configuration file
Write-Status "Setting up configuration..."
$configFile = "$xamppPath\htdocs\vacation-management\config.php"
$configExample = "$xamppPath\htdocs\vacation-management\config.example.php"

if (-not (Test-Path $configFile)) {
    Copy-Item $configExample $configFile
    Write-Success "Configuration file created"
} else {
    Write-Host "Configuration file already exists" -ForegroundColor Yellow
}

# Configure Apache
Write-Status "Configuring Apache..."
$httpdConf = "$xamppPath\apache\conf\httpd.conf"
$httpdContent = Get-Content $httpdConf -Raw

if ($httpdContent -match "LoadModule rewrite_module") {
    Write-Success "mod_rewrite is configured"
} else {
    Write-Error "Please enable mod_rewrite in Apache configuration"
}

# Create scheduled task
Write-Status "Setting up scheduled task..."
$taskName = "VacationManagement_AutoVacation"
$taskExists = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if (-not $taskExists) {
    $action = New-ScheduledTaskAction `
        -Execute "$xamppPath\php\php.exe" `
        -Argument "$xamppPath\htdocs\vacation-management\cron\auto_vacation.php"
    
    $trigger = New-ScheduledTaskTrigger -Daily -At "00:00"
    
    Register-ScheduledTask `
        -TaskName $taskName `
        -Action $action `
        -Trigger $trigger `
        -RunLevel Highest `
        -User "SYSTEM" | Out-Null
    
    Write-Success "Scheduled task created"
} else {
    Write-Host "Scheduled task already exists" -ForegroundColor Yellow
}

# Run system test
Write-Status "Running system test..."
& "$xamppPath\php\php.exe" "$xamppPath\htdocs\vacation-management\tests\test_system.php"

# Final instructions
Write-Host "`n================================="
Write-Host "Installation Complete!"
Write-Host "=================================`n"

Write-Host "Next steps:"
Write-Host "1. Configure database settings in config.php"
Write-Host "2. Import database schema"
Write-Host "3. Configure email settings"
Write-Host "4. Update default admin password`n"

Write-Host "Default login:"
Write-Host "Username: admin"
Write-Host "Password: Admin123!`n"

Write-Host "Access the system at:"
Write-Host "http://localhost/vacation-management/`n"

pause