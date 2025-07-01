# --- Étape 1 : build JS avec Node/Esbuild ---
FROM node:18-alpine AS builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY public/assets/js ./public/assets/js
RUN npm run build

# --- Étape 2 : image finale avec Apache + PHP 8.3 + pdo_pgsql ---
FROM php:8.3-apache
# Indiquer à Apache d'utiliser /var/www/html/public comme racine
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Remplacer dans la config Apache le DocumentRoot par la nouvelle valeur
RUN sed -ri "s!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g" \
      /etc/apache2/sites-available/*.conf \
 && sed -ri "s!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g" \
      /etc/apache2/apache2.conf

# Installer les dépendances système, l’extension PostgreSQL et Composer
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libpq-dev \
      unzip \
      zip \
      git \
      curl \
 && docker-php-ext-install pdo_pgsql \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
 && apt-get purge -y --auto-remove curl \
 && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./

# Installer les dépendances PHP en production
RUN composer install --no-dev --optimize-autoloader

# Copier tout le code PHP
COPY . /var/www/html

# Copier les bundles JS depuis le builder
COPY --from=builder /app/public/assets/js /var/www/html/public/assets/js

WORKDIR /var/www/html

# Exposer le port HTTP (Apache écoute déjà sur 80)
EXPOSE 80

# Lancer Apache en avant-plan
CMD ["apache2-foreground"]
