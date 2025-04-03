# XAMPP Configuration Script for Vacation Management System
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
Write-Host "XAMPP Configuration Script"
Write-Host "Vacation Management System"
Write-Host "================================="
Write-Host ""

# Find XAMPP installation
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Error "XAMPP installation not found in C:\xampp"
    $xamppPath = Read-Host "Please enter your XAMPP installation path"
}

# Verify XAMPP path
$httpdConf = Join-Path $xamppPath "apache\conf\httpd.conf"
if (-not (Test-Path $httpdConf)) {
    Write-Error "Invalid XAMPP installation path"
    pause
    exit 1
}

# Configure Apache
Write-Status "Configuring Apache..."

# Backup original config
$backupFile = "$httpdConf.bak"
Copy-Item $httpdConf $backupFile
Write-Success "Created backup of httpd.conf"

try {
    # Read httpd.conf content
    $config = Get-Content $httpdConf -Raw

    # Enable mod_rewrite
    Write-Status "Enabling mod_rewrite..."
    $config = $config -replace '#LoadModule rewrite_module modules/mod_rewrite.so', 'LoadModule rewrite_module modules/mod_rewrite.so'

    # Configure directory permissions
    $htdocsPath = Join-Path $xamppPath "htdocs\vacation-management"
    $dirConfig = @"

# Vacation Management System Configuration
<Directory "$htdocsPath">
    AllowOverride All
    Require all granted
    
    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteBase /vacation-management/
    </IfModule>
</Directory>
"@

    # Add directory configuration if not exists
    if ($config -notmatch [regex]::Escape($htdocsPath)) {
        $config += $dirConfig
    }

    # Save configuration
    $config | Set-Content $httpdConf -Force
    Write-Success "Updated Apache configuration"

    # Configure .htaccess
    Write-Status "Configuring .htaccess..."
    $htaccessTemplate = Join-Path $htdocsPath ".htaccess.xampp"
    $htaccessFile = Join-Path $htdocsPath ".htaccess"

    if (Test-Path $htaccessTemplate) {
        Copy-Item $htaccessTemplate $htaccessFile -Force
        Write-Success "Created .htaccess from template"
    } else {
        Write-Error ".htaccess.xampp template not found"
    }

    # Set file permissions
    Write-Status "Setting file permissions..."
    $acl = Get-Acl $htdocsPath
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        "IUSR",
        "ReadAndExecute",
        "ContainerInherit,ObjectInherit",
        "None",
        "Allow"
    )
    $acl.AddAccessRule($rule)
    Set-Acl $htdocsPath $acl

    # Set permissions for special directories
    $specialDirs = @("uploads", "logs")
    foreach ($dir in $specialDirs) {
        $dirPath = Join-Path $htdocsPath $dir
        if (Test-Path $dirPath) {
            $acl = Get-Acl $dirPath
            $rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
                "IUSR",
                "Modify",
                "ContainerInherit,ObjectInherit",
                "None",
                "Allow"
            )
            $acl.AddAccessRule($rule)
            Set-Acl $dirPath $acl
        }
    }
    Write-Success "Set file permissions"

    # Restart Apache
    Write-Status "Restarting Apache..."
    Stop-Service Apache2.4
    Start-Service Apache2.4
    Write-Success "Apache restarted"

    # Test configuration
    Write-Status "Testing configuration..."
    $apacheExe = Join-Path $xamppPath "apache\bin\httpd.exe"
    $testResult = & $apacheExe -t 2>&1
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Apache configuration test passed"
    } else {
        throw "Apache configuration test failed: $testResult"
    }

} catch {
    Write-Error "Configuration failed: $_"
    Write-Status "Restoring backup configuration..."
    
    Copy-Item $backupFile $httpdConf -Force
    Stop-Service Apache2.4
    Start-Service Apache2.4
    
    Write-Success "Restored original configuration"
    pause
    exit 1
}

Write-Host "`nConfiguration completed successfully!" -ForegroundColor Green
Write-Host "`nNext steps:"
Write-Host "1. Test the application at http://localhost/vacation-management"
Write-Host "2. Check Apache error logs if you encounter issues"
Write-Host "3. Configure database settings in config.php`n"

pause