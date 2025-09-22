#!/bin/bash

# ========================================
# Registration System Restore Script
# ========================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKUP_DIR="./backups"
DB_CONTAINER="registration_system_db_1"
DB_NAME="registration_db"
DB_USER="root"
DB_PASS="rootpassword"

echo -e "${YELLOW}🔄 Registration System Restore Script${NC}"
echo "====================================="

# Check if backup directory exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}❌ Backup directory not found: $BACKUP_DIR${NC}"
    exit 1
fi

# List available backups
echo -e "${BLUE}📋 Available backups:${NC}"
echo ""
echo -e "${YELLOW}Database backups:${NC}"
ls -lh $BACKUP_DIR/database_*.sql 2>/dev/null | head -10

echo ""
echo -e "${YELLOW}Upload backups:${NC}"
ls -lh $BACKUP_DIR/uploads_*.tar.gz 2>/dev/null | head -5

echo ""
echo -e "${YELLOW}Config backups:${NC}"
ls -lh $BACKUP_DIR/config_*.tar.gz 2>/dev/null | head -5

# Ask user to select database backup
echo ""
echo -e "${BLUE}🔍 Please enter the database backup filename to restore:${NC}"
echo -e "${YELLOW}(e.g., database_20250922_143000.sql)${NC}"
read -p "Database backup file: " DB_BACKUP_FILE

if [ ! -f "$BACKUP_DIR/$DB_BACKUP_FILE" ]; then
    echo -e "${RED}❌ Database backup file not found: $BACKUP_DIR/$DB_BACKUP_FILE${NC}"
    exit 1
fi

# Confirmation
echo ""
echo -e "${RED}⚠️  WARNING: This will replace all existing data!${NC}"
echo -e "${YELLOW}Database backup: $DB_BACKUP_FILE${NC}"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}🚫 Restore cancelled${NC}"
    exit 0
fi

# 1. Restore Database
echo ""
echo -e "${YELLOW}📊 Restoring database...${NC}"
docker exec -i $DB_CONTAINER mysql -u$DB_USER -p$DB_PASS $DB_NAME < "$BACKUP_DIR/$DB_BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database restore completed${NC}"
else
    echo -e "${RED}❌ Database restore failed${NC}"
    exit 1
fi

# 2. Ask about uploads restore
echo ""
read -p "Do you want to restore upload files? (yes/no): " RESTORE_UPLOADS

if [ "$RESTORE_UPLOADS" = "yes" ]; then
    echo -e "${BLUE}📁 Available upload backups:${NC}"
    ls -lh $BACKUP_DIR/uploads_*.tar.gz 2>/dev/null
    
    echo ""
    read -p "Upload backup filename: " UPLOAD_BACKUP_FILE
    
    if [ -f "$BACKUP_DIR/$UPLOAD_BACKUP_FILE" ]; then
        echo -e "${YELLOW}📁 Restoring upload files...${NC}"
        
        # Backup current uploads if exists
        if [ -d "uploads" ]; then
            mv uploads uploads_backup_$(date +"%Y%m%d_%H%M%S")
            echo -e "${YELLOW}📦 Current uploads backed up${NC}"
        fi
        
        # Restore from backup
        tar -xzf "$BACKUP_DIR/$UPLOAD_BACKUP_FILE"
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅ Upload files restore completed${NC}"
        else
            echo -e "${RED}❌ Upload files restore failed${NC}"
        fi
    else
        echo -e "${RED}❌ Upload backup file not found${NC}"
    fi
fi

# 3. Restart services
echo ""
echo -e "${YELLOW}🔄 Restarting services...${NC}"
docker-compose restart

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Services restarted${NC}"
else
    echo -e "${RED}❌ Service restart failed${NC}"
fi

# 4. Verify restore
echo ""
echo -e "${YELLOW}🔍 Verifying restore...${NC}"
sleep 5

# Check if web service is responding
if curl -s http://localhost:8080/backend/api/get_count.php > /dev/null; then
    echo -e "${GREEN}✅ Web service is responding${NC}"
else
    echo -e "${RED}❌ Web service is not responding${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Restore process completed!${NC}"
echo -e "${BLUE}💡 You can now access the system at: http://localhost:8080${NC}"