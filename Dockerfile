FROM php:8.2-apache

# Instala as extensões da base de dados
RUN docker-php-ext-install pdo pdo_mysql

# Copia o projeto para o servidor
COPY . /var/www/html/

# Define a pasta Frontend como a raiz oficial do servidor Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/Frontend
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80