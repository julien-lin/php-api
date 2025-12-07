# PHP API

Bibliothèque PHP pour créer des APIs REST automatiques, inspirée d'API Platform de Symfony.

## 🎯 Fonctionnalités

- ✅ **Annotations pour exposer des entités** : Utilisez `#[ApiResource]` sur vos entités
- ✅ **Sérialisation JSON automatique** : Avec groupes de sérialisation (`read`, `write`)
- ✅ **Opérations CRUD automatiques** : GET, POST, PUT, DELETE prêts à l'emploi
- ✅ **Système de filtres avancé** : SearchFilter, DateFilter, RangeFilter, BooleanFilter, OrderFilter
- ✅ **Tri automatique** : Tri multi-colonnes via paramètres de requête
- ✅ **Pagination automatique** : Support des paramètres `page` et `limit`
- ✅ **Validation automatique** : Validation des données avec messages structurés (RFC 7807)
- ✅ **Gestion d'erreurs standardisée** : Format Problem Details (RFC 7807)
- ✅ **Relations Doctrine** : Embedding et sous-ressources pour les relations ManyToOne/OneToMany
- ✅ **Système d'événements** : Hooks pre/post intégrés avec core-php EventDispatcher
- ✅ **Pagination améliorée** : Métadonnées complètes (total, pages, navigation)
- ✅ **Documentation Swagger/OpenAPI automatique** : Génération depuis les annotations
- ✅ **Interface Swagger UI interactive** : Testez votre API directement dans le navigateur
- ✅ **Intégration Core PHP** : Utilise le système de contrôleurs existant

## 📦 Installation

```bash
composer require julienlinard/php-api
```

## 🚀 Utilisation

### 1. Créer une entité avec annotations et filtres

```php
<?php

use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Api\Filter\ApiFilter;
use JulienLinard\Api\Filter\SearchFilter;
use JulienLinard\Api\Filter\DateFilter;
use JulienLinard\Api\Filter\RangeFilter;
use JulienLinard\Api\Filter\BooleanFilter;
use JulienLinard\Doctrine\Mapping\Entity;
use JulienLinard\Doctrine\Mapping\Id;
use JulienLinard\Doctrine\Mapping\Column;

#[ApiResource(
    operations: ['GET', 'POST', 'PUT', 'DELETE'],
    routePrefix: '/api',
    shortName: 'users',
    paginationEnabled: true,
    itemsPerPage: 20
)]
#[ApiFilter(SearchFilter::class, properties: ['name', 'email'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
#[Entity]
class User
{
    #[Id]
    #[Column(type: 'integer')]
    #[ApiProperty(groups: ['read', 'write'])]
    public ?int $id = null;

    #[Column(type: 'string', length: 255)]
    #[ApiProperty(
        groups: ['read', 'write'],
        required: true,
        description: 'Email de l\'utilisateur'
    )]
    public string $email;

    #[Column(type: 'string', length: 255)]
    #[ApiProperty(
        groups: ['write'], // Seulement en écriture (pas dans la réponse)
        required: true
    )]
    public string $password;

    #[Column(type: 'string', length: 100)]
    #[ApiProperty(groups: ['read', 'write'])]
    public string $name;

    #[Column(type: 'datetime')]
    #[ApiProperty(groups: ['read'])] // Seulement en lecture
    public \DateTime $createdAt;
}
```

### 2. Créer un contrôleur API

```php
<?php

use JulienLinard\Api\Controller\ApiController;
use JulienLinard\Api\Serializer\JsonSerializer;
use JulienLinard\Core\Application;

class UserController extends ApiController
{
    public function __construct()
    {
        parent::__construct(User::class, new JsonSerializer());
    }

    protected function getAll(array $queryParams = []): array
    {
        $em = Application::getInstanceOrFail()->getEntityManager();
        return $em->getRepository(User::class)->findAll();
    }

    protected function getOne(int|string $id): ?object
    {
        $em = Application::getInstanceOrFail()->getEntityManager();
        return $em->getRepository(User::class)->find($id);
    }

    protected function save(object $entity): void
    {
        $em = Application::getInstanceOrFail()->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    protected function remove(object $entity): void
    {
        $em = Application::getInstanceOrFail()->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }
}
```

