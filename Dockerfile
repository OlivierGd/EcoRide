FROM node:18-alpine AS builder
WORKDIR /app

# Copier les fichiers de config Node.js
COPY package.json package-lock.json* ./
RUN npm ci

# Copier TOUS les assets JavaScript
COPY public/assets ./public/assets

# Build avec esbuild
RUN npm run build

# --- Image finale PHP ---
FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri "s!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g" \
      /etc/apache2/sites-available/*.conf \
 && sed -ri "s!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g" \
      /etc/apache2/apache2.conf

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libpq-dev \
      unzip \
      zip \
      git \
      curl \
      # Dépendances pour PECL et la compilation
      build-essential \
      libssl-dev \
      librdkafka-dev \
 && docker-php-ext-install pdo_pgsql \
 # Installer et activer redis via PECL
 && pecl install -o -f redis \
 && docker-php-ext-enable redis \
 # Installer Composer
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
 # Nettoyer les paquets de compilation
 && apt-get purge -y --auto-remove build-essential librdkafka-dev \
 && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copier tout le code PHP
COPY . /var/www/html

# Remplacer les JS par les versions buildées
COPY --from=builder /app/public/assets /var/www/html/public/assets

WORKDIR /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]