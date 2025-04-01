# Utilizar una imagen base de PHP
FROM php:8.2-fpm

# Crear un usuario y grupo para el contenedor
RUN groupadd -g 1000 symfony && useradd -u 1000 -g symfony -m symfony

# Instalar dependencias para MySQL 8.0
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    default-mysql-client \
    libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-enable pdo_mysql

# Instalar Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer directorio de trabajo
WORKDIR /var/www/html
COPY . .

# Configurar permisos
RUN mkdir -p /var/www/html/assets && chmod -R 777 /var/www/html/assets
RUN mkdir -p /var/www/html/var && chmod -R 777 /var/www/html/var

# Usuario no root
USER symfony

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Exponer puerto
EXPOSE 10000

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]