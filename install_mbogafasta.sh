#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Function to print status messages
print_status() {
    echo -e "${GREEN}[*] $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}[!] $1${NC}"
}

print_error() {
    echo -e "${RED}[x] $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root"
    exit 1
fi

# Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install required packages
print_status "Installing required packages..."
apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd \
    php8.2-mbstring php8.2-xml php8.2-zip composer software-properties-common git

# Configure firewall
print_status "Configuring firewall..."
ufw allow 'Nginx Full'
ufw allow 'OpenSSH'
ufw --force enable

# Create project directory
print_status "Creating project directory..."
mkdir -p /var/www/html/mbogafasta-cms
chown -R $SUDO_USER:$SUDO_USER /var/www/html/mbogafasta-cms

# Clone project
print_status "Cloning project..."
cd /var/www/html
if [ -d "mbogafasta-cms" ]; then
    print_warning "Project directory already exists. Skipping clone."
else
    git clone git@github.com:andreamsilu/mbogafasta-cms.git
fi

# Install Composer dependencies
print_status "Installing Composer dependencies..."
cd mbogafasta-cms
composer install

# Set permissions
print_status "Setting file permissions..."
chown -R www-data:www-data /var/www/html/mbogafasta-cms
chmod -R 755 /var/www/html/mbogafasta-cms
chmod -R 777 /var/www/html/mbogafasta-cms/uploads
chmod -R 777 /var/www/html/mbogafasta-cms/logs

# Create Nginx configuration
print_status "Creating Nginx configuration..."
cat > /etc/nginx/sites-available/mbogafasta-cms.conf << 'EOL'
server {
    listen 80;
    server_name www.eazysafari.com eazysafari.com;
    root /var/www/html/mbogafasta-cms;
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
EOL

# Enable Nginx site
print_status "Enabling Nginx site..."
ln -sf /etc/nginx/sites-available/mbogafasta-cms.conf /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx

# Install Certbot
print_status "Installing Certbot..."
add-apt-repository universe
add-apt-repository ppa:certbot/certbot
apt update
apt install -y certbot python3-certbot-nginx

# Create backup script
print_status "Creating backup script..."
cat > /usr/local/bin/backup-mbogafasta.sh << 'EOL'
#!/bin/bash
BACKUP_DIR="/backups/mbogafasta"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
mysqldump -u msilu -p'passw0rd' mbogafastadb > $BACKUP_DIR/db_$DATE.sql
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/mbogafasta-cms
EOL

chmod +x /usr/local/bin/backup-mbogafasta.sh

# Set up log rotation
print_status "Setting up log rotation..."
cat > /etc/logrotate.d/mbogafasta-cms << 'EOL'
/var/www/html/mbogafasta-cms/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
EOL

# Install monitoring tools
print_status "Installing monitoring tools..."
apt install -y htop iotop

# Install fail2ban
print_status "Installing fail2ban..."
apt install -y fail2ban
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
systemctl restart fail2ban

# Create .env file
print_status "Creating .env file..."
cat > /var/www/html/mbogafasta-cms/.env << 'EOL'
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

# File Upload Configuration
UPLOAD_MAX_SIZE=2048
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,pdf
EOL

# Set secure permissions for sensitive files
print_status "Setting secure permissions..."
chmod 600 /var/www/html/mbogafasta-cms/.env
chmod 600 /var/www/html/mbogafasta-cms/config/config.php

# Add weekly system update to crontab
print_status "Setting up weekly system updates..."
(crontab -l 2>/dev/null; echo "0 0 * * 0 apt update && apt upgrade -y") | crontab -

# Add weekly certificate check to crontab
print_status "Setting up weekly SSL certificate checks..."
(crontab -l 2>/dev/null; echo "0 0 * * 0 /usr/bin/certbot certificates > /var/log/ssl-status.log") | crontab -

print_status "Installation complete!"
print_warning "Please configure the database manually:"
echo "1. Create database: mbogafastadb"
echo "2. Create user: msilu"
echo "3. Set password: passw0rd"
echo "4. Import database schema"
echo ""
print_warning "After database setup, run:"
echo "sudo certbot --nginx -d www.eazysafari.com -d eazysafari.com" 