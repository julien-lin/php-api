<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Controller\ApiController;
use JulienLinard\Api\Serializer\JsonSerializer;
use JulienLinard\Api\Exception\NotFoundException;

class TestEntity
{
    public ?int $id = null;
    public string $name = '';
    public string $email = '';

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

class TestApiController extends ApiController
{
    private array $entities = [];

    public function __construct()
    {
        parent::__construct(TestEntity::class, new JsonSerializer());
        
        // Données de test
        $this->entities = [
            1 => new TestEntity(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']),
            2 => new TestEntity(['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']),
        ];
    }

    protected function getAll(array $queryParams = []): array
    {
        return array_values($this->entities);
    }

    protected function getOne(int|string $id): ?object
    {
        return $this->entities[$id] ?? null;
    }

    protected function createEntity(array $data): object
    {
        $id = max(array_keys($this->entities)) + 1;
        $data['id'] = $id;
        return new TestEntity($data);
    }

    protected function save(object $entity): void
    {
        if ($entity->id !== null) {
            $this->entities[$entity->id] = $entity;
        }
    }

    protected function remove(object $entity): void
    {
        if ($entity->id !== null) {
            unset($this->entities[$entity->id]);
        }
    }
}

class ApiControllerTest extends TestCase
{
    private TestApiController $controller;

    protected function setUp(): void
    {
        $this->controller = new TestApiController();
    }

    public function testIndex(): void
    {
        $response = $this->controller->index();
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));
        
        $body = json_decode($response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('total', $body);
        $this->assertEquals(2, $body['total']);
    }

    public function testShow(): void
    {
        $response = $this->controller->show(1);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertEquals(1, $body['data']['id']);
        $this->assertEquals('John', $body['data']['name']);
    }

    public function testShowNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->controller->show(999);
    }

    public function testCreate(): void
    {
        $data = [
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ];
        
        $response = $this->controller->create($data);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotNull($body['data']['id']);
        $this->assertEquals('Bob', $body['data']['name']);
    }

    public function testUpdate(): void
    {
        $data = ['name' => 'John Updated'];
        
        $response = $this->controller->update(1, $data);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('John Updated', $body['data']['name']);
    }

    public function testUpdateNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->controller->update(999, ['name' => 'Test']);
    }

    public function testDelete(): void
    {
        $response = $this->controller->delete(2);
        
        $this->assertEquals(204, $response->getStatusCode());
        
        // Vérifier que l'entité a été supprimée
        $this->expectException(NotFoundException::class);
        $this->controller->show(2);
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->controller->delete(999);
    }
}
