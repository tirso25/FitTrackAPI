# Usa la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala las extensiones necesarias para MySQL y otras dependencias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql

# Copia el código de la aplicación
COPY . /var/www/html

# Da permisos al directorio de trabajo
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
