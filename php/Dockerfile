FROM php:8.2-apache

# Installa mysqli e PDO con driver MySQL/MariaDB
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo pdo_mysql

# Abilita mod_rewrite
RUN a2enmod rewrite

# Copia i sorgenti PHP nell'immagine
COPY src/ /var/www/html/

# Copia lo script di init bcrypt
COPY php/init_bcrypt.php /usr/local/bin/init_bcrypt.php

# Copia l'entrypoint
COPY php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permessi web root
RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
