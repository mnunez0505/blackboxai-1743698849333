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

# Maintenance log file
LOG_DIR="logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/maintenance_$(date +%Y%m%d_%H%M%S).log"

# Start maintenance log
echo "Maintenance started at $(date)" > "$LOG_FILE"

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
    echo "$1"
}

# Function to check disk space
check_disk_space() {
    print_status "Checking disk space..."
    df -h >> "$LOG_FILE"
    
    # Get disk usage percentage
    DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 90 ]; then
        print_error "Warning: Disk usage is above 90%"
        log_message "WARNING: High disk usage detected: $DISK_USAGE%"
    else
        print_success "Disk space check completed"
    fi
}

# Function to clean log files
clean_logs() {
    print_status "Cleaning old log files..."
    find ./logs -name "*.log" -type f -mtime +30 -delete
    find ./cron -name "*.log" -type f -mtime +30 -delete
    print_success "Log cleanup completed"
    log_message "Old log files cleaned"
}

# Function to clean cache
clean_cache() {
    print_status "Cleaning cache..."
    rm -rf cache/*
    mkdir -p cache
    chmod 777 cache
    print_success "Cache cleanup completed"
    log_message "Cache directory cleaned"
}

# Function to check file permissions
check_permissions() {
    print_status "Checking file permissions..."
    
    # Define expected permissions
    chmod 755 .
    chmod 755 *.php
    chmod 644 *.md
    chmod 644 .htaccess
    chmod -R 755 assets
    chmod -R 777 uploads
    chmod -R 777 logs
    chmod -R 777 cache
    chmod -R 755 cron
    chmod 755 *.sh
    
    print_success "File permissions updated"
    log_message "File permissions checked and updated"
}

# Function to check PHP configuration
check_php_config() {
    print_status "Checking PHP configuration..."
    
    # Check PHP version
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    if (( $(echo "$PHP_VERSION >= 7.4" | bc -l) )); then
        print_success "PHP version $PHP_VERSION is compatible"
    else
        print_error "PHP version $PHP_VERSION is not compatible"
    fi
    
    # Check PHP extensions
    REQUIRED_EXTENSIONS=("pdo" "pdo_oci" "curl" "json" "mbstring" "openssl")
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "Extension $ext is installed"
        else
            print_error "Extension $ext is missing"
        fi
    done
    
    log_message "PHP configuration checked"
}

# Function to optimize database
optimize_database() {
    print_status "Optimizing database..."
    
    if [ -f "config.php" ]; then
        # Extract database credentials from config.php
        DB_USER=$(grep "DB_USER" config.php | cut -d "'" -f 4)
        DB_PASS=$(grep "DB_PASS" config.php | cut -d "'" -f 4)
        DB_DSN=$(grep "DB_DSN" config.php | cut -d "'" -f 4)
        
        # Run database optimization commands
        sqlplus "$DB_USER/$DB_PASS@$DB_DSN" << EOF >> "$LOG_FILE" 2>&1
        EXEC DBMS_STATS.GATHER_SCHEMA_STATS(ownname => '$DB_USER');
        ALTER SYSTEM FLUSH SHARED_POOL;
        ALTER SYSTEM FLUSH BUFFER_CACHE;
        EXIT;
EOF
        print_success "Database optimization completed"
        log_message "Database optimized"
    else
        print_error "Database configuration not found"
    fi
}

# Function to check cron jobs
check_cron_jobs() {
    print_status "Checking cron jobs..."
    
    # Check if auto_vacation cron is installed
    if crontab -l | grep -q "auto_vacation.php"; then
        print_success "Vacation automation cron job is installed"
    else
        print_error "Vacation automation cron job is missing"
    fi
    
    log_message "Cron jobs checked"
}

# Function to test email configuration
test_email() {
    print_status "Testing email configuration..."
    
    php -r "
        require 'config.php';
        require 'functions.php';
        \$result = sendEmailNotification(
            'test@example.com',
            'System Maintenance Test',
            'This is a test email from the maintenance script.'
        );
        echo \$result ? 'Email test successful' : 'Email test failed';
    " >> "$LOG_FILE" 2>&1
    
    log_message "Email configuration tested"
}

# Main maintenance menu
while true; do
    echo -e "\nMaintenance Tasks:"
    echo "1. Check disk space"
    echo "2. Clean log files"
    echo "3. Clean cache"
    echo "4. Check/update file permissions"
    echo "5. Check PHP configuration"
    echo "6. Optimize database"
    echo "7. Check cron jobs"
    echo "8. Test email configuration"
    echo "9. Run all tasks"
    echo "0. Exit"
    
    read -rp "Select a task (0-9): " choice
    
    case $choice in
        1) check_disk_space ;;
        2) clean_logs ;;
        3) clean_cache ;;
        4) check_permissions ;;
        5) check_php_config ;;
        6) optimize_database ;;
        7) check_cron_jobs ;;
        8) test_email ;;
        9)
            check_disk_space
            clean_logs
            clean_cache
            check_permissions
            check_php_config
            optimize_database
            check_cron_jobs
            test_email
            ;;
        0)
            print_success "Maintenance completed"
            log_message "Maintenance script finished"
            exit 0
            ;;
        *)
            print_error "Invalid choice"
            ;;
    esac
done