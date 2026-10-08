# Gunakan image dasar PHP 8.2 dengan Apache
FROM php:8.2-apache

# Install dependensi sistem & ekstensi PHP yang dibutuhkan
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite Apache (jika kamu menggunakan .htaccess)
RUN a2enmod rewrite

# Set folder kerja
WORKDIR /var/www/html

# Copy semua file proyek ke dalam container
COPY . /var/www/html/

# Set permission yang tepat
RUN chown -R www-data:www-data /var/www/html

# Ekspos port 80 (port default Apache)
EXPOSE 80