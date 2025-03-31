# Imagen base con PHP 8.2 y FPM
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \ 
    intl \
    gd \
    opcache \
    zip \
    mbstring \
    xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Crear usuario y directorio de trabajo
RUN groupadd -g 1000 symfony && \
    useradd -u 1000 -g symfony -m symfony && \
    mkdir -p /var/www/html && \
    chown symfony:symfony /var/www/html

WORKDIR /var/www/html

# Copiar archivos con permisos correctos
COPY --chown=symfony:symfony . .

# Cambiar a usuario Symfony
USER symfony

# Instalar dependencias con Composer
RUN composer install --no-dev --optimize-autoloader

# Limpiar caché de Symfony (por si hay configuraciones antiguas)
RUN php bin/console cache:clear --no-warmup

# Exponer el puerto de la aplicación
EXPOSE 10000

# Comando de arranque del contenedor
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
