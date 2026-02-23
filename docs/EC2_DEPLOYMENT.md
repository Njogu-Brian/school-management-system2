# EC2 Deployment Guide

## Prerequisites

- EC2 instance running Ubuntu
- SSH key: `erp-key.pem`
- Domain: erp.royalkingsschools.sc.ke (or your domain)

## Server Setup (One-time)

### 1. Install PHP, Nginx, MySQL, Composer, Node

```bash
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd unzip git mysql-server
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Create project directory

```bash
sudo mkdir -p /var/www/erp
sudo chown ubuntu:ubuntu /var/www/erp
cd /var/www/erp
```

### 3. Clone repository (first time)

```bash
git clone <your-repo-url> .
# Or if repo exists: git pull origin main
```

### 4. Upload storage from cPanel

After merging storage locally (see main README), upload to EC2:

```bash
# From your Windows machine (PowerShell):
scp -i erp-key.pem -r "d:\school-management-system2\school-management-system2\storage\app\*" ubuntu@13.245.211.78:/var/www/erp/storage/app/
```

### 5. Configure .env on EC2

```bash
ssh -i erp-key.pem ubuntu@13.245.211.78
cd /var/www/erp
cp .env.example .env
nano .env
```

Set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.royalkingsschools.sc.ke

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### 6. Configure Nginx

```nginx
server {
    listen 80;
    server_name erp.royalkingsschools.sc.ke;
    root /var/www/erp/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
}
```

---

## Deploying Updates

### Option A: SSH and run script

```bash
ssh -i erp-key.pem ubuntu@13.245.211.78
cd /var/www/erp
chmod +x scripts/deploy-ec2.sh
./scripts/deploy-ec2.sh
```

### Option B: One-liner from local machine

```bash
ssh -i erp-key.pem ubuntu@13.245.211.78 "cd /var/www/erp && git pull && composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link"
```

---

## S3 Setup (Optional)

To use S3 for file storage:

1. Create S3 bucket in AWS Console (e.g. `erp-royalkingsschools`)
2. Create IAM user with S3 access, get Access Key + Secret
3. In `.env`:

```
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=af-south-1
AWS_BUCKET=erp-royalkingsschools

# Switch to S3 for file storage
FILESYSTEM_PUBLIC_DISK=s3_public
FILESYSTEM_PRIVATE_DISK=s3_private
```

4. Install S3 package (run on EC2): `cd /var/www/erp && composer require league/flysystem-aws-s3-v3`
5. Migrate existing files: `php artisan storage:migrate-to-s3`

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 404 on bank statement PDF | Ensure storage/app/private/bank-statements has files; run `php artisan storage:link` |
| 500 error | Check `storage/logs/laravel.log` |
| Permission denied | `sudo chown -R www-data:www-data /var/www/erp/storage` |
| Composer memory limit | `php -d memory_limit=-1 /usr/local/bin/composer install` |
