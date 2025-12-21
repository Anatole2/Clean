# Shared Parking

## 🚀 Installation et Démarrage

Ce projet est conteneurisé avec **Docker**. Vous n'avez pas besoin d'installer PHP ou MySQL localement.

### Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et lancé.

### Étapes d'installation

1. **Cloner le projet** (si ce n'est pas déjà fait)

   ```bash
   git clone https://github.com/Anatole2/Clean/
   cd Clean
   ```

2. **Configurer l'environnement**
   Copiez le fichier d'exemple pour créer votre configuration locale :

   ```bash
   cp .env.example .env
   ```

   Vous pouvez modifier les ports ou les identifiants dans le fichier `.env` si nécessaire.

   **Choix de la Base de Données** : Le projet supporte MySQL et MongoDB. Vous pouvez choisir quelle technologie utiliser en modifiant la variable DB_CONNECTION dans votre fichier .env :
   Ini, TOML

   **Dans le fichier .env :**

      *Pour utiliser MySQL (par défaut)* : `DB_CONNECTION=mysql`

      *Pour utiliser MongoDB* :
      `DB_CONNECTION=mongodb`

      L'application détectera automatiquement ce changement et utilisera les Repositories adaptés (Architecture Hexagonale).

3. **Lancer les conteneurs**
   Construisez et démarrez l'application :

   ```bash
   docker compose up -d --build
   ```

4. **Initialiser la Base de Données (MySQL)**
   Une fois les conteneurs lancés, vous devez créer les tables. Exécutez cette commande pour importer le schéma SQL situé dans le dossier database/ ou le faire manuellement via PhpMyAdmin :

```bash
    docker exec -i sharedParking_mysql mysql -u user -puser SharedParking < database/schema.sql
```

(Note : Si vous avez changé les identifiants dans le .env, adaptez la commande : mysql -u VOTRE_USER -pVOTRE_PASS VOTRE_DB)

5. **Installer les dépendances PHP**
   Exécutez `composer install` à l'intérieur du conteneur PHP pour télécharger les librairies nécessaires (dont PHPUnit) :

   ```bash
   docker exec sharedParking_php composer install
   ```

### 🌍 Accès à l'application

Une fois les conteneurs démarrés :

- **Application Web** : [http://localhost:8080](http://localhost:8080) (ou le port défini dans `APP_PORT`)
- **PhpMyAdmin** (Gestion BDD) : [http://localhost:8081](http://localhost:8081) (ou le port défini dans `PMA_PORT`)
- **MongoExpress** (Gestion BDD) : [http://localhost:8082](http://localhost:8082) (ou le port défini dans `MONGOEXPRESS_PORT`)

### ✅ Lancer les Tests

Pour exécuter la suite de tests PHPUnit avec le rapport de couverture :

```bash
docker exec sharedParking_php ./vendor/bin/phpunit --coverage-text
```

> **Note** : Le nom du conteneur `sharedParking_php` dépend de la variable `PROJECT_NAME` dans votre `.env`. Si vous l'avez changé, adaptez la commande.

### Commandes utiles

- **Arrêter les conteneurs** : `docker compose down`
- **Voir les logs PHP** : `docker compose logs -f php`
- **Accéder au terminal du conteneur PHP** : `docker exec -it sharedParking_php bash`

Auteur
| Nom | Prénom | Github |
| --- | --- | --- |
| Allard | Adrien | [The-Leyn](https://github.com/The-Leyn) |
| Dupuis | Anatole | [Anatole2](https://github.com/Anatole2) |
| Da Rocha | Hugo | [Hugodrc55](https://github.com/Hugodrc55) |
| Ben Chabane | Aryles | [Aryles27](https://github.com/Aryles27) |


--- 


# **Requêtes API**

## **Owner**

### Liste des parkings du Owner

```bash
curl -X GET http://localhost:8080/my-parkings \
-H "Accept: application/json" \
-H "Cookie: auth_token=TOKEN_PROPRIO_TEST"
```

### Create parking

```bash
curl -X POST http://localhost:8080/parkings \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H "Cookie: auth_token=TOKEN_PROPRIO_TEST" \
-d '{
    "name": "Parking API Test Json",
    "totalPlaces": 200,
    "latitude": 42.588,
    "longitude": 23.259,
    "priceGridConfig": {
        "30": 100,
        "60": 200,
        "120": 600,
        "1440": 3000
    },
    "openingHoursConfig": [
        {
            "startDay": 1,
            "startTime": "08:00",
            "endDay": 1,
            "endTime": "18:00"
        },
        {
            "startDay": 2,
            "startTime": "08:00",
            "endDay": 2,
            "endTime": "12:00"
        },
        {
            "startDay": 2,
            "startTime": "13:00",
            "endDay": 2,
            "endTime": "18:00"
        },
        {
            "startDay": 3,
            "startTime": "08:00",
            "endDay": 3,
            "endTime": "12:00"
        },
        {
            "startDay": 3,
            "startTime": "14:00",
            "endDay": 3,
            "endTime": "18:00"
        }
    ]
}'
```

### AddParkingSubscriptionPlan

```bash
curl -X POST http://localhost:8080/parkings/UUID_DU_PARKING/plans \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Cookie: auth_token=TOKEN_JWT" \
  -d '{
    "name": "Forfait Week-end VIP",
    "price": 45.00,
    "schedule": [
      {
        "startDay": 5,
        "startTime": "18:00",
        "endDay": 1,
        "endTime": "08:00"
      }
    ]
  }'
```

### UpdateParkingPrice

```bash
curl -X POST "http://localhost:8080/parkings/ID_DU_PARKING/prices" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "Cookie: auth_token=TON_TOKEN_OWNER" \
     -d '{
           "priceGridConfig": {
               "60": 200,
               "120": 380,
               "1440": 1500
           }
         }'
```

### UpdateParkingHours

```bash
curl -X POST "http://localhost:8080/parkings/ID_DU_PARKING/hours" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -d '{
           "openingHoursConfig": [
               {
                   "startDay": 1,
                   "startTime": "08:00",
                   "endDay": 1,
                   "endTime": "12:00"
               },
               {
                   "startDay": 1,
                   "startTime": "14:00",
                   "endDay": 1,
                   "endTime": "18:00"
               },
               {
                   "startDay": 2,
                   "startTime": "09:00",
                   "endDay": 2,
                   "endTime": "17:00"
               }
           ]
         }'
```

### GetParkingReservations

```bash
curl -X GET "http://localhost:8080/parkings/ID_DU_PARKING/reservations" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json"
```

### GetParkingSessions
```bash
curl -X GET "http://localhost:8080/parkings/ID_DU_PARKING/sessions" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json"
```

### GetParkingAvailability

*Global*
```bash
curl -X GET "http://localhost:8080/parkings/ID_DU_PARKING/availability" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json"
```

*With date*
```bash
curl -X GET "http://localhost:8080/parkings/ID_DU_PARKING/availability?date=2025-12-25T20:00:00" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json"
```

### GetParkingRevenue

```bash
curl -X GET "http://localhost:8080/parkings/ID_DU_PARKING/revenue?month=12&year=2025" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json"
```

### GetUnauthorizedParkers

```bash
curl -X GET "http://localhost:8080/parkings/ID_DU_PARKING/unauthorized" \
     -H "Cookie: auth_token=JWT_TOKEN_OWNER" \
     -H "Accept: application/json"
```
## **Auth**

### Register Owner

```bash
curl -X POST http://localhost:8080/register/owner \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{ 
	"email": "api-owner@test.com",
	"password": "secure123", 
	"firstName": "RobotOwner", 
	"lastName": "API" 
}'
```

### Register User

```bash
curl -X POST http://localhost:8080/register/user \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "api-user@test.com",
    "password": "secure123",
    "firstName": "RobotUser",
    "lastName": "API"
  }'
```
### Login

```bash
curl -X POST http://localhost:8080/login \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "email": "user@gmail.com",
    "password": "123456"
}'
```

## User

### SearchParking

```bash
curl -X GET "http://localhost:8080/search?lat=48.8566&lon=2.3522" \
     -H "Accept: application/json" \
     -b "auth_token=TOKEN_JWT"
```

### CreateReservation

```bash
curl -X POST http://localhost:8080/reservation \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Cookie: auth_token=TOKEN_JWT" \
  -d '{
    "parkingId": "UUID_DU_PARKING",
    "start_time": "2026-02-01 14:00",
    "end_time": "2026-02-01 16:00"
  }'
```

### GetReservations

```bash
curl -X GET "http://localhost:8080/reservations" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json"
```

### GetParkingSessions

```bash
curl -X GET "http://localhost:8080/parkings/sessions" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json"
```
### GenerateInvoice

```bash
curl -X GET "http://localhost:8080/reservations/ID_RESERVATION/invoice" \
     -H "Cookie: auth_token=JWT_TOKEN" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json"
```

### SubscribeToParkingPlan

```bash
curl -X POST "http://localhost:8080/parkings/UUID_DU_PARKING/subscribe" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "Cookie: auth_token=VOTRE_TOKEN_JWT_ICI" \
     -d '{
           "plan_id": "ID_DU_PLAN",
           "start_date": "2026-01-01",
           "end_date": "2026-03-01"
         }'
```

### Enter Parking

```bash
curl -X POST http://localhost:8080/parkings/UUID_DU_PARKING/enter \
     -H "Cookie: auth_token=TOKEN_JWT" \
     -H "Content-Type: application/json"
```

### Exit Parking

```bash
curl -X POST http://localhost:8080/parkings/UUID_DU_PARKING/exit \
     -H "Cookie: auth_token=TOKEN_JWT" \
     -H "Content-Type: application/json"
```

## Shared

### GetParkingDetails

```bash
curl -X GET http://localhost:8080/parkings/UUID_DU_PARKING \
     -H "Accept: application/json" \
     -H "Cookie: auth_token=TOKEN_JWT"
```
