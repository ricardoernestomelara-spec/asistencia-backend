FROM php:8.2-apache

# Habilitar los módulos de Apache necesarios para CORS y redirecciones
RUN a2enmod headers rewrite

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar el código del proyecto al directorio web de Apache
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html