# --- 1. ÉTAPE DE BASE ---
# On utilise Debian au lieu d'Alpine pour une compatibilité parfaite avec SQL Server
FROM php:8.4-fpm AS base

# 1.1 Installation des dépendances système (gnupg2 et curl sont requis pour la clé MS)
RUN apt-get update && apt-get install -y \
    gnupg2 \
    curl \
    libicu-dev \
    unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# 1.2 Ajout du dépôt officiel Microsoft (Debian 12 - Bookworm)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list

# 1.3 Installation des drivers SQL Server (msodbcsql18)
RUN apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && rm -rf /var/lib/apt/lists/*

# 1.4 Installation des extensions PHP
RUN docker-php-ext-install intl opcache \
    && pecl install redis sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable redis sqlsrv pdo_sqlsrv

# 1.5 Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# --- 2. DÉVELOPPEMENT ---
FROM base AS dev
ENV APP_ENV=dev
# Installe les dépendances au démarrage uniquement si le dossier vendor est absent
CMD sh -c "[ ! -d vendor ] && composer install --no-interaction; php-fpm"

# --- 3. PRODUCTION (Défaut) ---
FROM base AS prod
ENV APP_ENV=prod
COPY . .
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
RUN chown -R www-data:www-data var/
