# Use official PHP image as base
FROM php:8.1-apache

# Install system dependencies (e.g., git, libpng, etc.)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . /var/www/html/

# Clear cache and install PHP dependencies using Composer
RUN composer clear-cache && composer install --verbose --no-interaction --optimize-autoloader --no-scripts

# Expose port 80 for Apache
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
