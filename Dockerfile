# Utilizar una imagen base de PHP
FROM php:8.2-fpm

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar dependencias del proyecto
WORKDIR /var/www/html
COPY . .

# Instalar las dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto 10000
EXPOSE 10000

# Comando para ejecutar el servidor PHP
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