### 3. Définir les routes

```php
<?php

use JulienLinard\Router\Router;

$router = new Router();

// Routes automatiques pour l'API
$router->get('/api/users', [UserController::class, 'index']);
$router->get('/api/users/{id}', [UserController::class, 'show']);
$router->post('/api/users', [UserController::class, 'create']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->delete('/api/users/{id}', [UserController::class, 'delete']);
```

### 4. Utiliser l'API

#### GET /api/users
```json
{
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "createdAt": "2025-01-01T00:00:00+00:00"
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "limit": 20,
    "totalPages": 1,
    "hasNextPage": false,
    "hasPreviousPage": false
  }
}
```

#### GET /api/users/1
```json
{
  "data": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "createdAt": "2025-01-01T00:00:00+00:00"
  }
}
```

#### POST /api/users
```json
{
  "email": "newuser@example.com",
  "password": "secret123",
  "name": "Jane Doe"
}
```

## 📚 Annotations

### ApiResource

Expose une classe en tant que ressource API.

```php
#[ApiResource(
    operations: ['GET', 'POST', 'PUT', 'DELETE'], // Opérations disponibles
    routePrefix: '/api',                          // Préfixe de route
    normalizationContext: ['groups' => ['read']], // Groupes pour la sérialisation
    denormalizationContext: ['groups' => ['write']], // Groupes pour la désérialisation
    paginationEnabled: true,                      // Activer la pagination
    itemsPerPage: 30                             // Éléments par page
)]
```

### ApiProperty

Configure la sérialisation et la validation d'une propriété.

```php
#[ApiProperty(
    groups: ['read', 'write'],    // Groupes de sérialisation
    readable: true,               // Lisible via l'API
    writable: true,               // Modifiable via l'API
    required: true,               // Propriété requise (validation)
    required: true,               // Requis
    description: 'Description'   // Description
)]
```

### Groups

Alternative simple pour définir les groupes.

```php
#[Groups(['read', 'write'])]
public string $name;
```

## 📖 Documentation Swagger/OpenAPI

### Configuration

Ajoutez le contrôleur Swagger pour générer automatiquement la documentation :

```php
use JulienLinard\Api\Controller\SwaggerController;
use App\Entity\Product;
use App\Entity\User;

// Créer le contrôleur Swagger
$swaggerController = new SwaggerController(
    entityClasses: [Product::class, User::class], // Liste des entités exposées
    title: 'Mon API',
    version: '1.0.0',
    basePath: '/api'
);

// Routes pour la documentation
$router->get('/api/docs', [$swaggerController, 'ui']);        // Interface Swagger UI
$router->get('/api/docs.json', [$swaggerController, 'json']);  // Spec OpenAPI JSON
$router->get('/api/docs.yaml', [$swaggerController, 'yaml']);  // Spec OpenAPI YAML
```

### Utilisation

1. **Accédez à `/api/docs`** pour voir l'interface Swagger UI interactive
2. **Explorez les entités** : Toutes les entités avec `#[ApiResource]` sont automatiquement documentées
3. **Testez les requêtes** : Utilisez l'interface "Try it out" pour tester directement les endpoints
4. **Voyez les schémas** : Les propriétés et leurs types sont automatiquement détectés

### Fonctionnalités

- ✅ **Génération automatique** : La documentation est générée depuis vos annotations
- ✅ **Interface interactive** : Testez vos endpoints directement dans le navigateur
- ✅ **Schémas complets** : Types, descriptions, propriétés requises
- ✅ **Support des opérations** : GET, POST, PUT, PATCH, DELETE
- ✅ **Pagination** : Paramètres de pagination automatiquement documentés
- ✅ **Export JSON/YAML** : Récupérez la spec OpenAPI pour d'autres outils

## 🔍 Filtrage et tri

### Utilisation des filtres

Les filtres sont automatiquement appliqués depuis les query params :

