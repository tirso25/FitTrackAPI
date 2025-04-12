# Utilizar una imagen base de PHP con Alpine
FROM php:8.2-fpm-alpine

# Instalar dependencias básicas incluyendo bash
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    mariadb-client \
    mariadb-connector-c-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-enable pdo_mysql

# Instalar Symfony CLI (versión alternativa compatible con Alpine)
RUN curl -sS https://get.symfony.com/cli/installer -o installer \
    && chmod +x installer \
    && ./installer --install-dir=/usr/local/bin

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Crear usuario y grupo para la aplicación
RUN addgroup -g 1000 symfony && adduser -u 1000 -G symfony -D symfony

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos
COPY --chown=symfony:symfony . .
COPY --chown=symfony:symfony /etc/secrets/.env .env
# Configurar permisos
RUN mkdir -p var public/assets \
    && chown -R symfony:symfony var public/assets \
    && chmod -R 777 var public/assets

# Cambiar a usuario no root
USER symfony

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Exponer puerto
EXPOSE 10000

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]