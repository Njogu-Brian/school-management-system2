#!/bin/bash

# Database Backup Script
# Safely backs up database before migrations or schema changes
# Usage: ./scripts/backup-db.sh [environment]

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-production}
BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "${GREEN}Starting database backup for environment: $ENVIRONMENT${NC}"

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
elif [ -f .env.$ENVIRONMENT ]; then
    export $(cat .env.$ENVIRONMENT | grep -v '^#' | xargs)
else
    echo -e "${RED}Error: .env file not found${NC}"
    exit 1
fi

# Detect database type
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

if [ -z "$DB_DATABASE" ]; then
    echo -e "${RED}Error: DB_DATABASE not set in .env${NC}"
    exit 1
fi

# Generate backup filename
BACKUP_FILENAME="${DB_DATABASE}_${ENVIRONMENT}_${TIMESTAMP}.sql"
BACKUP_PATH="$BACKUP_DIR/$BACKUP_FILENAME"
BACKUP_CHECKSUM="$BACKUP_PATH.sha256"

echo -e "${YELLOW}Database: $DB_DATABASE${NC}"
echo -e "${YELLOW}Host: $DB_HOST:$DB_PORT${NC}"
echo -e "${YELLOW}Backup file: $BACKUP_PATH${NC}"

# Perform backup based on database type
case $DB_CONNECTION in
    mysql|mariadb)
        echo -e "${GREEN}Backing up MySQL/MariaDB database...${NC}"
        
        # Check if mysqldump is available
        if ! command -v mysqldump &> /dev/null; then
            echo -e "${RED}Error: mysqldump not found. Please install MySQL client tools.${NC}"
            exit 1
        fi
        
        # Build mysqldump command
        MYSQLDUMP_CMD="mysqldump"
        
        if [ -n "$DB_HOST" ]; then
            MYSQLDUMP_CMD="$MYSQLDUMP_CMD -h $DB_HOST"
        fi
        
        if [ -n "$DB_PORT" ]; then
            MYSQLDUMP_CMD="$MYSQLDUMP_CMD -P $DB_PORT"
        fi
        
        if [ -n "$DB_USERNAME" ]; then
            MYSQLDUMP_CMD="$MYSQLDUMP_CMD -u $DB_USERNAME"
        fi
        
        if [ -n "$DB_PASSWORD" ]; then
            MYSQLDUMP_CMD="$MYSQLDUMP_CMD -p$DB_PASSWORD"
        fi
        
        # Add options for better backup
        MYSQLDUMP_CMD="$MYSQLDUMP_CMD --single-transaction --routines --triggers --events"
        MYSQLDUMP_CMD="$MYSQLDUMP_CMD $DB_DATABASE > $BACKUP_PATH"
        
        # Execute backup
        eval $MYSQLDUMP_CMD
        
        if [ $? -ne 0 ]; then
            echo -e "${RED}Error: Database backup failed${NC}"
            rm -f "$BACKUP_PATH"
            exit 1
        fi
        ;;
        
    pgsql|postgresql)
        echo -e "${GREEN}Backing up PostgreSQL database...${NC}"
        
        # Check if pg_dump is available
        if ! command -v pg_dump &> /dev/null; then
            echo -e "${RED}Error: pg_dump not found. Please install PostgreSQL client tools.${NC}"
            exit 1
        fi
        
        # Set PGPASSWORD environment variable
        export PGPASSWORD=$DB_PASSWORD
        
        # Build pg_dump command
        PG_DUMP_CMD="pg_dump"
        
        if [ -n "$DB_HOST" ]; then
            PG_DUMP_CMD="$PG_DUMP_CMD -h $DB_HOST"
        fi
        
        if [ -n "$DB_PORT" ]; then
            PG_DUMP_CMD="$PG_DUMP_CMD -p $DB_PORT"
        fi
        
        if [ -n "$DB_USERNAME" ]; then
            PG_DUMP_CMD="$PG_DUMP_CMD -U $DB_USERNAME"
        fi
        
        PG_DUMP_CMD="$PG_DUMP_CMD -F c -f $BACKUP_PATH $DB_DATABASE"
        
        # Execute backup
        eval $PG_DUMP_CMD
        
        if [ $? -ne 0 ]; then
            echo -e "${RED}Error: Database backup failed${NC}"
            rm -f "$BACKUP_PATH"
            exit 1
        fi
        
        unset PGPASSWORD
        ;;
        
    sqlite)
        echo -e "${GREEN}Backing up SQLite database...${NC}"
        
        if [ ! -f "$DB_DATABASE" ]; then
            echo -e "${RED}Error: SQLite database file not found: $DB_DATABASE${NC}"
            exit 1
        fi
        
        cp "$DB_DATABASE" "$BACKUP_PATH"
        
        if [ $? -ne 0 ]; then
            echo -e "${RED}Error: SQLite backup failed${NC}"
            exit 1
        fi
        ;;
        
    *)
        echo -e "${RED}Error: Unsupported database type: $DB_CONNECTION${NC}"
        exit 1
        ;;
esac

# Verify backup file exists and has content
if [ ! -f "$BACKUP_PATH" ]; then
    echo -e "${RED}Error: Backup file was not created${NC}"
    exit 1
fi

# Check file size (must be > 1KB)
FILE_SIZE=$(stat -f%z "$BACKUP_PATH" 2>/dev/null || stat -c%s "$BACKUP_PATH" 2>/dev/null || echo "0")
MIN_SIZE=1024

if [ "$FILE_SIZE" -lt "$MIN_SIZE" ]; then
    echo -e "${YELLOW}Warning: Backup file is very small ($FILE_SIZE bytes). This might indicate an issue.${NC}"
    echo -e "${YELLOW}Continuing anyway, but please verify the backup manually.${NC}"
fi

# Generate checksum
if command -v sha256sum &> /dev/null; then
    sha256sum "$BACKUP_PATH" > "$BACKUP_CHECKSUM"
elif command -v shasum &> /dev/null; then
    shasum -a 256 "$BACKUP_PATH" > "$BACKUP_CHECKSUM"
else
    echo -e "${YELLOW}Warning: Checksum tool not found. Skipping checksum generation.${NC}"
fi

# Create backup metadata file
METADATA_FILE="$BACKUP_PATH.meta"
cat > "$METADATA_FILE" <<EOF
{
    "timestamp": "$TIMESTAMP",
    "environment": "$ENVIRONMENT",
    "database": "$DB_DATABASE",
    "connection": "$DB_CONNECTION",
    "host": "$DB_HOST",
    "port": "$DB_PORT",
    "filename": "$BACKUP_FILENAME",
    "size_bytes": $FILE_SIZE,
    "checksum_file": "$BACKUP_CHECKSUM"
}
EOF

echo -e "${GREEN}✓ Backup completed successfully${NC}"
echo -e "${GREEN}  File: $BACKUP_PATH${NC}"
echo -e "${GREEN}  Size: $FILE_SIZE bytes${NC}"
echo -e "${GREEN}  Checksum: $BACKUP_CHECKSUM${NC}"
echo -e "${GREEN}  Metadata: $METADATA_FILE${NC}"

# Return backup path for use in scripts
echo "$BACKUP_PATH"

exit 0

