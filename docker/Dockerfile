# Usar la imagen oficial de PHP 8.2 con Apache
FROM php:8.2-apache

# Instalar las extensiones de PHP necesarias.
# pdo_mysql es para la conexión a la base de datos MySQL.
RUN docker-php-ext-install pdo_mysql

# Habilitar el módulo 'rewrite' de Apache para las URLs amigables (.htaccess)
RUN a2enmod rewrite

# Establecer el directorio de trabajo
WORKDIR /var/www/html
