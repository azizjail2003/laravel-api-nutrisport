# laravel-api-nutrisport

API REST Laravel pour NutriSport — plateforme e-commerce de nutrition sportive opérant sur 3 sites (FR, IT, BE).

## Prérequis

- PHP >= 8.2
- Composer
- MySQL 8+ ou SQLite (pour les tests)

## Installation

```bash
git clone <repo-url> laravel-api-nutrisport
cd laravel-api-nutrisport

composer install
cp .env.example .env

# Remplir .env (DB, JWT, Pusher, Mail) puis :
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
```

## Configuration `.env`

| Variable | Description |
|----------|-------------|
| `DB_*` | Connexion base de données |
| `JWT_SECRET` | Généré par `php artisan jwt:secret` |
| `JWT_TTL` | Durée token client en minutes (`360` = 6h) |
| `MAIL_*` | Config serveur mail |
| `MAIL_ADMIN_ADDRESS` | Adresse administrateur |
| `PUSHER_*` | Credentials Pusher (notifications BO) |

## Lancer le serveur

```bash
php artisan serve   # → http://localhost:8000
```

## Cron (rapport quotidien)

```cron
* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1
```

La commande `nutrisport:daily-report` s'exécute à **minuit** chaque jour.

## Tests

```bash
php artisan test
php artisan test --filter CartTest   # tests panier uniquement
```

---

## Endpoints

### Variables
- `{site}` : `fr`, `it`, ou `be`
- Header auth : `Authorization: Bearer <token>`

### 🔐 Auth client

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| POST | `/api/{site}/register` | ❌ | Inscription |
| POST | `/api/{site}/login` | ❌ | Connexion (JWT 6h) |
| GET | `/api/{site}/me` | ✅ | Profil |
| PUT | `/api/{site}/profile` | ✅ | Modifier profil/mdp |
| POST | `/api/{site}/logout` | ✅ | Déconnexion |

### 📦 Catalogue

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| GET | `/api/{site}/products` | ❌ | Liste (prix du site) |
| GET | `/api/{site}/products/{id}` | ❌ | Détail |

### 🛒 Panier (cache 3 jours, sans auth)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/{site}/cart` | Voir |
| POST | `/api/{site}/cart` | Ajouter `{ product_id, quantity }` |
| DELETE | `/api/{site}/cart/{productId}` | Supprimer un article |
| DELETE | `/api/{site}/cart` | Vider |

### 🧾 Commandes (auth requise)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/{site}/orders` | Passer commande |
| GET | `/api/{site}/orders` | Historique |
| GET | `/api/{site}/orders/{id}` | Détail |

**Body POST /orders** :
```json
{
  "shipping_full_name": "Jean Dupont",
  "shipping_address": "12 Rue de la Paix",
  "shipping_city": "Paris",
  "shipping_country": "France"
}
```

### 🏢 BackOffice

| Méthode | Endpoint | Permission | Description |
|---------|----------|------------|-------------|
| POST | `/api/backoffice/login` | — | Connexion agent (JWT 8h) |
| GET | `/api/backoffice/me` | — | Profil agent |
| GET | `/api/backoffice/orders` | `view_orders` | Commandes 5 derniers jours |
| POST | `/api/backoffice/products` | `create_products` | Créer produit |

> Agent `ID=1` accède à tout sans vérification de permissions.

**Body POST /backoffice/products** :
```json
{
  "name": "Whey Chocolat",
  "stock": 100,
  "prices": [
    { "site_id": 1, "price": 34.99 },
    { "site_id": 2, "price": 32.99 },
    { "site_id": 3, "price": 36.99 }
  ]
}
```

### 🌐 Flux catalogue (publics)

| Endpoint | Description |
|----------|-------------|
| `/feeds/json` | JSON |
| `/feeds/xml` | XML |

> Architecture extensible via `FeedDriverInterface` — zéro modification du code existant pour ajouter un nouveau format.

---

## Architecture

```
app/
├── Console/Commands/    # SendDailyReport
├── Events/              # OrderPlaced (Pusher)
├── Feeds/               # FeedDriverInterface, JsonFeedDriver, XmlFeedDriver
├── Http/
│   ├── Controllers/Api/ # Auth, Product, Cart, Order
│   ├── Controllers/BackOffice/ # AgentAuth, BackOffice
│   └── Middleware/      # ResolveSite, CheckAgentPermission
├── Mail/                # OrderConfirmationMail, DailyReportMail
└── Models/              # User, Site, Product, ProductPrice, Order, OrderItem, Agent, AgentPermission
```

## Comptes de test (seeder)

| Type | Email | Mot de passe | Permissions |
|------|-------|-------------|-------------|
| Super admin | `admin@nutrisport.fr` | `password` | toutes |
| Agent commandes | `commandes@nutrisport.fr` | `password` | `view_orders` |
| Agent catalogue | `catalogue@nutrisport.fr` | `password` | `create_products` |
