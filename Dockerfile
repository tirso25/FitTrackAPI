# ----------------------------------
# 1. Fase de construcción (Build)
# ----------------------------------
FROM php:8.2-fpm-alpine as builder

WORKDIR /var/www/html

# Instalar dependencias del sistema
RUN apk update && \
    apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    mariadb-client \
    mariadb-connector-c-dev && \
    docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-enable pdo_mysql

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar solo los archivos necesarios para instalar dependencias
COPY composer.json composer.lock symfony.lock ./

# Instalar dependencias de producción (sin dev)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# ----------------------------------
# 2. Fase final (Runtime)
# ----------------------------------
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Crear usuario y grupo para la aplicación
RUN addgroup -g 1000 symfony && \
    adduser -u 1000 -G symfony -D symfony

# Copiar dependencias instaladas desde la fase builder
COPY --from=builder /var/www/html/vendor vendor/
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer

# Copiar el código de la aplicación
COPY --chown=symfony:symfony bin bin/
COPY --chown=symfony:symfony config config/
COPY --chown=symfony:symfony public public/
COPY --chown=symfony:symfony src src/
COPY --chown=symfony:symfony templates templates/
COPY --chown=symfony:symfony translations translations/

RUN echo "APP_ENV=prod" > .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "DATABASE_URL=${DATABASE_URL}" >> .env && \
    echo "JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem" >> .env && \
    echo "JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem" >> .env && \
    echo "JWT_PASSPHRASE=${JWT_PASSPHRASE}" >> .env && \
    echo "MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0" >> .env

# Configurar permisos
RUN mkdir -p var/cache var/log && \
    chown -R symfony:symfony var && \
    chmod -R 777 var

# Si usas JWT, copia las claves desde Secret Files (Render)
# COPY --chown=symfony:symfony /etc/secrets/private.pem config/jwt/private.pem
# COPY --chown=symfony:symfony /etc/secrets/public.pem config/jwt/public.pem

# Generar .env dinámicamente si prefieres usar variables de entorno
RUN echo "APP_ENV=${APP_ENV:-prod}" > .env && \
    echo "APP_DEBUG=${APP_DEBUG:-false}" >> .env && \
    echo "DATABASE_URL=${DATABASE_URL}" >> .env && \
    echo "JWT_PASSPHRASE=${JWT_PASSPHRASE}" >> .env

USER symfony

# Puerto expuesto (ajusta según tu configuración en Render)
EXPOSE 10000

# Comando de inicio (para Render)
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]