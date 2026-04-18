FROM php:8.2-apache

# Installa mysqli e PDO con driver MySQL/MariaDB
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql

# Abilita mod_rewrite (utile in generale)
RUN a2enmod rewrite

# Permessi corretti per la web root
RUN chown -R www-data:www-data /var/www/html
