FROM php:8.1-apache

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP
RUN echo "upload_max_filesize = 5M" >> /usr/local/etc/php/conf.d/certificate.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/certificate.ini \
    && echo "max_file_uploads = 20" >> /usr/local/etc/php/conf.d/certificate.ini \
    && echo "memory_limit = 128M" >> /usr/local/etc/php/conf.d/certificate.ini

# Configure Apache Virtual Host
RUN echo "<VirtualHost *:80>" > /etc/apache2/sites-available/000-default.conf \
    && echo "    ServerAdmin webmaster@localhost" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    DocumentRoot /var/www/html" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    <Directory /var/www/html>" >> /etc/apache2/sites-available/000-default.conf \
    && echo "        Options Indexes FollowSymLinks" >> /etc/apache2/sites-available/000-default.conf \
    && echo "        AllowOverride All" >> /etc/apache2/sites-available/000-default.conf \
    && echo "        Require all granted" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    </Directory>" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    # Security Headers" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    Header always set X-Content-Type-Options nosniff" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    Header always set X-Frame-Options DENY" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    Header always set X-XSS-Protection \"1; mode=block\"" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    ErrorLog \${APACHE_LOG_DIR}/error.log" >> /etc/apache2/sites-available/000-default.conf \
    && echo "    CustomLog \${APACHE_LOG_DIR}/access.log combined" >> /etc/apache2/sites-available/000-default.conf \
    && echo "</VirtualHost>" >> /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Create upload directories with proper permissions
RUN mkdir -p /var/www/html/uploads/profiles /var/www/html/uploads/payments \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80