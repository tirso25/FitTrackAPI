# Utilizar una imagen base de PHP
FROM php:8.2-fpm

# Crear un usuario y grupo para el contenedor
RUN groupadd -g 1000 symfony && useradd -u 1000 -g symfony -m symfony

# Instalar las dependencias necesarias (Git y unzip)
RUN apt-get update && apt-get install -y git unzip

# Instalar Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo y copiar los archivos
WORKDIR /var/www/html
COPY . .

# Cambiar a un usuario no root para evitar problemas de permisos
USER symfony

# Instalar las dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto 10000
EXPOSE 10000

# Comando para ejecutar el servidor PHP
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
