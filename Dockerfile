FROM php:8.2-apache

# Instalar extensión mysqli requerida
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Apuntar el DocumentRoot de Apache directamente a la carpeta /api
ENV APACHE_DOCUMENT_ROOT /var/www/html/api
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar todo el contenido del repositorio
COPY . /var/www/html/

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80