FROM php:8.2-apache

# Instalar extensión mysqli requerida para la BD
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite de Apache para rutas y CORS
RUN a2enmod rewrite

# Copiar todo el contenido del proyecto a la raíz de Apache
COPY . /var/www/html/

# Dar permisos adecuados
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80