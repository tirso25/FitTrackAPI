# Utilizar una imagen base de PHP
FROM php:8.2-fpm

# Actualizar índices de paquetes primero
RUN apt-get update && apt-get install -y \
    gnupg \
    gosu \
    curl \
    ca-certificates \
    git \
    unzip

# Añadir repositorio de MySQL oficial
RUN curl -sSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2022 | gpg --dearmor > /usr/share/keyrings/mysql.gpg
RUN echo "deb [signed-by=/usr/share/keyrings/mysql.gpg] http://repo.mysql.com/apt/debian/ $(lsb_release -sc) mysql-8.0" > /etc/apt/sources.list.d/mysql.list

# Instalar dependencias para MySQL 8.0
RUN apt-get update && apt-get install -y \
    mysql-client \
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