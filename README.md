# API Scheduler - Documentation d'installation et de configuration

Ce document décrit les étapes nécessaires pour installer, configurer et lancer l'API Scheduler.

## Prérequis

Avant de commencer, assurez-vous d'avoir les outils suivants installés sur votre machine :

*   **PHP** (version 8.4 ou supérieure)
*   **Composer**
*   **Symfony CLI** (recommandé) ou un serveur web local
*   **SQL Server** (ou un accès à une instance SQL Server)
*   **OpenSSL** (pour la génération des clés JWT si nécessaire)

## Installation

1.  **Cloner le dépôt**
    ```bash
    git clone <url_du_repository>
    cd API_Scheduler
    ```

2.  **Installer les dépendances PHP**
    ```bash
    composer install
    ```

## Configuration

### 1. Variables d'environnement

Commencez par créer un fichier `.env.local` pour surcharger les variables d'environnement locales (ce fichier ne doit pas être versionné).

```bash
cp .env .env.local
```

Ouvrez le fichier `.env.local` et configurez la connexion à votre base de données SQL Server via la variable `DATABASE_URL`.

Exemple pour SQL Server :
```dotenv
DATABASE_URL="sqlsrv://UTILISATEUR:MOT_DE_PASSE@HOST:PORT/NOM_DE_LA_BDD?serverVersion=13&encrypt=no&trustServerCertificate=yes"
```
*Note : Adaptez les paramètres (utilisateur, mot de passe, hôte, nom de la base) selon votre configuration.*

### 2. Configuration JWT (Si utilisé)

Le projet intègre `lexik/jwt-authentication-bundle`. Si vous prévoyez d'utiliser l'authentification JWT, vous devez générer les clés SSL.

```bash
php bin/console lexik:jwt:generate-keypair
```
Cela va créer les fichiers `config/jwt/private.pem` et `config/jwt/public.pem` et mettre à jour votre `.env.local` avec la passphrase (si vous en avez défini une différente).

## Base de données

1.  **Créer la base de données** (si elle n'existe pas déjà)
    ```bash
    php bin/console doctrine:database:create
    ```

2.  **Exécuter les migrations**
    Pour créer les tables nécessaires (y compris la mise à jour de la table `Session` pour l'authentification) :
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

    *Si vous n'avez pas encore de migration pour les changements récents (ajout de rôles user), créez-en une d'abord :*
    ```bash
    php bin/console make:migration
    php bin/console doctrine:migrations:migrate
    ```

## Lancement du serveur

Vous pouvez lancer le serveur de développement Symfony :

```bash
symfony server:start
```
L'API sera accessible par défaut sur `http://127.0.0.1:8000`.

## Utilisation de l'API

### Authentification

Une route de connexion personnalisée a été mise en place.

*   **URL** : `/api/login`
*   **Méthode** : `POST`
*   **Corps de la requête (JSON)** :
    ```json
    {
        "username": "votre_email@exemple.com",
        "password": "votre_mot_de_passe"
    }
    ```
    *(Note: le champ `username` correspond à l'email de l'utilisateur dans l'entité Session).*

*   **Réponse (Succès)** :
    Renvoie les informations de l'utilisateur connecté (ID, Email, Rôles).

### Autres commandes utiles

*   Déconnexion : `/api/logout` (Si utilisé avec une session)
*   Vérifier les routes disponibles : `php bin/console debug:router`

