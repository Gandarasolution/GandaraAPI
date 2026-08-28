#!/bin/bash
# install.sh - Script de déploiement initial pour l'API (Production)

echo "🚀 Début de l'installation de l'environnement..."

# 1. Variables globales (à modifier selon votre image Docker)
IMAGE_API="votre-registre.com/votre-nom/mon-api:latest"
DOMAINE="api.client.com" # À adapter

# 2. Création de l'arborescence
echo "📁 Création des dossiers locaux..."
mkdir -p config/jwt
mkdir -p docker/nginx

# 3. Génération des mots de passe sécurisés
echo "🔑 Génération des secrets..."
JWT_PASSPHRASE=$(openssl rand -base64 32)
MERCURE_JWT_SECRET=$(openssl rand -base64 32)

# 4. Génération des clés physiques LexikJWT (.pem)
echo "🔐 Génération de la paire de clés RSA pour LexikJWT..."
# Clé privée chiffrée avec la passphrase
openssl genrsa -passout pass:"$JWT_PASSPHRASE" -out config/jwt/private.pem 4096
# Clé publique associée
openssl rsa -passin pass:"$JWT_PASSPHRASE" -pubout -in config/jwt/private.pem -out config/jwt/public.pem
# Sécurisation des droits (lisible uniquement par le propriétaire)
chmod 600 config/jwt/private.pem public.pem

# 5. Création du fichier d'environnement (.env)
echo "📄 Création du fichier .env..."
cat <<EOF > .env
APP_ENV=prod
APP_SECRET=$(openssl rand -hex 16)

# Base de données (À MODIFIER avec les vrais identifiants du client)
DATABASE_URL="sqlsrv://user:password@serveur-db:1433/nom_base"

# JWT API
JWT_PASSPHRASE="${JWT_PASSPHRASE}"

# Mercure
MERCURE_JWT_SECRET="${MERCURE_JWT_SECRET}"
MERCURE_CORS_ALLOWED_ORIGINS="*"
EOF

# 6. Génération de la configuration Nginx
echo "🌐 Création de la configuration Nginx..."
cat <<EOF > docker/nginx/default.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    location / {
        try_files \$uri /index.php\$is_args\$args;
    }

    location ~ ^/index\.php(/|\$) {
        fastcgi_pass api:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)\$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        internal;
    }

    location ~ \.php\$ {
        return 404;
    }
}
EOF

# 7. Création du fichier docker-compose.yaml de Production
echo "🐳 Création du compose.yaml..."
cat <<EOF > compose.yaml
services:
  api:
    image: ${IMAGE_API}
    restart: always
    env_file:
      - .env
    volumes:
      # On monte les clés physiques du serveur vers le conteneur en lecture seule
      - ./config/jwt:/var/www/html/config/jwt:ro

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - api

  mercure:
    image: dunglas/mercure
    restart: always
    ports:
      - "3000:80"
    env_file:
      - .env
    environment:
      SERVER_NAME: ':80'
      MERCURE_PUBLISHER_JWT_KEY: \${MERCURE_JWT_SECRET}
      MERCURE_SUBSCRIBER_JWT_KEY: \${MERCURE_JWT_SECRET}
      MERCURE_EXTRA_DIRECTIVES: |
        cors_origins \${MERCURE_CORS_ALLOWED_ORIGINS}
    volumes:
      - mercure_data:/data
      - mercure_config:/config

  redis:
    image: redis:alpine
    restart: always
    command: redis-server --notify-keyspace-events Ex
    volumes:
      - redis_data:/data

volumes:
  mercure_data:
  mercure_config:
  redis_data:
EOF

echo "✅ Installation terminée avec succès !"
echo "------------------------------------------------------"
echo "⚠️  ACTION REQUISE : "
echo "1. Éditez le fichier .env pour renseigner la DATABASE_URL :"
echo "   nano .env"
echo "2. Lancez les conteneurs :"
echo "   docker compose up -d"
echo "3. (Optionnel) Lancez les migrations de base de données :"
echo "   docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction"
echo "------------------------------------------------------"