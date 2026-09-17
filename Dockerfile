FROM php:8.2-apache

# Instalar extensión mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar TODO el repositorio manteniendo las subcarpetas intactas
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80