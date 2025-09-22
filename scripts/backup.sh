#!/bin/bash

# ========================================
# Registration System Backup Script
# ========================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BACKUP_DIR="./backups"
DATE=$(date +"%Y%m%d_%H%M%S")
DB_CONTAINER="registration_system_db_1"
DB_NAME="registration_db"
DB_USER="root"
DB_PASS="rootpassword"

echo -e "${YELLOW}🔄 Registration System Backup Script${NC}"
echo "=================================="

# Create backup directory
mkdir -p $BACKUP_DIR

# 1. Database Backup
echo -e "${YELLOW}📊 Backing up database...${NC}"
docker exec $DB_CONTAINER mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/database_$DATE.sql"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database backup completed: $BACKUP_DIR/database_$DATE.sql${NC}"
else
    echo -e "${RED}❌ Database backup failed${NC}"
    exit 1
fi

# 2. Upload Files Backup (only if exists)
if [ -d "uploads" ]; then
    echo -e "${YELLOW}📁 Backing up upload files...${NC}"
    tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" uploads/
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Upload files backup completed: $BACKUP_DIR/uploads_$DATE.tar.gz${NC}"
    else
        echo -e "${RED}❌ Upload files backup failed${NC}"
    fi
fi

# 3. Configuration Backup
echo -e "${YELLOW}⚙️  Backing up configuration files...${NC}"
tar -czf "$BACKUP_DIR/config_$DATE.tar.gz" \
    docker-compose.yml \
    Dockerfile \
    .env.example \
    backend/config/ \
    2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Configuration backup completed: $BACKUP_DIR/config_$DATE.tar.gz${NC}"
else
    echo -e "${RED}❌ Configuration backup failed${NC}"
fi

# 4. Show backup summary
echo ""
echo -e "${YELLOW}📋 Backup Summary${NC}"
echo "=================="
echo -e "${GREEN}Date: $(date)${NC}"
echo -e "${GREEN}Location: $BACKUP_DIR/${NC}"
echo ""

ls -lh $BACKUP_DIR/*$DATE*

# 5. Cleanup old backups (keep last 10)
echo ""
echo -e "${YELLOW}🧹 Cleaning up old backups...${NC}"
cd $BACKUP_DIR
ls -t database_*.sql | tail -n +11 | xargs -r rm
ls -t uploads_*.tar.gz | tail -n +11 | xargs -r rm  
ls -t config_*.tar.gz | tail -n +11 | xargs -r rm
echo -e "${GREEN}✅ Cleanup completed${NC}"

echo ""
echo -e "${GREEN}🎉 Backup process completed successfully!${NC}"