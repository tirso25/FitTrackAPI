##############################################
# 1. IMAGEN BASE
##############################################

# Usamos la imagen oficial de PHP 8.2 con Alpine Linux
# Alpine es una distribución ligera de Linux (~5MB)
# php:8.2-fpm-alpine incluye PHP-FPM (FastCGI Process Manager)
FROM php:8.2-fpm-alpine

##############################################
# 2. INSTALACIÓN DE DEPENDENCIAS DEL SISTEMA
##############################################

# Actualizamos el sistema e instalamos paquetes necesarios
RUN apk update && apk add --no-cache \
    # Herramientas básicas
    # Shell avanzado
    bash \
    # Control de versiones
    git \
    # Para descomprimir archivos          
    unzip \
    # Para hacer peticiones HTTP      
    curl \
    # Cliente MySQL/MariaDB
    # Herramientas de línea de comandos
    mariadb-client \
    # Conector C para MySQL (IMPORTANTE para PDO)
    mariadb-connector-c \
    # Instalamos extensiones PHP necesarias
    # Extensión PDO para MySQL
    && docker-php-ext-install pdo pdo_mysql \
    # Habilitamos la extensión
    && docker-php-ext-enable pdo_mysql

##############################################
# 3. INSTALACIÓN DE HERRAMIENTAS DE DESARROLLO
##############################################

# Instalamos Symfony CLI (interfaz de línea de comandos)
RUN curl -sS https://get.symfony.com/cli/installer -o installer \
    # Hacemos el instalador ejecutable
    && chmod +x installer \
    # Instalamos en /usr/local/bin
    && ./installer --install-dir=/usr/local/bin

# Instalamos Composer (gestor de dependencias PHP)
RUN curl -sS https://getcomposer.org/installer | php -- \
    # Donde se instalará
    --install-dir=/usr/local/bin \
    # Nombre del ejecutable
    --filename=composer

##############################################
# 4. CONFIGURACIÓN DE SEGURIDAD
##############################################

# Creamos un usuario y grupo específico para la aplicación
# (Buenas prácticas: no usar root)
# Creamos grupo 'symfony' con ID 1000
RUN addgroup -g 1000 symfony && \
    # Creamos usuario 'symfony'
    adduser -u 1000 -G symfony -D symfony

# Establecemos el directorio de trabajo
WORKDIR /var/www/html

##############################################
# 5. COPIA DE ARCHIVOS DE LA APLICACIÓN
##############################################

# Copiamos todos los archivos del proyecto al contenedor
# y asignamos la propiedad al usuario symfony
COPY --chown=symfony:symfony . .

##############################################
# 6. CONFIGURACIÓN DE PERMISOS
##############################################

# Creamos directorios necesarios y asignamos permisos
# Creamos directorios
RUN mkdir -p var public/assets \
    # Cambiamos propietario 
    && chown -R symfony:symfony var public/assets \
    # Permisos completos (en desarrollo)
    && chmod -R 777 var public/assets

##############################################
# 7. CAMBIO A USUARIO NO PRIVILEGIADO
##############################################

# Todas las operaciones siguientes se ejecutarán como el usuario symfony
USER symfony

##############################################
# 8. INSTALACIÓN DE DEPENDENCIAS DE COMPOSER
##############################################

# Instalamos solo dependencias de producción (--no-dev)
RUN composer install \
    # Sin dependencias de desarrollo
    --no-dev \
    # Autoloader optimizado para producción
    --optimize-autoloader \
    # No preguntar durante la instalación
    --no-interaction

##############################################
# 9. CONFIGURACIÓN DEL PUERTO Y ARRANQUE
##############################################

# Exponemos el puerto 10000 para acceder a la aplicación
EXPOSE 10000

# Comando que se ejecutará al iniciar el contenedor:
# - Servidor PHP embebido escuchando en 0.0.0.0:10000
# - Sirviendo archivos desde el directorio public/
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]