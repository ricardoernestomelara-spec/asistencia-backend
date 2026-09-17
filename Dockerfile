FROM php:8.2-apache

# Instalar extensión mysqli requerida
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar todo el contenido del repositorio
COPY . /var/www/html/

# Apuntar la raíz web de Apache directamente a la carpeta api
RUN sed -i 's|/var/www/html|/var/www/html/api|g' /etc/apache2/sites-available/000-default.conf

# Ajustar permisos para el usuario Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80