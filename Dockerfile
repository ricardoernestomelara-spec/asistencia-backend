FROM php:8.2-apache

# Instalar extensión mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Cambiar la variable de entorno del DocumentRoot de Apache a la carpeta api
ENV APACHE_DOCUMENT_ROOT /var/www/html/api
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Copiar el repositorio completo
COPY . /var/www/html/

# Permisos para el usuario de Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80