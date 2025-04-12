# Usar una imagen base de PHP
FROM php:8.1-fpm

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git

# Instalar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar el contenido del proyecto
COPY . .

# Copiar el archivo .env al contenedor
COPY .env .env

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Exponer puerto
EXPOSE 9000