```bash
# Recherche partielle
GET /api/products?name[partial]=laptop

# Filtre par plage
GET /api/products?price[gte]=100&price[lte]=500

# Filtre booléen
GET /api/products?active=true

# Filtre par date
GET /api/products?createdAt[after]=2025-01-01

# Tri
GET /api/products?order[price]=desc&order[name]=asc

# Combinaison avec pagination
GET /api/products?name[partial]=laptop&price[gte]=100&order[price]=desc&page=1&limit=20
```

### Stratégies de filtres

- **SearchFilter** : `exact`, `partial`, `start`, `end`, `word_start`
- **DateFilter** : `exact`, `before`, `after`
- **RangeFilter** : `gt`, `gte`, `lt`, `lte`, `between`
- **BooleanFilter** : `true`/`false`
- **OrderFilter** : `asc`/`desc`

## ✅ Validation

La validation est automatique lors de `create()` et `update()`. Les erreurs sont au format RFC 7807 :

```json
{
  "type": "https://example.com/problems/validation-error",
  "title": "Validation Error",
  "status": 422,
  "detail": "Les données fournies sont invalides",
  "violations": [
    {
      "property": "email",
      "message": "Le champ 'email' est requis"
    }
  ]
}
```

## 🔗 Relations et sous-ressources

### Embedding de relations

Pour inclure des relations dans la réponse, utilisez le paramètre `embed` :

```bash
# Inclure la relation category
GET /api/products?embed=category

# Inclure plusieurs relations
GET /api/products?embed=category,orderItems
```

### Définir une relation

```php
use JulienLinard\Doctrine\Mapping\ManyToOne;
use JulienLinard\Api\Annotation\ApiSubresource;

class Product
{
    // Relation ManyToOne
    #[ManyToOne(targetEntity: Category::class)]
    #[ApiSubresource(maxDepth: 1)]
    public ?Category $category = null;
    
    // Relation OneToMany
    #[OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    #[ApiSubresource(maxDepth: 1)]
    public array $orderItems = [];
}
```

### Sous-ressources

Accédez aux relations via des routes dédiées :

```bash
# Récupérer les orderItems d'un produit
GET /api/products/1/orderItems

# Récupérer un orderItem spécifique
GET /api/products/1/orderItems/5
```

## 🎯 Événements API

Le système d'événements est intégré avec `core-php` EventDispatcher :

```php
use JulienLinard\Core\Events\EventDispatcher;
use JulienLinard\Api\Event\ApiEvent;

$events = $app->getEvents();

// Écouter la création d'une ressource
$events->listen(ApiEvent::POST_CREATE, function(array $data) {
    $entity = $data['entity'];
    // Votre logique : log, notification, etc.
});

// Écouter la mise à jour
$events->listen(ApiEvent::PRE_UPDATE, function(array $data) {
    $entity = $data['entity'];
    $newData = $data['data'];
    // Vérifier permissions, valider, etc.
});
```

Événements disponibles :
- `api.pre_create` / `api.post_create`
- `api.pre_update` / `api.post_update`
- `api.pre_delete` / `api.post_delete`

## 🔧 Personnalisation

### Sérialisation personnalisée

```php
$serializer = new JsonSerializer();
$data = $serializer->serialize($user, ['read', 'admin']); // Groupes spécifiques
```

### Filtre personnalisé

```php
class CustomFilter implements FilterInterface
{
    public function apply(QueryBuilder $queryBuilder, string $property, mixed $value, string $alias = 'e'): void
    {
        // Votre logique de filtrage
    }
}

#[ApiFilter(CustomFilter::class, properties: ['customField'])]
class Product { }
```

### Gestion d'erreurs

```php
use JulienLinard\Api\Exception\ApiException;
use JulienLinard\Api\Exception\NotFoundException;
use JulienLinard\Api\Exception\ValidationException;

try {
    $user = $controller->show(123);
} catch (NotFoundException $e) {
    // 404 - Format Problem Details
} catch (ValidationException $e) {
    // 422 - Erreurs de validation
    $violations = $e->getViolations();
} catch (ApiException $e) {
    // Autre erreur API
}
```

## 📝 Licence

MIT
