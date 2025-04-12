
FROM php:8.2-fpm-alpine as builder

WORKDIR /var/www/html

# Instalar dependencias del sistema
RUN apk update && \
    apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    openssl \
    mariadb-client \
    mariadb-connector-c-dev && \
    docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-enable pdo_mysql

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar variables de entorno para Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Copiar solo los archivos necesarios para instalar dependencias
COPY composer.json composer.lock symfony.lock ./

# Instalar dependencias de producción (sin dev)
RUN composer install --no-dev --no-interaction --optimize-autoloader --ignore-platform-reqs --no-scripts

# Configurar permisos temporales
RUN mkdir -p var/cache var/log && \
    chmod -R 777 var

FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Crear usuario y grupo para la aplicación
RUN addgroup -g 1000 symfony && \
    adduser -u 1000 -G symfony -D symfony

# Instalar dependencias del sistema necesarias para runtime
RUN apk add --no-cache openssl

# Copiar dependencias instaladas desde la fase builder
COPY --from=builder /var/www/html/vendor vendor/
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer

# Copiar el código de la aplicación
COPY --chown=symfony:symfony . .

# Configurar permisos
RUN mkdir -p var/cache var/log && \
    chown -R symfony:symfony var && \
    chmod -R 777 var

# Generar .env dinámicamente
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "DATABASE_URL=${DATABASE_URL}" >> .env && \
    echo "JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem" >> .env && \
    echo "JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem" >> .env && \
    echo "JWT_PASSPHRASE=${JWT_PASSPHRASE}" >> .env && \
    echo "MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0" >> .env

# Generar claves JWT si no existen
RUN mkdir -p config/jwt && \
    if [ ! -f config/jwt/private.pem ]; then \
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:${JWT_PASSPHRASE} && \
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:${JWT_PASSPHRASE}; \
    fi && \
    chown -R symfony:symfony config/jwt

USER symfony

# Puerto expuesto
EXPOSE 10000

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]