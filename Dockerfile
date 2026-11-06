# ==========================================
# 1. BUILDER (Compilation & Dépendances)
# ==========================================
FROM php:8.4-fpm AS builder

# Paquets requis uniquement pour la compilation des extensions et l'installation de Composer
RUN apt-get update && apt-get install -y \
    gnupg2 \
    curl \
    libicu-dev \
    unixodbc-dev \
    unzip \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    && rm -rf /var/lib/apt/lists/*

# Dépôt Microsoft et driver ODBC
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-install intl opcache \
    && pecl install redis sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable redis sqlsrv pdo_sqlsrv \
    && rm -rf /tmp/pear

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Installation de Composer et téléchargement des dépendances
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

COPY . .
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction


# ==========================================
# 2. BASE D'EXÉCUTION (Runtime minimal)
# ==========================================
FROM php:8.4-fpm AS base_runtime

# Utilisation des dépendances sans figer les numéros de révision de la bibliothèque Debian
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo \
    libfreetype6 \
    libwebp-dev \
    unixodbc \
    && rm -rf /var/lib/apt/lists/*

# Driver SQL Server (msodbcsql18)
COPY --from=builder /usr/share/keyrings/microsoft-prod.gpg /usr/share/keyrings/microsoft-prod.gpg
COPY --from=builder /etc/apt/sources.list.d/mssql-release.list /etc/apt/sources.list.d/mssql-release.list
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql18 && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

WORKDIR /var/www/html


# ==========================================
# 3. DÉVELOPPEMENT
# ==========================================
FROM builder AS dev
ENV APP_ENV=dev
CMD ["sh", "-c", "composer install --no-interaction --prefer-dist && exec php-fpm"]


# ==========================================
# 4. PRODUCTION (Sécurisée)
# ==========================================
FROM base_runtime AS prod
ENV APP_ENV=prod

# Configuration PHP / OPcache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN echo "opcache.enable=1\n\
opcache.enable_cli=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=0\n\
opcache.jit_buffer_size=100M\n\
opcache.jit=tracing" > $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini

# Copie de l'application préparée depuis l'étape builder
COPY --chown=www-data:www-data --from=builder /var/www/html /var/www/html

# Passage à un utilisateur non-root
USER www-data

CMD ["php-fpm"]


# ==========================================
# 5. NGINX PRODUCTION
# ==========================================
FROM nginx:alpine AS nginx_prod

COPY --from=prod /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
