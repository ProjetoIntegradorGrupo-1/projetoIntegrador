# =============================================================================
# PROJETO INTEGRADOR IFES - SISTEMA AXION (VISTORIA E CHECKLIST VEICULAR)
# Dockerfile Oficial da Aplicação (PHP 8.2 + Apache)
# =============================================================================

FROM php:8.2-apache

# 1. Instala dependências de sistema para extensões PHP (GD, ZIP, PDO MySQL)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# 2. Configura diretório de trabalho do Apache
WORKDIR /var/www/html

# 3. Copia todo o código-fonte da aplicação para o container
COPY . /var/www/html/

# 4. Ajusta permissões para escrita de uploads e sessões
RUN mkdir -p /var/www/html/backend/uploads \
    && chown -R www-data:www-data /var/www/html/backend/uploads \
    && chmod -R 775 /var/www/html/backend/uploads

# 5. Expõe a porta padrão do Apache
EXPOSE 80

# 6. Ponto de entrada padrão
CMD ["apache2-foreground"]
