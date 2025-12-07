# PHP API

Bibliothèque PHP pour créer des APIs REST automatiques, inspirée d'API Platform de Symfony.

## 🎯 Fonctionnalités

- ✅ **Annotations pour exposer des entités** : Utilisez `#[ApiResource]` sur vos entités
- ✅ **Sérialisation JSON automatique** : Avec groupes de sérialisation (`read`, `write`)
- ✅ **Opérations CRUD automatiques** : GET, POST, PUT, DELETE prêts à l'emploi
- ✅ **Support des relations Doctrine** : Relations ManyToOne, OneToMany, etc.
- ✅ **Filtrage et pagination** : Support des paramètres de requête
- ✅ **Validation des données** : Intégration avec le validateur
- ✅ **Intégration Core PHP** : Utilise le système de contrôleurs existant

## 📦 Installation

```bash
composer require julienlinard/php-api
```

## 🚀 Utilisation

### 1. Créer une entité avec annotations

```php
<?php

use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Doctrine\Mapping\Entity;
use JulienLinard\Doctrine\Mapping\Id;
use JulienLinard\Doctrine\Mapping\Column;

#[ApiResource(
    operations: ['GET', 'POST', 'PUT', 'DELETE'],
    routePrefix: '/api',
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']]
)]
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
  "total": 1
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

Configure la sérialisation d'une propriété.

```php
#[ApiProperty(
    groups: ['read', 'write'],    // Groupes de sérialisation
    readable: true,               // Lisible via l'API
    writable: true,               // Modifiable via l'API
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

## 🔧 Personnalisation

### Sérialisation personnalisée

```php
$serializer = new JsonSerializer();
$data = $serializer->serialize($user, ['read', 'admin']); // Groupes spécifiques
```

### Gestion d'erreurs

```php
use JulienLinard\Api\Exception\ApiException;
use JulienLinard\Api\Exception\NotFoundException;

try {
    $user = $controller->show(123);
} catch (NotFoundException $e) {
    // 404
} catch (ApiException $e) {
    // Autre erreur API
}
```

## 📝 Licence

MIT
