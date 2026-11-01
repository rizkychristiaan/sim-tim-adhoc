FROM php:8.2-apache

# Install ekstensi PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Copy seluruh file aplikasi ke direktori web Apache
COPY . /var/www/html/

# Pastikan folder uploads memiliki izin tulis
RUN chown -R www-data:www-data /var/www/html/app/uploads \
    && chmod -R 775 /var/www/html/app/uploads

EXPOSE 80
