# Use PHP 8.0 as the base image
FROM php:8.0-apache

# Set the working directory inside the container
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer (dependency manager)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy your code into the container
COPY . /var/www/html/

# Install PHP dependencies using Composer
RUN composer install

# Expose port 80 for the web server
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
