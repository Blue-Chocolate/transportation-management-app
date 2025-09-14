# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    sqlite3 \
    libsqlite3-dev \
    supervisor \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd \
    && a2enmod rewrite

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create application directory and set permissions
RUN mkdir -p /var/www/html \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Create supervisor config for queue worker
RUN echo '[program:laravel-queue]\n\
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3\n\
directory=/var/www/html\n\
autostart=true\n\
autorestart=true\n\
user=www-data\n\
numprocs=1\n\
redirect_stderr=true\n\
stdout_logfile=/var/log/supervisor/laravel-queue.log' > /etc/supervisor/conf.d/laravel-queue.conf

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Wait for database to be ready\n\
until php artisan migrate:status > /dev/null 2>&1; do\n\
    echo "Waiting for database..."\n\
    sleep 2\n\
done\n\
\n\
# Run migrations\n\
php artisan migrate --force\n\
\n\
# Clear and cache config\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
# Start supervisor for queue workers\n\
service supervisor start\n\
\n\
# Start Apache\n\
apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]