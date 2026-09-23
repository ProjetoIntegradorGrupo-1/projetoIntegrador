FROM php:8.2-apache
# Instala as extensões necessárias para ligar ao MySQL/PostgreSQL
RUN docker-php-ext-install pdo pdo_mysql
# Copia os ficheiros do seu projeto para a pasta do servidor
COPY . /var/www/html/
# Expõe a porta 80
EXPOSE 80