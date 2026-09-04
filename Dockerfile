# --- 1. ÉTAPE DE BASE ---
# On utilise Debian au lieu d'Alpine pour une compatibilité parfaite avec SQL Server
FROM php:8.4-fpm AS base

# 1.1 Installation des dépendances système (gnupg2 et curl sont requis pour la clé MS)
RUN apt-get update && apt-get install -y \
    gnupg2 \
    curl \
    libicu-dev \
    unixodbc-dev \
    unzip \
    git \
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
    && docker-php-ext-enable redis sqlsrv pdo_sqlsrv \
    && rm -rf /tmp/pear

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
# Copier uniquement les fichiers Composer pour utiliser le cache Docker
COPY composer.json composer.lock symfony.lock .env ./
COPY bin/ bin/
COPY config/ config/
COPY public/ public/
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

# Copier le reste du projet
COPY . .


# Activer le php.ini de production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configurer OPcache pour des performances maximales
RUN echo "opcache.enable=1\n\
opcache.enable_cli=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=0\n\
opcache.jit_buffer_size=100M\n\
opcache.jit=tracing" > $PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini


# Générer l'autoloader optimisé et exécuter les scripts
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
RUN chown -R www-data:www-data var/
