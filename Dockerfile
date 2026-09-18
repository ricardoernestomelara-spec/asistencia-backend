FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones de MySQL para PDO
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar archivos al contenedor
COPY . /var/www/html/

# Exponer el puerto
EXPOSE 80