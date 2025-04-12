# Utilizar una imagen base de PHP con Alpine (como en tu versión original)
FROM php:8.2-fpm-alpine

# Instalar dependencias básicas
RUN apk add --no-cache \
    bash \
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

# Copiar solo los archivos necesarios (excluyendo .env)
COPY --chown=symfony:symfony bin bin/
COPY --chown=symfony:symfony config config/
COPY --chown=symfony:symfony public public/
COPY --chown=symfony:symfony src src/
COPY --chown=symfony:symfony templates templates/
COPY --chown=symfony:symfony translations translations/
COPY --chown=symfony:symfony vendor vendor/
COPY --chown=symfony:symfony composer.json composer.lock symfony.lock ./

# Configurar permisos
RUN mkdir -p var public/assets \
    && chown -R symfony:symfony var public/assets \
    && chmod -R 777 var public/assets

# Cambiar a usuario no root
USER symfony

# Generar el archivo .env dinámicamente desde variables de entorno
RUN echo "APP_ENV=${APP_ENV:-prod}" > .env && \
    echo "APP_DEBUG=${APP_DEBUG:-false}" >> .env && \
    echo "DATABASE_URL=${DATABASE_URL}" >> .env && \
    echo "MESSENGER_TRANSPORT_DSN=${MESSENGER_TRANSPORT_DSN:-doctrine://default?auto_setup=0}" >> .env && \
    echo "MAILER_DSN=${MAILER_DSN:-null://null}" >> .env && \
    echo "JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem" >> .env && \
    echo "JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem" >> .env && \
    echo "JWT_PASSPHRASE=${JWT_PASSPHRASE}" >> .env

# Alternativa si prefieres usar el archivo .env de Render Secret Files:
# COPY --from=build /etc/secrets/.env .env

# Instalar dependencias de Composer (sin dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Si necesitas las claves JWT, asegúrate de copiarlas:
# COPY --chown=symfony:symfony config/jwt/private.pem config/jwt/private.pem
# COPY --chown=symfony:symfony config/jwt/public.pem config/jwt/public.pem

# Exponer puerto
EXPOSE 10000

# Comando de inicio
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]