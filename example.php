<?php

/**
 * Exemple d'utilisation de PHP API
 * 
 * Cet exemple montre comment créer une API REST complète
 * avec annotations et sérialisation automatique.
 */

require_once __DIR__ . '/vendor/autoload.php';

use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Api\Controller\ApiController;
use JulienLinard\Api\Serializer\JsonSerializer;

// Exemple d'entité avec annotations
#[ApiResource(
    operations: ['GET', 'POST', 'PUT', 'DELETE'],
    routePrefix: '/api',
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']]
)]
class User
{
    #[ApiProperty(groups: ['read', 'write'])]
    public ?int $id = null;

    #[ApiProperty(
        groups: ['read', 'write'],
        required: true,
        description: 'Email de l\'utilisateur'
    )]
    public string $email;

    #[ApiProperty(
        groups: ['write'], // Seulement en écriture (pas dans la réponse)
        required: true
    )]
    public string $password;

    #[ApiProperty(groups: ['read', 'write'])]
    public string $name;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

// Exemple de contrôleur API
class UserController extends ApiController
{
    private array $users = [];

    public function __construct()
    {
        parent::__construct(User::class, new JsonSerializer());
        
        // Données de démonstration
        $this->users = [
            1 => new User(['id' => 1, 'email' => 'john@example.com', 'name' => 'John Doe']),
            2 => new User(['id' => 2, 'email' => 'jane@example.com', 'name' => 'Jane Doe']),
        ];
    }

    protected function getAll(array $queryParams = []): array
    {
        return array_values($this->users);
    }

    protected function getOne(int|string $id): ?object
    {
        return $this->users[$id] ?? null;
    }

    protected function createEntity(array $data): object
    {
        $id = max(array_keys($this->users)) + 1;
        $data['id'] = $id;
        return new User($data);
    }

    protected function save(object $entity): void
    {
        if ($entity->id !== null) {
            $this->users[$entity->id] = $entity;
        }
    }

    protected function remove(object $entity): void
    {
        if ($entity->id !== null) {
            unset($this->users[$entity->id]);
        }
    }
}

// Exemple d'utilisation
echo "=== Exemple PHP API ===\n\n";

$controller = new UserController();

// Test sérialisation
$serializer = new JsonSerializer();
$user = new User([
    'id' => 1,
    'email' => 'test@example.com',
    'name' => 'Test User',
    'password' => 'secret'
]);

echo "1. Sérialisation avec groupe 'read':\n";
$data = $serializer->serialize($user, ['read']);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

echo "2. Sérialisation avec groupe 'write':\n";
$data = $serializer->serialize($user, ['write']);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

echo "3. Test contrôleur - Index:\n";
$response = $controller->index();
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getBody() . "\n\n";

echo "4. Test contrôleur - Show:\n";
$response = $controller->show(1);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getBody() . "\n\n";

echo "5. Test contrôleur - Create:\n";
$response = $controller->create([
    'email' => 'newuser@example.com',
    'password' => 'secret123',
    'name' => 'New User'
]);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getBody() . "\n\n";
