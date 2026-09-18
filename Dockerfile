FROM php:8.2-apache

# Instalar dependencias y extensiones de MySQL para PDO
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli

# HABILITAR MODULOS DE APACHE PARA CORS Y REWRITE
RUN a2enmod rewrite headers

# Configurar Apache para permitir CORS globalmente
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    Header set Access-Control-Allow-Origin "*"\n\
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS, PUT, DELETE"\n\
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"\n\
</Directory>' > /etc/apache2/conf-available/cors.conf \
    && a2enconf cors

# Copiar archivos del proyecto al contenedor
COPY . /var/www/html/

EXPOSE 80