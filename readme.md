ðŸš€ CoramTix Panel Installation Guide (with Nginx)

1. Update System

    sudo apt update && sudo apt upgrade -y

2. Install Dependencies

    sudo apt install -y curl unzip git redis-server mariadb-server nginx php8.2 php8.2-cli php8.2-mysql php8.2-gd php8.2-mbstring php8.2-bcmath php8.2-curl php8.2-xml php8.2-zip php8.2-fpm composer

3. Setup Database

    sudo mysql -u root -p

    CREATE DATABASE coramtix;
    CREATE USER 'coramuser'@'127.0.0.1' IDENTIFIED BY 'StrongPassword123!';
    GRANT ALL PRIVILEGES ON coramtix.* TO 'coramuser'@'127.0.0.1';
    FLUSH PRIVILEGES;
    EXIT;

4. Download Panel

    cd /var/www/
    git clone https://github.com/CoramTix/Panel.git coramtix
    cd coramtix
    composer install --no-dev --optimize-autoloader
    cp .env.example .env
    php artisan key:generate

5. Configure Environment

    nano /var/www/coramtix/.env

Fill in:

    APP_URL=https://yourdomain.com
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=coramtix
    DB_USERNAME=coramuser
    DB_PASSWORD=StrongPassword123!
    CACHE_DRIVER=redis
    SESSION_DRIVER=redis
    QUEUE_CONNECTION=redis

6. Run Migrations

    cd /var/www/coramtix
    php artisan migrate --seed --force

7. Nginx Configuration

    sudo tee /etc/nginx/sites-available/coramtix.conf > /dev/null <<'EOF'
    server_tokens off;

    server {
        listen 80;
        server_name yourdomain.com;
        return 301 https://$server_name$request_uri;
    }

    server {
        listen 443 ssl http2;
        server_name yourdomain.com;

        root /var/www/coramtix/public;
        index index.php;

        access_log /var/log/nginx/coramtix.access.log;
        error_log  /var/log/nginx/coramtix.error.log error;

        client_max_body_size 100m;
        client_body_timeout 120s;
        sendfile off;

        ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/run/php/php8.2-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS on;
            fastcgi_read_timeout 120s;
        }

        location ~ /\.ht {
            deny all;
        }
    }
    EOF

    sudo ln -s /etc/nginx/sites-available/coramtix.conf /etc/nginx/sites-enabled/coramtix.conf
    sudo nginx -t
    sudo systemctl restart nginx

8. Permissions

    chown -R www-data:www-data /var/www/coramtix
    chmod -R 755 /var/www/coramtix/storage /var/www/coramtix/bootstrap/cache

9. Queue Worker

    sudo tee /etc/systemd/system/coramtix-queue.service > /dev/null <<'EOF'
    [Unit]
    Description=CoramTix Queue Worker
    After=network.target

    [Service]
    User=www-data
    Group=www-data
    Restart=always
    ExecStart=/usr/bin/php /var/www/coramtix/artisan queue:work --sleep=3 --tries=3 --timeout=90
    WorkingDirectory=/var/www/coramtix

    [Install]
    WantedBy=multi-user.target
    EOF

    sudo systemctl daemon-reload
    sudo systemctl enable --now coramtix-queue

10. Create Admin User

    php artisan p:user:make

âœ… Installation Done!

Open https://yourdomain.com in your browser.
