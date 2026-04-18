FROM php:8.2-apache

# Installa estensione mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Abilita mod_rewrite (utile in generale)
RUN a2enmod rewrite

# Permessi corretti per la web root
RUN chown -R www-data:www-data /var/www/html
