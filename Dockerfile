# Use an official PHP image with Apache for arm64
FROM php:8.2.27-apache-bookworm

# Install Apache and required PHP extensions
RUN apt-get update && apt-get install -y \
    apache2 \
    libonig-dev libzip-dev unzip curl \
    libicu-dev nano && \
    docker-php-ext-install pdo pdo_mysql mysqli zip && \
    docker-php-ext-install intl

# Enable Apache mod_rewrite for CI4
RUN a2enmod rewrite

# Set working directory inside the container
WORKDIR /var/www/html

# Copy the entire CodeIgniter project to the container
COPY . /var/www/html

# Create writable directories if they don't exist
RUN mkdir -p /var/www/html/public/writable/cache /var/www/html/public/writable/logs /var/www/html/public/writable/session

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/public/writable

# Change Apache's DocumentRoot to the public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose the Apache port
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
