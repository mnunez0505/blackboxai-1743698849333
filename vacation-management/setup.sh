#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status messages
print_status() {
    echo -e "${YELLOW}[*]${NC} $1"
}

# Function to print success messages
print_success() {
    echo -e "${GREEN}[+]${NC} $1"
}

# Function to print error messages
print_error() {
    echo -e "${RED}[-]${NC} $1"
}

# Check if running with root privileges
if [ "$EUID" -ne 0 ]; then 
    print_error "Please run as root"
    exit 1
fi

# Welcome message
echo "=================================="
echo "Vacation Management System Setup"
echo "=================================="
echo

# Set directory permissions
print_status "Setting directory permissions..."
chmod 755 -R .
chmod 777 -R ./cron
print_success "Directory permissions set"

# Create necessary directories if they don't exist
print_status "Creating necessary directories..."
mkdir -p assets/css
mkdir -p assets/js
mkdir -p assets/images
print_success "Directories created"

# Check PHP version
print_status "Checking PHP version..."
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if (( $(echo "$PHP_VERSION >= 7.4" | bc -l) )); then
    print_success "PHP version $PHP_VERSION is compatible"
else
    print_error "PHP version $PHP_VERSION is not compatible. Please upgrade to PHP 7.4 or higher"
    exit 1
fi

# Check required PHP extensions
print_status "Checking required PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "pdo_oci" "curl" "json" "mbstring" "openssl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_success "Extension $ext is installed"
    else
        print_error "Extension $ext is missing"
        MISSING_EXTENSIONS+=($ext)
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo
    print_error "Please install the missing extensions:"
    for ext in "${MISSING_EXTENSIONS[@]}"; do
        echo "  - $ext"
    done
    exit 1
fi

# Check if config.php exists
print_status "Checking configuration file..."
if [ ! -f "config.php" ]; then
    print_error "config.php not found. Please create it from config.example.php"
    exit 1
fi

# Create log directory and file for cron job
print_status "Setting up log directory..."
mkdir -p logs
touch logs/auto_vacation.log
chmod 777 logs/auto_vacation.log
print_success "Log directory created"

# Check if Oracle client is installed
print_status "Checking Oracle client..."
if ! command -v sqlplus &> /dev/null; then
    print_error "Oracle client not found. Please install Oracle client"
    exit 1
fi
print_success "Oracle client found"

# Setup cron job
print_status "Setting up cron job..."
CRON_JOB="0 0 * * * /usr/bin/php $(pwd)/cron/auto_vacation.php >> $(pwd)/logs/auto_vacation.log 2>&1"
(crontab -l 2>/dev/null | grep -v "auto_vacation.php"; echo "$CRON_JOB") | crontab -
print_success "Cron job installed"

# Run system tests
print_status "Running system tests..."
php tests/test_system.php

echo
echo "=================================="
echo "Setup Complete!"
echo "=================================="
echo
echo "Next steps:"
echo "1. Update database credentials in config.php"
echo "2. Import database schema from database/schema.sql"
echo "3. Configure email settings in config.php"
echo "4. Update the default admin password"
echo "5. Set up HTTPS for production use"
echo
echo "For more information, please read the README.md file"
echo "=================================="