# SplashSupportAI Deployment Guide

## 📋 Pre-Deployment Checklist

- [ ] Server meets minimum requirements (PHP 7.0+, MySQL 5.7+)
- [ ] Domain name configured
- [ ] SSL certificate obtained
- [ ] Database backup plan in place
- [ ] Monitoring tools configured

---

## 🚀 Production Deployment

### 1. Server Requirements

**Recommended Specifications:**
- **CPU**: 2+ cores
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 20GB SSD minimum
- **OS**: Ubuntu 20.04 LTS or CentOS 8+

### 2. Install LAMP Stack

#### Ubuntu/Debian

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y

# Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Install PHP 7.4
sudo apt install php7.4 php7.4-cli php7.4-fpm php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl php7.4-json php7.4-zip -y

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

#### CentOS/RHEL

```bash
# Update system
sudo yum update -y

# Install Apache
sudo yum install httpd -y
sudo systemctl start httpd
sudo systemctl enable httpd

# Install MySQL
sudo yum install mysql-server -y
sudo systemctl start mysqld
sudo systemctl enable mysqld
sudo mysql_secure_installation

# Install PHP
sudo yum install php php-cli php-fpm php-mysqlnd php-mbstring php-xml php-json -y
sudo systemctl restart httpd
```

### 3. Configure Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE splash_support_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'splash_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON splash_support_ai.* TO 'splash_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u splash_user -p splash_support_ai < /path/to/database.sql
```

### 4. Deploy Application

```bash
# Navigate to web root
cd /var/www/

# Clone or upload application
# Option 1: Git
sudo git clone https://github.com/yourusername/SplashSupportAI.git
cd SplashSupportAI

# Option 2: Upload via SCP/FTP
# Upload files to /var/www/SplashSupportAI/

# Set permissions
sudo chown -R www-data:www-data /var/www/SplashSupportAI
sudo chmod -R 755 /var/www/SplashSupportAI
sudo chmod -R 775 /var/www/SplashSupportAI/storage
sudo chmod -R 775 /var/www/SplashSupportAI/storage/uploads
sudo chmod -R 775 /var/www/SplashSupportAI/storage/logs

# Configure environment
sudo cp .env.example .env
sudo nano .env
```

### 5. Configure .env for Production

```env
# Database
DB_HOST=localhost
DB_NAME=splash_support_ai
DB_USER=splash_user
DB_PASS=STRONG_PASSWORD_HERE

# Application
APP_ENV=production
APP_DEBUG=false
APP_NAME=SplashSupportAI
BASE_URL=https://yourdomain.com

# Security
SESSION_LIFETIME=7200
CSRF_TOKEN_NAME=csrf_token

# File Uploads
MAX_UPLOAD_SIZE=10485760

# AI Provider (Replace with real keys)
AI_PROVIDER=openai
AI_PROVIDER_KEY=sk-your-real-api-key-here
AI_MODEL=gpt-4
AI_MAX_TOKENS=1000

# Timezone
DEFAULT_TIMEZONE=UTC
```

### 6. Configure Apache Virtual Host

```bash
sudo nano /etc/apache2/sites-available/splashsupportai.conf
```

Add:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/SplashSupportAI/public

    <Directory /var/www/SplashSupportAI/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/splash_error.log
    CustomLog ${APACHE_LOG_DIR}/splash_access.log combined

    # Redirect to HTTPS
    RewriteEngine on
    RewriteCond %{SERVER_NAME} =yourdomain.com [OR]
    RewriteCond %{SERVER_NAME} =www.yourdomain.com
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>
```

Enable site:

```bash
sudo a2ensite splashsupportai.conf
sudo systemctl reload apache2
```

### 7. Install SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (certbot adds this automatically)
sudo systemctl status certbot.timer
```

The SSL VirtualHost is created automatically by Certbot.

### 8. Configure CRON Jobs

```bash
# Edit crontab
sudo crontab -e
```

Add:

```cron
# SLA checker - every 5 minutes
*/5 * * * * php /var/www/SplashSupportAI/cron/check_sla.php >> /var/log/splash_sla.log 2>&1

# Reset monthly usage - 1st of each month at midnight
0 0 1 * * php /var/www/SplashSupportAI/cron/reset_usage.php >> /var/log/splash_usage.log 2>&1

