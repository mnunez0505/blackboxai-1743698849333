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

# Load configuration
if [ -f "config.php" ]; then
    # Extract database credentials from config.php
    DB_USER=$(grep "DB_USER" config.php | cut -d "'" -f 4)
    DB_PASS=$(grep "DB_PASS" config.php | cut -d "'" -f 4)
    DB_DSN=$(grep "DB_DSN" config.php | cut -d "'" -f 4)
else
    print_error "Configuration file not found!"
    exit 1
fi

# Create backup directory if it doesn't exist
BACKUP_DIR="backups"
mkdir -p "$BACKUP_DIR"

# Generate timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Backup file names
DB_BACKUP="$BACKUP_DIR/db_backup_$TIMESTAMP.dmp"
FILES_BACKUP="$BACKUP_DIR/files_backup_$TIMESTAMP.tar.gz"
LOG_FILE="$BACKUP_DIR/backup_$TIMESTAMP.log"

# Start backup process
print_status "Starting backup process..."
echo "Backup started at $(date)" > "$LOG_FILE"

# Database backup
print_status "Backing up database..."
if exp "$DB_USER/$DB_PASS@$DB_DSN" file="$DB_BACKUP" log="$LOG_FILE.exp" >> "$LOG_FILE" 2>&1; then
    print_success "Database backup completed: $DB_BACKUP"
else
    print_error "Database backup failed! Check $LOG_FILE for details."
fi

# Files backup
print_status "Backing up files..."
if tar -czf "$FILES_BACKUP" \
    --exclude="$BACKUP_DIR" \
    --exclude="*.log" \
    --exclude="*.dmp" \
    --exclude="node_modules" \
    --exclude="vendor" \
    --exclude=".git" \
    . >> "$LOG_FILE" 2>&1; then
    print_success "Files backup completed: $FILES_BACKUP"
else
    print_error "Files backup failed! Check $LOG_FILE for details."
fi

# Calculate backup sizes
DB_SIZE=$(du -h "$DB_BACKUP" | cut -f1)
FILES_SIZE=$(du -h "$FILES_BACKUP" | cut -f1)

# Clean old backups (keep last 5)
print_status "Cleaning old backups..."
cd "$BACKUP_DIR" || exit
ls -t db_backup_* | tail -n +6 | xargs -r rm
ls -t files_backup_* | tail -n +6 | xargs -r rm
ls -t backup_*.log | tail -n +6 | xargs -r rm
cd - > /dev/null || exit

# Backup summary
echo -e "\nBackup Summary:"
echo "================"
echo "Database Backup: $DB_BACKUP ($DB_SIZE)"
echo "Files Backup: $FILES_BACKUP ($FILES_SIZE)"
echo "Log File: $LOG_FILE"
echo "Timestamp: $TIMESTAMP"

# Add backup summary to log
{
    echo -e "\nBackup Summary"
    echo "=============="
    echo "Database Backup: $DB_BACKUP ($DB_SIZE)"
    echo "Files Backup: $FILES_BACKUP ($FILES_SIZE)"
    echo "Completed at: $(date)"
} >> "$LOG_FILE"

print_success "Backup completed successfully!"
print_status "Backup files are stored in the '$BACKUP_DIR' directory"
print_status "Check $LOG_FILE for detailed information"

# Optional: Upload to remote storage
if [ -n "$REMOTE_BACKUP_PATH" ]; then
    print_status "Uploading backups to remote storage..."
    if rsync -avz "$BACKUP_DIR/" "$REMOTE_BACKUP_PATH/" >> "$LOG_FILE" 2>&1; then
        print_success "Remote backup completed"
    else
        print_error "Remote backup failed! Check $LOG_FILE for details."
    fi
fi

# Set proper permissions
chmod 600 "$DB_BACKUP"
chmod 600 "$FILES_BACKUP"
chmod 600 "$LOG_FILE"

# Exit successfully
exit 0