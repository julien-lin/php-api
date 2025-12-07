# PHP API

Bibliothèque PHP pour créer des APIs REST automatiques, inspirée d'API Platform de Symfony.

## Fonctionnalités

- ✅ Annotations pour exposer des entités en API
- ✅ Sérialisation JSON automatique avec groupes
- ✅ Opérations CRUD automatiques
- ✅ Support des relations Doctrine
- ✅ Filtrage et pagination
- ✅ Validation des données
- ✅ Intégration avec le Core PHP existant

## Installation

```bash
composer require julienlinard/php-api
```

## Utilisation

### 1. Annoter une entité

```php
use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Doctrine\Mapping\Entity;
use JulienLinard\Doctrine\Mapping\Id;
use JulienLinard\Doctrine\Mapping\Column;

#[ApiResource(
    operations: ['GET', 'POST', 'PUT', 'DELETE'],
    routePrefix: '/api'
)]
#[Entity]
class User
{
    #[Id]
    #[Column(type: 'integer')]
    #[ApiProperty(groups: ['read', 'write'])]
    public ?int $id = null;

    #[Column(type: 'string', length: 255)]
    #[ApiProperty(groups: ['read', 'write'])]
    public string $email;

    #[Column(type: 'string', length: 255)]
    #[ApiProperty(groups: ['read'])] // Seulement en lecture
    public string $password;

    #[Column(type: 'string', length: 100)]
    #[ApiProperty(groups: ['read', 'write'])]
    public string $name;
}
```

### 2. Créer un contrôleur API

```php
use JulienLinard\Api\Controller\ApiController;
use JulienLinard\Api\Serializer\JsonSerializer;

class UserController extends ApiController
{
    public function __construct()
    {
        parent::__construct(User::class, new JsonSerializer());
    }
}
```

### 3. Définir les routes

```php
use JulienLinard\Router\Router;

$router = new Router();

// Routes automatiques pour l'API
$router->get('/api/users', [UserController::class, 'index']);
$router->get('/api/users/{id}', [UserController::class, 'show']);
$router->post('/api/users', [UserController::class, 'create']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->delete('/api/users/{id}', [UserController::class, 'delete']);
```

## Documentation

Voir [README.fr.md](README.fr.md) pour la documentation complète en français.
