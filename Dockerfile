# Utilizar una imagen base de PHP con Alpine (más ligera y con menos problemas de dependencias)
FROM php:8.2-fpm-alpine

# Instalar dependencias básicas
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    mariadb-client \
    mariadb-connector-c-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-enable pdo_mysql

# Instalar Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Crear usuario y grupo para la aplicación
RUN addgroup -g 1000 symfony && adduser -u 1000 -G symfony -D symfony

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos (excepto lo excluido en .dockerignore)
COPY --chown=symfony:symfony . .

# Configurar permisos
RUN mkdir -p var public/assets \
    && chown -R symfony:symfony var public/assets \
    && chmod -R 777 var public/assets

# Cambiar a usuario no root
USER symfony

# Instalar dependencias de Composer (sin dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Exponer puerto
EXPOSE 10000

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]