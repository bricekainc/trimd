# Use official PHP image with Apache
FROM php:8.1-apache

# Install system dependencies required by the app
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zlib1g-dev \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set environment variable to allow Composer to run as root (in non-interactive mode)
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working directory
WORKDIR /var/www/html

# Copy all the project files into the container
COPY . /var/www/html/

# Clear Composer cache (in case of previous failed installs)
RUN composer clear-cache

# Install dependencies using Composer (ensure verbose logging for debugging)
RUN composer install --no-interaction --no-scripts --optimize-autoloader --verbose --prefer-dist

# Expose port 80 for Apache web server
EXPOSE 80

# Enable Apache rewrite module
RUN a2enmod rewrite

# Start Apache in the foreground
CMD ["apache2-foreground"]
