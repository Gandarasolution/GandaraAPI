# API Scheduler - Documentation d'installation et de configuration

Ce document décrit les étapes nécessaires pour installer, configurer et lancer l'API Scheduler.

## Prérequis

Avant de commencer, assurez-vous d'avoir les outils suivants installés sur votre machine :

*   **PHP** (version 8.4 ou supérieure)
*   **Composer**
*   **Symfony CLI** (recommandé) ou un serveur web local
*   **SQL Server** (ou un accès à une instance SQL Server)
*   **OpenSSL** (pour la génération des clés JWT si nécessaire)

Important : OpenSSL doit être disponible avec le support TLS activé sur votre PHP (extension `openssl`). Sur Windows, assurez-vous que l'extension `php_openssl.dll` est activée dans votre `php.ini` (ex. `extension=openssl`). Le support TLS est essentiel pour les échanges sécurisés et pour certaines opérations du bundle JWT.

Sous Windows : activer les extensions SQL Server (sqlsrv / pdo_sqlsrv)

1. Copiez manuellement les fichiers nécessaires depuis `fichier_config_php` vers le dossier `ext` de votre installation PHP (ex. `C:\php\ext` ou `C:\xampp\php\ext`).

2. Ouvrez le `php.ini` utilisé par votre serveur (vérifiez que c'est bien le `php.ini` de l'installation que vous avez modifiée) et ajoutez les lignes suivantes en adaptant les noms exacts des DLL :

```
extension=php_sqlsrv_84_nts_x64.dll
extension=php_pdo_sqlsrv_84_nts_x64.dll
```

(Remplacez `php_sqlsrv_84_nts_x64.dll` et `php_pdo_sqlsrv_84_nts_x64.dll` par les noms exacts que vous avez copiés.)

3. Redémarrez votre serveur web / service PHP (Apache, IIS, PHP-FPM, ou le serveur Symfony) pour que les extensions soient prises en compte.


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