# Database backup - daily at 2 AM
0 2 * * * mysqldump -u splash_user -pPASSWORD splash_support_ai | gzip > /var/backups/splash_$(date +\%Y\%m\%d).sql.gz
```

### 9. Security Hardening

```bash
# Disable directory listing
sudo nano /etc/apache2/apache2.conf
# Set: Options -Indexes

# Hide PHP version
sudo nano /etc/php/7.4/apache2/php.ini
# Set: expose_php = Off

# Restart Apache
sudo systemctl restart apache2

# Configure firewall
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### 10. Verify Installation

```bash
# Test database connection
php /var/www/SplashSupportAI/tests/test_db_connection.php

# Check Apache logs
sudo tail -f /var/log/apache2/splash_error.log
```

Visit https://yourdomain.com and verify:
- [ ] Login page loads
- [ ] Can login with admin credentials
- [ ] Dashboard displays
- [ ] Can create ticket
- [ ] Chat widget loads

---

## 📊 Monitoring & Maintenance

### Log Files

```bash
# Application logs
tail -f /var/www/SplashSupportAI/storage/logs/error_log.txt

# Apache logs
sudo tail -f /var/log/apache2/splash_error.log
sudo tail -f /var/log/apache2/splash_access.log

# MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### Database Backup

```bash
# Manual backup
mysqldump -u splash_user -p splash_support_ai | gzip > splash_backup_$(date +%Y%m%d).sql.gz

# Restore from backup
gunzip < splash_backup_20250120.sql.gz | mysql -u splash_user -p splash_support_ai
```

### Performance Optimization

**Enable PHP OPCache:**

```bash
sudo nano /etc/php/7.4/apache2/php.ini
```

Add/enable:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

**MySQL Optimization:**

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add:

```ini
[mysqld]
innodb_buffer_pool_size=1G
innodb_log_file_size=256M
max_connections=200
query_cache_size=0
query_cache_type=0
```

Restart:

```bash
sudo systemctl restart mysql
sudo systemctl restart apache2
```

---

## 🔄 Updates & Upgrades

### Updating Application

```bash
# Backup database first
mysqldump -u splash_user -p splash_support_ai > backup_before_update.sql

# Pull latest code
cd /var/www/SplashSupportAI
git pull origin main

# Run any database migrations (if applicable)
# mysql -u splash_user -p splash_support_ai < migrations/update_v2.sql

# Clear cache (if implementing caching)
# rm -rf storage/cache/*

# Set permissions
sudo chown -R www-data:www-data /var/www/SplashSupportAI
```

### PHP Updates

```bash
# Update PHP
sudo apt update
sudo apt upgrade php7.4

# Restart Apache
sudo systemctl restart apache2
```

---

## 🆘 Troubleshooting

### Common Issues

**1. 500 Internal Server Error**

```bash
# Check Apache error log
sudo tail -50 /var/log/apache2/splash_error.log

# Check PHP errors
sudo tail -50 /var/www/SplashSupportAI/storage/logs/error_log.txt

# Verify permissions
ls -la /var/www/SplashSupportAI/storage
```

**2. Database Connection Failed**

```bash
# Test connection
mysql -u splash_user -p -h localhost splash_support_ai

# Verify .env settings
cat /var/www/SplashSupportAI/.env | grep DB_
```

**3. Chat Widget Not Loading**

```bash
# Check CORS headers
curl -I https://yourdomain.com/chat-widget.js

# Verify file exists
ls -la /var/www/SplashSupportAI/public/chat-widget.js
```

**4. File Upload Fails**

```bash
# Check permissions
sudo chmod -R 775 /var/www/SplashSupportAI/storage/uploads

# Check PHP upload settings
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

---

## 🔐 Security Best Practices

1. **Change Default Credentials**
   - Login and change admin password immediately
   - Update database user password periodically

2. **Enable Security Headers**

Add to Apache config:

```apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
```

3. **Regular Updates**
   - Keep OS updated
   - Update PHP and MySQL
   - Apply application patches

4. **Monitor Logs**
   - Set up log monitoring (Logwatch, GoAccess)
   - Configure alerts for errors

5. **Database Security**
   - Use strong passwords
   - Limit database user permissions
   - Enable MySQL audit logging

---

## 📞 Support

If you encounter issues:

1. Check logs first
2. Review this deployment guide
3. Search GitHub issues
4. Contact support: support@splashsupportai.com

---

**Deployment Guide Version 1.0** | Last Updated: 2025-01-22
