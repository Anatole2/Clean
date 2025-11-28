# Shared Parking

## 🚀 Installation et Démarrage

Ce projet est conteneurisé avec **Docker**. Vous n'avez pas besoin d'installer PHP ou MySQL localement.

### Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et lancé.

### Étapes d'installation

1. **Cloner le projet** (si ce n'est pas déjà fait)

   ```bash
   git clone https://github.com/Anatole2/Clean/
   cd MyWeeklyAllowance
   ```

2. **Configurer l'environnement**
   Copiez le fichier d'exemple pour créer votre configuration locale :

   ```bash
   cp .env.example .env
   ```

   Vous pouvez modifier les ports ou les identifiants dans le fichier `.env` si nécessaire.

3. **Lancer les conteneurs**
   Construisez et démarrez l'application :
   ```bash
   docker compose up -d --build
   ```
4. **Installer les dépendances PHP**
   Exécutez `composer install` à l'intérieur du conteneur PHP pour télécharger les librairies nécessaires (dont PHPUnit) :

   ```bash
   docker exec sharedParking_php composer install
   ```

5. **Initialiser la base de données 💾**

Accédez à http://localhost:8081 (PhpMyAdmin). Connectez-vous avec l'utilisateur root et le mot de passe défini dans .env. Sélectionnez la base de données myweeklyallowance et utilisez l'onglet "Importer" pour charger le fichier SQL du projet qui se trouve dans le dossier `Database/myweeklyallowanceDatabase.sql`.

### 🌍 Accès à l'application

Une fois les conteneurs démarrés :

- **Application Web** : [http://localhost:8080](http://localhost:8080) (ou le port défini dans `APP_PORT`)
- **PhpMyAdmin** (Gestion BDD) : [http://localhost:8081](http://localhost:8081) (ou le port défini dans `PMA_PORT`)

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
| Nom | Prénom | [Pseudo](https://github.com/Pseudo) |
| Nom | Prénom | [Pseudo](https://github.com/Pseudo) |
| Nom | Prénom | [Pseudo](https://github.com/Pseudo) |

