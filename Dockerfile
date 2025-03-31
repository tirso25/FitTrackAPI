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
    mysqli \
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

# Crear usuario y directorio
RUN groupadd -g 1000 symfony && \
    useradd -u 1000 -g symfony -m symfony && \
    mkdir -p /var/www/html && \
    chown symfony:symfony /var/www/html

WORKDIR /var/www/html

# Copiar archivos como root temporalmente
COPY --chown=symfony:symfony . .

USER symfony

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]