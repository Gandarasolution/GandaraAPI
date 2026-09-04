# API Scheduler

API Symfony 8 pour la gestion des plannings. Le projet est prévu pour être lancé avec Docker Compose : PHP 8.4 et les extensions SQL Server sont installés dans le conteneur, avec Nginx, Redis et Mercure.

## Prérequis

- Docker Desktop avec Docker Compose V2 démarré
- Git
- Un accès à une base SQL Server existante

Le serveur SQL n'est pas fourni par Docker Compose. Le conteneur `api` doit pouvoir joindre l'hôte indiqué dans `DATABASE_URL`.

## Installation et initialisation

### 1. Récupérer le projet

```bash
git clone <url_du_repository>
cd API_Scheduler
```

### 2. Configurer l'environnement

Le dépôt contient un fichier `.env.exemple`. Si `.env` n'existe pas encore, copiez-le :

```bash
cp .env.exemple .env
```

Sous PowerShell :

```powershell
Copy-Item .env.exemple .env
```

Modifiez ensuite `.env` avec vos valeurs locales. Comme Compose injecte ce fichier dans les conteneurs, c'est lui qui doit contenir la configuration utilisée par Docker. Au minimum, configurez `DATABASE_URL` avec les identifiants de votre SQL Server :

```dotenv
DATABASE_URL="sqlsrv://UTILISATEUR:MOT_DE_PASSE@HOTE:PORT/NOM_DE_LA_BDD?serverVersion=13&TrustServerCertificate=yes&charset=UTF-8"
```

Vérifiez également `APP_SECRET`, `JWT_PASSPHRASE` et `MERCURE_JWT_SECRET`. Ne versionnez jamais de secrets réels.

### 3. Construire et démarrer Docker

**Attention à l'environnement cible (Développement vs Production) :**

- En développement : Conservez le fichier compose.override.yaml. Docker le charge automatiquement pour activer les outils de debug, surcharger l'environnement en dev et monter vos volumes locaux.

- En production : Supprimez le fichier compose.override.yaml avant le démarrage. Docker se basera uniquement sur le compose.yaml principal pour démarrer l'API avec les paramètres optimisés (mode prod, cache optimisé, pas de montage de fichiers locaux).

Depuis la racine du projet, lancez la construction et le démarrage :

```bash
docker compose up -d --build
```

Cette commande construit l'image PHP, démarre l'API, Nginx, Redis et Mercure. L'API est disponible à l'adresse `http://localhost:8000` et Mercure à `http://localhost:3000`.

Contrôlez l'état des services :

```bash
docker compose ps
docker compose logs -f api
```

### 4. Installer les dépendances et vérifier la base

Le conteneur de développement installe automatiquement Composer si `vendor` est absent. Pour forcer ou refaire l'installation :

```bash
docker compose exec api composer install
```

Testez ensuite la connexion SQL Server :

```bash
docker compose exec api php bin/console dbal:run-sql "SELECT 1"
```

### 5. Initialiser les clés JWT

Si les fichiers `config/jwt/private.pem` et `config/jwt/public.pem` n'existent pas, générez-les depuis le conteneur :

```bash
docker compose exec api php bin/console lexik:jwt:generate-keypair
```

Cette commande utilise `JWT_PASSPHRASE` défini dans l'environnement. Si les clés existent déjà, ne les remplacez pas sans vérifier les clients qui utilisent l'API.

## Arrêt et maintenance

Arrêter les conteneurs sans supprimer les volumes :

```bash
docker compose stop
```

Arrêter et supprimer les conteneurs :

```bash
docker compose down
```

Afficher les routes disponibles :

```bash
docker compose exec api php bin/console debug:router
```

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
