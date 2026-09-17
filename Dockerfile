FROM php:8.2-apache

# Instalación de extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

EXPOSE 80