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

## Documentation de l'API

L'application expose une documentation en ligne générée automatiquement via Swagger Ui (NelmioApiDocBundle).
Vous pouvez y accéder via l'URL suivante une fois le serveur lancé :

**URL de la documentation web** : `http://127.0.0.1:8000/api/doc`

# Documentation des Routes de l'API

## Routes disponibles (Planning Evenement)

Toutes les routes événementielles commencent par `/api/event`.

### Catégorie : Lecture d'évènements

#### 1. Lister les événements entre deux dates
*   **Mthode** : `GET`
*   **Route** : `/api/event/{dateStart}/{dateEnd}`
*   **Données d'entrée (Paramètres de Route dans l'URL)** : 
    * `dateStart` (ex: `2024-01-01`) : Date de début de recherche
    * `dateEnd` (ex: `2024-12-31`) : Date de fin de recherche
*   **Retour** :
    ```json
    {
      "error": 0,
      "data": [
        {
          "IdPlanningEvenement": 1,
          "DebutPlanningEvenement": 1713513600,
          "FinPlanningEvenement": 1713528000,
          ...
        }
      ]
    }
    ```
*   **Description** : Récupère la liste de tous les événements compris entre `dateStart` et `dateEnd`.

#### 2. Récupérer un événement spécifique
*   **Mthode** : `GET`
*   **Route** : `/api/event/{id}`
*   **Données d'entrée (Paramètres de Route dans l'URL)** : 
    * `{id}` : L'identifiant numérique de l'événement.
*   **Retour** : JSON contenant `error: 0` et l'objet de l'événement dans `data`.
*   **Description** : Affiche les informations complètes d'un événement donné.

#### 3. Récupérer les événements d'un employé spécifique
*   **Mthode** : `GET`
*   **Route** : `/api/event/?employee={id}&type={Salarie|Interim}`
*   **Données d'entrée (Paramètres de Requête URL / Query String)** : 
    * `employee` : L'identifiant numérique de l'employé
    * `type` : Soit "Salarie", soit "Interim".
*   **Retour** : JSON contenant les événements associés à cet employé.
*   **Description** : Cherche les événements liés au planning d'un employé selon son type (Salarie ou Interim).

### Catégorie : Création, Modification et Suppression (CRUD basique)

#### 4. Créer un nouvel événement
*   **Mthode** : `POST`
*   **Route** : `/api/event`
*   **Données d'entrée (Corps / Body JSON)** :
    ```json
    {
       "DebutPlanningEvenement": 1713513600,
       "FinPlanningEvenement": 1713528000,
       "IdPlanningRessource": 10
    }
    ```
*   **Retour** : L'événement nouvellement créé avec un code 201 (Created) ou un message d'erreur.
*   **Description** : Enregistre un nouvel événement dans le planning.

#### 5. Modifier un événement
*   **Mthode** : `PUT`
*   **Route** : `/api/event/{id}`
*   **Données d'entrée mixtes** :
    * *URL Route* : `{id}` L'identifiant de l'événement.
    * *Body JSON* (Champs modifiés) :
      ```json
      {
        "PlanningEvenementPriorite": 1,
        "DebutPlanningEvenement": 1713513600
      }
      ```
*   **Retour** : Message confirmant le succès ou l'échec. (`{ "error": 0, "message": "..." }`)
*   **Description** : Met à jour un événement existant.

#### 6. Supprimer un événement
*   **Mthode** : `DELETE`
*   **Route** : `/api/event/{id}`
*   **Données d'entrée (Paramètres de Route dans l'URL)** : 
    * `{id}` : L'identifiant numérique de l'événement à supprimer.
*   **Retour** : Message de succès ou d'erreur.
*   **Description** : Retire un événement de la base de données.

### Catégorie : Opérations Complexes (Mises à jour avancées)

#### 7. Mettre à jour un événement et sa ressource
*   **Mthode** : `PUT`
*   **Route** : `/api/event/updateRessourceAndEvent/{id}`
*   **Données d'entrée mixtes** :
    * *URL Route* : `{id}` L'identifiant de l'événement ciblé.
    * *Body JSON* :
      ```json
      {
         "DebutPlanningEvenement": 1713513600,
         "Ressource": {
           "IdPlanningRessource": 10,
           "Couleur": "#FFFFFF"
         }
      }
      ```
*   **Retour** : `{ "error": 0, "message": "Événement mis à jour avec succès" }`
*   **Description** : Met à jour simultanément un événement et les informations de sa ressource liée via une procédure stockée.

#### 8. Diviser un événement
*   **Mthode** : `PUT`
*   **Route** : `/api/event/divide/{id}`
*   **Données d'entrée mixtes** :
    * *URL Route* : `{id}` L'identifiant de l'événement ciblé.
    * *Body JSON* :
      ```json
      {
        "DateCoupure": 1713520000
      }
      ```
*   **Retour** : Les données du/des nouveaux événements divisés.
*   **Description** : Coupe un événement existant en deux parties selon un timestamp donné.

#### 9. Répéter un événement
*   **Mthode** : `POST`
*   **Route** : `/api/event/repeat`
*   **Données d'entrée (Corps / Body JSON)** : Les paramètres nécessaires à la duplication de l'événement (selon la logique de votre repository).
*   **Retour** : Les détails des événements nouvellement créés via la répétition.
*   **Description** : Duplique un événement sur plusieurs occurrences (gestion de récurrence).
