FROM php:8.2-apache

# Instalar extensión mysqli requerida
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar archivos a la raíz web
COPY . /var/www/html/

# Crear subcarpeta api y duplicar archivos para asegurar ambas rutas
RUN mkdir -p /var/www/html/api && cp /var/www/html/*.php /var/www/html/api/ 2>/dev/null || true

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80