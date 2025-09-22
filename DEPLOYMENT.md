# Certificate Registration System - Deployment Guide

## ຄູ່ມືການນຳໃຊ້ລະບົບລົງທະບຽນໃບຢັ້ງຢືນ

### Production Deployment Steps

1. **Database Setup:**
   ```bash
   mysql -u root -p
   CREATE DATABASE registration_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'registration_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON registration_db.* TO 'registration_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

2. **Import Database Schema:**
   ```bash
   mysql -u registration_user -p registration_db < init.sql
   ```

3. **Configure Environment:**
   ```bash
   cp .env.production .env
   # Edit .env file with your actual values
   nano .env
   ```

4. **Set Permissions:**
   ```bash
   chmod 755 backend/
   chmod 644 backend/*.php
   chmod 777 uploads/
   chmod 777 logs/
   ```

5. **Web Server Configuration (Apache):**
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /path/to/registrations/frontend
       
       # PHP Backend
       Alias /api /path/to/registrations/backend
       <Directory "/path/to/registrations/backend">
           AllowOverride All
           Require all granted
       </Directory>
       
       # File Uploads
       Alias /uploads /path/to/registrations/uploads
       <Directory "/path/to/registrations/uploads">
           AllowOverride None
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/registration_error.log
       CustomLog ${APACHE_LOG_DIR}/registration_access.log combined
   </VirtualHost>
   ```

6. **Web Server Configuration (Nginx):**
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;
       root /path/to/registrations/frontend;
       index index.html;

       # Frontend files
       location / {
           try_files $uri $uri/ =404;
       }

       # PHP Backend
       location /api {
           alias /path/to/registrations/backend;
           location ~ \.php$ {
               fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
               fastcgi_param SCRIPT_FILENAME $request_filename;
               include fastcgi_params;
           }
       }

       # File uploads
       location /uploads {
           alias /path/to/registrations/uploads;
       }
   }
   ```

7. **SSL Certificate (Let's Encrypt):**
   ```bash
   sudo certbot --apache -d yourdomain.com
   # or for Nginx:
   sudo certbot --nginx -d yourdomain.com
   ```

### Security Checklist

- [ ] Change default admin password
- [ ] Set strong database password
- [ ] Configure SSL certificate
- [ ] Set proper file permissions
- [ ] Enable firewall rules
- [ ] Configure backup system
- [ ] Test all functionality

### File Structure (Production Ready)
```
registrations/
├── backend/           # PHP API files
├── frontend/          # HTML/CSS/JS files
├── uploads/           # User uploaded files (writable)
├── logs/             # Application logs (writable)
├── images/           # System images (QR code, etc.)
├── scripts/          # Backup/restore scripts
├── .env             # Production environment config
├── .env.production  # Production template
├── init.sql         # Database initialization
├── docker-compose.yml # Docker deployment (optional)
└── DEPLOYMENT.md    # This file
```

### Default Admin Login
- Username: admin
- Password: (set in .env file - DEFAULT_ADMIN_PASSWORD)

### System Requirements
- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- SSL certificate for production

### Support
For technical support or issues, contact the development team.