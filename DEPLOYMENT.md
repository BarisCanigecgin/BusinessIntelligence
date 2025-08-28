# 🚀 Production Deployment - knowhowpilot.com

## 📋 Deployment Checklist

### ✅ Pre-Deployment
- [x] Domain: https://knowhowpilot.com
- [x] Database: knowhowpilot_BI
- [x] User: knowhowpilot_admin
- [x] Production config ready
- [x] HTTPS force enabled
- [x] Security headers configured

### 🔧 Server Requirements
- PHP 8.0+
- MySQL 5.7+
- Apache with mod_rewrite
- SSL Certificate (HTTPS)
- Minimum 512MB RAM
- 1GB disk space

## 📤 Upload Instructions

### 1. File Upload via FTP/SFTP
```bash
# Upload all files to your web root directory
# Example: /public_html/ or /htdocs/

BusinessIntelligence/
├── dashboard.php
├── reports.php
├── index.php
├── api/
├── .htaccess
├── .env
├── src/
├── database/
├── scripts/
├── logs/
├── cache/
└── vendor/
```

### 2. Set File Permissions
```bash
chmod 755 dashboard.php reports.php index.php
chmod 755 api/
chmod 777 logs/
chmod 777 cache/
chmod 644 .htaccess
chmod 600 .env
```

### 3. Database Setup
```bash
# SSH into your server and run:
php scripts/install_database.php
```

## 🔒 Security Configuration

### 1. Environment File
- Copy `.env.production` to `.env`
- Update production secrets
- Ensure `.env` is not publicly accessible

### 2. Apache Security
```apache
# Add to .htaccess or virtual host
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

### 3. SSL/HTTPS
- Ensure SSL certificate is installed
- Force HTTPS redirects (already in .htaccess)
- Update security headers

## 🌐 Domain Configuration

### DNS Settings
```
A Record: knowhowpilot.com → Your Server IP
CNAME: www.knowhowpilot.com → knowhowpilot.com
```

### Apache Virtual Host
```apache
<VirtualHost *:443>
    ServerName knowhowpilot.com
    ServerAlias www.knowhowpilot.com
    DocumentRoot /path/to/BusinessIntelligence
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /path/to/BusinessIntelligence>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/knowhowpilot_error.log
    CustomLog ${APACHE_LOG_DIR}/knowhowpilot_access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName knowhowpilot.com
    ServerAlias www.knowhowpilot.com
    Redirect permanent / https://knowhowpilot.com/
</VirtualHost>
```

## 🧪 Post-Deployment Testing

### 1. Basic Functionality
- [ ] https://knowhowpilot.com/ (redirects to dashboard)
- [ ] https://knowhowpilot.com/dashboard.php
- [ ] https://knowhowpilot.com/reports.php
- [ ] https://knowhowpilot.com/api/v1/health

### 2. API Endpoints
```bash
# Test API health
curl https://knowhowpilot.com/api/v1/health

# Test sales data
curl https://knowhowpilot.com/api/v1/sales/overview

# Test customer data
curl https://knowhowpilot.com/api/v1/customers/rfm-analysis
```

### 3. Database Connection
```bash
# SSH into server and test
php simple_test.php
```

## 📊 Monitoring & Maintenance

### Log Files
- Application logs: `logs/app.log`
- Apache error logs: `/var/log/apache2/error.log`
- Apache access logs: `/var/log/apache2/access.log`

### Performance Monitoring
- Monitor API response times
- Check database query performance
- Monitor disk space usage
- Track memory usage

### Backup Strategy
```bash
# Database backup
mysqldump -u knowhowpilot_admin -p knowhowpilot_BI > backup_$(date +%Y%m%d).sql

# File backup
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/BusinessIntelligence
```

## 🔧 Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check Apache error logs
- Verify file permissions
- Check .htaccess syntax

**Database Connection Failed**
- Verify database credentials
- Check MySQL service status
- Test connection with simple_test.php

**API Not Working**
- Check mod_rewrite is enabled
- Verify .htaccess rules
- Check CORS headers

**HTTPS Issues**
- Verify SSL certificate
- Check certificate chain
- Test with SSL checker tools

## 📞 Support Contacts

### Server Issues
- Hosting provider support
- Server administrator

### Application Issues
- Check logs/app.log
- Review error messages
- Test API endpoints

## 🎯 Success Metrics

After successful deployment:
- ✅ Dashboard loads in < 3 seconds
- ✅ API responses in < 1 second
- ✅ All HTTPS redirects working
- ✅ Database queries optimized
- ✅ Security headers present
- ✅ Mobile responsive design

---

**🎉 Your Luxury Watch BI system is now live at https://knowhowpilot.com!**
