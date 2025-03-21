# Utilizar una imagen base de PHP
FROM php:8.2-fpm

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony*/bin/symfony /usr/local/bin/symfony


# Instalar dependencias necesarias (Git y unzip)
RUN apt-get update && apt-get install -y git unzip

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar las dependencias del proyecto
WORKDIR /var/www/html
COPY . .

# Instalar las dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto 10000
EXPOSE 10000

# Comando para ejecutar el servidor PHP
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
