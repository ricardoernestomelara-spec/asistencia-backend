FROM php:8.2-apache

# Instalar extensión mysqli requerida para la BD
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite de Apache si usas rutas
RUN a2enmod rewrite

# Copiar TODO el proyecto (incluyendo la carpeta api) a la raíz del servidor web
COPY . /var/www/html/

# Dar permisos correctos a Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80