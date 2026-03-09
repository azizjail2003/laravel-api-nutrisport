<div align="center">
  <h1>NutriSport API</h1>
  <p>Une API REST complète développée sous Laravel 11 pour une plateforme e-commerce multi-boutiques spécialisée en nutrition sportive.</p>

  [![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://php.net)
  [![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
  [![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
  [![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://docker.com)
</div>

---

## 🚀 Fonctionnalités Principales

- **Architecture Multi-Sites** : Support natif pour plusieurs pays (France, Italie, Belgique) avec gestion dynamique des devises (EUR, CHF) et des tarifs spécifiques par pays.
- **Catalogue & Panier** : Listing public des produits et gestion de panier avec persistance via Redis/Cache (durée de vie : 3 jours).
- **Prise de Commandes** : Processus d'achat sécurisé, génération de tickets, décrémentation des stocks (`lockForUpdate`), envois d'emails asynchrones et notifications en temps réel (via Laravel Reverb / Pusher).
- **Authentification JWT Intégrale** : Accès sécurisés via JSON Web Tokens, séparant les clients (Frontend, TTL 6h) et les agents (BackOffice, TTL 8h).
- **BackOffice Avancé** : Gestion granulaire des rôles (`view_orders`, `create_products`) pour les agents administratifs.
- **Tâches Planifiées & Flux** : Génération de rapports analytiques quotidiens et exports automatiques (XML/JSON).

---

## 🛠 Prérequis

Pour installer et lancer ce projet, vous aurez besoin de :
- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/) & Docker Compose

Aucune installation locale de PHP n'est nécessaire, tout est conteneurisé.

---

## ⚙️ Installation & Lancement

1. **Cloner le projet**
   ```bash
   git clone <url-du-depot>
   cd laravel-api-nutrisport
   ```

2. **Configuration des variables d'environnement**
   Copiez le fichier d'exemple et générez les clés de sécurité.
   ```bash
   cp .env.example .env
   ```

3. **Démarrage des conteneurs via Docker Compose**
   L'application est servie par un combo Nginx / PHP-FPM ultra-léger.
   ```bash
   docker compose up -d
   ```

4. **Préparation de la base de données et des clés**
   Une fois les conteneurs démarrés, exécutez ces commandes pour finaliser l'installation :
   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan jwt:secret
   docker compose exec app php artisan migrate:fresh --seed
   ```

L'API est désormais accessible sur **[http://localhost:8080](http://localhost:8080)**.

> **💡 Outil d'administration DB** : phpMyAdmin est directement accessible sur **[http://localhost:9090](http://localhost:9090)** 
> *(Identifiants : `nutri_user` / `nutri_password`, Base : `nutrisport`)*

---

## 📖 Utilisation & Endpoints

Toutes les routes publiques clients sont préfixées par `/api/{site}` où `{site}` prend la valeur `fr`, `it` ou `be`. 

### Authentification & Profil Client
- `POST /api/{site}/register` : Créer un compte.
- `POST /api/{site}/login` : Obtenir un jeton JWT.
- `POST /api/{site}/logout` : Se déconnecter et révoquer le jeton JWT *(Auth requise)*.
- `GET  /api/{site}/me` : Consulter son profil *(Auth requise)*.
- `PUT  /api/{site}/profile` : Mettre à jour ses informations personnelles *(Auth requise)*.

### Catalogue & Panier (Accès Libre)
- `GET  /api/{site}/products` : Parcourir le catalogue (prix alignés sur la devise du site).
- `GET  /api/{site}/products/{id}` : Consulter les détails d'un produit spécifique.
- `GET  /api/{site}/cart` : Afficher le contenu du panier.
- `POST /api/{site}/cart` : Ajouter un article `{ "product_id": 1, "quantity": 1 }`.
- `DELETE /api/{site}/cart/{productId}` : Retirer un article spécifique du panier.
- `DELETE /api/{site}/cart` : Vider complètement le panier.

### Commandes
- `GET  /api/{site}/orders` : Lister l'historique de ses commandes *(Auth requise)*.
- `POST /api/{site}/orders` : Transformer son panier en commande *(Auth requise)*.
- `GET  /api/{site}/orders/{id}` : Détails d'une session d'achat spécifique *(Auth requise)*.

### Flux de Données Publics (Feeds)
- `GET /api/feeds/json` : Exporter le catalogue au format JSON.
- `GET /api/feeds/xml` : Exporter le catalogue au format XML.

### BackOffice (Agents administratifs)
Routes situées sous `/api/backoffice/`.

- `POST /login` : Authentification agent.
- `POST /logout` : Déconnexion de l'agent *(Auth requise)*.
- `GET  /me` : Consulter le profil de l'agent connecté *(Auth requise)*.
- `GET  /orders` : Historique des 5 derniers jours *(Permission : view_orders)*.
- `POST /products` : Ajout d'une nouvelle référence avec grilles de prix multiples *(Permission : create_products)*.

---

## 👥 Comptes de démonstration (Seeders)

Afin de faciliter vos tests côté BackOffice, la base de données est provisionnée avec trois agents :

| Fonction | Email | Mot de passe | Rôle effectif |
|----------|-------|--------------|---------------|
| **SuperAdmin** | `admin@nutrisport.fr` | `password` | Contourne toutes les permissions (ID=1) |
| **Agent Commandes** | `commandes@nutrisport.fr` | `password` | Lecture des commandes uniquement |
| **Agent Catalogue** | `catalogue@nutrisport.fr` | `password` | Création de produits uniquement |

*Note: Aucun client n'est provisionné par défaut, vous devez utiliser l'endpoint `/register`.*

---

## 🧪 Tests via Postman

Un fichier de collection intégral a été inclus dans le dépôt : **`postman_collection.json`**. 
- Il inclut la gestion automatique des variables (auto-sauvegarde des tokens JWT).
- Il couvre tout l'arbre de tests nominatifs et des bords (`edge-cases` : mots de passe invalides, panier vide).
- Il vous suffit de l'importer dans Postman et de jouer les requêtes dans l'ordre de la collection.

---

## 📂 Structure du code

L'application respecte les conventions Laravel, tout en structurant les éléments métier selon des dossiers distincts :
- `app/Events/` : Dispatch des actions temps-réel post-achat.
- `app/Mail/` : Scénarios asynchrones pour l'envoi des confirmations de commande.
- `app/Feeds/` : Gestionnaires des drivers XML/JSON pour nos exports partenaires.
- `docker/nginx/default.conf` : Bloc de routage Web côté conteneur Nginx.

---

## 👨‍💻 Développeur

Développé par **Abdelaziz Jail**.

- ✉️ **Email** : [jailabdelaziz@icloud.com](mailto:jailabdelaziz@icloud.com)
- 🌐 **Portfolio** : [https://jailabdelaziz.online/](https://jailabdelaziz.online/)
