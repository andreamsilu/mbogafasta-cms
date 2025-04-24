# Hosting Instructions for www.eazysafari.com

## Server Requirements
- Ubuntu 20.04 LTS or later
- Nginx
- PHP 8.2 or later
- MySQL 8.0 or later
- Composer
- SSL Certificate (Let's Encrypt)

## 1. Server Setup

### Install Required Packages
```bash
# Update system
sudo apt update
sudo apt upgrade

# Install required packages
sudo apt install nginx mysql-server php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip composer
```

### Configure Firewall
```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow 'OpenSSH'
sudo ufw enable
```

## 2. Database Setup

### Create Database and User
```bash
# Login to MySQL
sudo mysql

# Create database and user
CREATE DATABASE mbogafastadb;
CREATE USER 'msilu'@'localhost' IDENTIFIED BY 'passw0rd';
GRANT ALL PRIVILEGES ON mbogafastadb.* TO 'msilu'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database schema
mysql -u msilu -p mbogafastadb < /path/to/mbogafastadb.sql
```

## 3. Project Setup

### Directory Structure
```bash
# Create project directory
sudo mkdir -p /var/www/html/myprojects
sudo chown -R $USER:$USER /var/www/html/myprojects

# Clone or copy project
cd /var/www/html/myprojects
git clone your-repository-url mbogafasta-cms

# Install dependencies
cd mbogafasta-cms
composer install

# Set permissions
sudo chown -R www-data:www-data /var/www/html/myprojects/mbogafasta-cms
sudo chmod -R 755 /var/www/html/myprojects/mbogafasta-cms
sudo chmod -R 777 /var/www/html/myprojects/mbogafasta-cms/uploads
sudo chmod -R 777 /var/www/html/myprojects/mbogafasta-cms/logs
```

## 4. Nginx Configuration

### Create Nginx Configuration
Create file: `/etc/nginx/sites-available/mbogafasta-cms.conf`
```nginx
server {
    listen 80;
    server_name www.eazysafari.com eazysafari.com;
    root /var/www/html/myprojects/mbogafasta-cms;
    index index.php index.html index.htm;

    # Logging
    access_log /var/log/nginx/mbogafasta-cms.access.log;
    error_log /var/log/nginx/mbogafasta-cms.error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    # PHP handling
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ ^/(config|includes|logs|vendor)/ {
        deny all;
    }

    # Allow access to uploads directory
    location /uploads/ {
        try_files $uri $uri/ =404;
    }

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Increase upload size limit
    client_max_body_size 10M;

    # Enable gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 10240;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;
    gzip_disable "MSIE [1-6]\.";

    # Redirect non-www to www
    if ($host = eazysafari.com) {
        return 301 http://www.eazysafari.com$request_uri;
    }
}
```

### Enable Site
```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/mbogafasta-cms.conf /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

## 5. SSL Configuration with Certbot

### Install Certbot and Nginx Plugin
```bash
# Add Certbot repository
sudo apt update
sudo apt install software-properties-common
sudo add-apt-repository universe
sudo add-apt-repository ppa:certbot/certbot
sudo apt update

# Install Certbot and Nginx plugin
sudo apt install certbot python3-certbot-nginx
```

### Configure Nginx for Certbot
```bash
# Make sure Nginx is running
sudo systemctl status nginx

# Test Nginx configuration
sudo nginx -t

# If test passes, reload Nginx
sudo systemctl reload nginx
```

### Obtain SSL Certificate
```bash
# Request SSL certificate for both www and non-www domains
sudo certbot --nginx -d www.eazysafari.com -d eazysafari.com

# During the process, Certbot will:
# 1. Ask for your email (for renewal notifications)
# 2. Ask to agree to terms of service
# 3. Ask if you want to redirect HTTP to HTTPS (recommended: choose 2)
```

### Verify SSL Installation
```bash
# Check certificate status
sudo certbot certificates

# Test automatic renewal
sudo certbot renew --dry-run
```

### Set Up Automatic Renewal
```bash
# Certbot automatically creates a systemd timer and service
# Verify the timer is active
sudo systemctl status certbot.timer

# Check when the next renewal will occur
sudo systemctl list-timers | grep certbot
```

### Manual Renewal (if needed)
```bash
# Renew all certificates
sudo certbot renew

# Renew specific certificate
sudo certbot renew --cert-name www.eazysafari.com
```

### Troubleshooting SSL Issues
```bash
# Check certificate expiration
sudo certbot certificates

# Check Nginx SSL configuration
sudo nginx -t

# Check SSL configuration
curl -vI https://www.eazysafari.com

# Check SSL Labs rating (from another machine)
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=www.eazysafari.com
```

### Force HTTPS in Nginx Configuration
After Certbot installation, your Nginx configuration will be updated. Verify it includes:
```nginx
server {
    listen 443 ssl;
    server_name www.eazysafari.com eazysafari.com;
    
    ssl_certificate /etc/letsencrypt/live/www.eazysafari.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/www.eazysafari.com/privkey.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    
    # HSTS (uncomment if you're sure)
    # add_header Strict-Transport-Security "max-age=31536000" always;
    
    # Rest of your configuration...
}
```

### SSL Security Best Practices
1. Keep Certbot updated:
```bash
sudo apt update
sudo apt upgrade certbot python3-certbot-nginx
```

2. Monitor certificate expiration:
```bash
# Add to crontab
sudo crontab -e
# Add this line to check certificates weekly
0 0 * * 0 /usr/bin/certbot certificates > /var/log/ssl-status.log
```

3. Set up email notifications for renewal:
```bash
# Edit Certbot renewal configuration
sudo nano /etc/letsencrypt/cli.ini
# Add:
email = your-email@example.com
```

## 6. Environment Configuration

Update `.env` file with production settings:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=mbogafastadb
DB_USER=msilu
DB_PASS=passw0rd

# Application Configuration
APP_NAME=Mbogafasta CMS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.eazysafari.com

# Session Configuration
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
```

## 7. Security Measures

### Set File Permissions
```bash
sudo chmod 600 /var/www/html/myprojects/mbogafasta-cms/.env
sudo chmod 600 /var/www/html/myprojects/mbogafasta-cms/config/config.php
```

### Install Fail2ban
```bash
sudo apt install fail2ban
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo systemctl restart fail2ban
```

## 8. Backup Configuration

### Create Backup Script
Create file: `/usr/local/bin/backup-mbogafasta.sh`
```bash
#!/bin/bash
BACKUP_DIR="/backups/mbogafasta"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u msilu -p'passw0rd' mbogafastadb > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/myprojects/mbogafasta-cms

# Set execute permission
sudo chmod +x /usr/local/bin/backup-mbogafasta.sh

# Add to crontab
sudo crontab -e
# Add this line:
0 2 * * * /usr/local/bin/backup-mbogafasta.sh
```

## 9. Monitoring

### Install Monitoring Tools
```bash
sudo apt install htop iotop
```

### Set Up Log Rotation
Create file: `/etc/logrotate.d/mbogafasta-cms`
```
/var/www/html/myprojects/mbogafasta-cms/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

## 10. Maintenance

### Regular Updates
```bash
# Update system weekly
sudo apt update
sudo apt upgrade

# Update Composer dependencies monthly
cd /var/www/html/myprojects/mbogafasta-cms
composer update
```

### Monitoring Logs
```bash
# Check Nginx logs
sudo tail -f /var/log/nginx/mbogafasta-cms.error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

## Troubleshooting

1. **Database Connection Issues**
   - Check MySQL service status: `sudo systemctl status mysql`
   - Verify database credentials in `.env`
   - Check MySQL error log: `sudo tail -f /var/log/mysql/error.log`

2. **Nginx Issues**
   - Check Nginx configuration: `sudo nginx -t`
   - Check Nginx error log: `sudo tail -f /var/log/nginx/error.log`
   - Verify file permissions

3. **PHP Issues**
   - Check PHP-FPM status: `sudo systemctl status php8.2-fpm`
   - Check PHP error log: `sudo tail -f /var/log/php8.2-fpm.log`
   - Verify PHP extensions are installed

4. **SSL Issues**
   - Check SSL certificate: `sudo certbot certificates`
   - Renew SSL certificate: `sudo certbot renew`

## Support Contact

For technical support, contact:
- Email: support@eazysafari.com
- Phone: [Your Support Phone Number]
- Hours: [Your Support Hours] 