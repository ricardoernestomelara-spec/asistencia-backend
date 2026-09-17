FROM php:8.2-apache

# Instalar extensión mysqli para conexión a BD
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar el contenido de la carpeta 'api' directamente a la raíz de Apache
COPY api/ /var/www/html/

# Si tienes archivos en la raíz que también necesitas, descomenta la siguiente línea:
# COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80