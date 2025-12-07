<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Controller;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Controller\ApiController;
use JulienLinard\Api\Serializer\JsonSerializer;
use JulienLinard\Api\Validator\ApiValidator;
use JulienLinard\Api\Exception\NotFoundException;
use JulienLinard\Api\Exception\ValidationException;
use JulienLinard\Router\Request;
use JulienLinard\Router\Response;
use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Annotation\ApiProperty;

#[ApiResource]
class TestEntity
{
    public ?int $id = null;
    
    #[ApiProperty(required: true, groups: ['write', 'Default'])]
    public string $name = '';
}

class TestApiController extends ApiController
{
    private array $entities = [];
    private int $nextId = 1;
    
    public function __construct()
    {
        parent::__construct(TestEntity::class, new JsonSerializer(), new ApiValidator());
    }
    
    protected function getAll(array $queryParams = []): array
    {
        return $this->entities;
    }
    
    protected function getOne(int|string $id): ?object
    {
        foreach ($this->entities as $entity) {
            if ($entity->id === (int)$id) {
                return $entity;
            }
        }
        return null;
    }
    
    protected function createEntity(array $data): object
    {
        $entity = new TestEntity();
        $entity->id = $this->nextId++;
        $entity->name = $data['name'] ?? '';
        return $entity;
    }
    
    protected function save(object $entity): void
    {
        $this->entities[] = $entity;
    }
    
    protected function remove(object $entity): void
    {
        $this->entities = array_filter($this->entities, fn($e) => $e !== $entity);
    }
}

class ApiControllerTest extends TestCase
{
    private TestApiController $controller;
    
    protected function setUp(): void
    {
        $this->controller = new TestApiController();
    }
    
    public function testIndexWithArray(): void
    {
        $response = $this->controller->index([]);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testIndexWithRequest(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn(['page' => 1]);
        
        $response = $this->controller->index($request);
        
        $this->assertInstanceOf(Response::class, $response);
    }
    
    public function testShowWithId(): void
    {
        // Créer une entité d'abord
        $this->controller->create(['name' => 'Test']);
        
        $response = $this->controller->show(1);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testShowNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        
        $this->controller->show(999);
    }
    
    public function testCreateWithArray(): void
    {
        $data = ['name' => 'New Entity'];
        
        $response = $this->controller->create($data);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }
    
    public function testCreateWithRequest(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn(['name' => 'New Entity']);
        $request->method('getRawBody')->willReturn('');
        
        $response = $this->controller->create($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }
    
    public function testCreateValidationError(): void
    {
        // name est requis mais manquant - doit lever ValidationException
        // Mais comme empty([]) est true, on aura d'abord InvalidArgumentException
        // Pour tester la validation, on doit passer des données non vides mais invalides
        try {
            $this->controller->create(['invalid' => 'data']); // name manquant
            $this->fail('ValidationException attendue');
        } catch (ValidationException $e) {
            $this->assertEquals(422, $e->getStatusCode());
            $this->assertNotEmpty($e->getViolations());
        } catch (\Throwable $e) {
            // Si c'est une ApiException qui wrap la ValidationException
            if ($e->getPrevious() instanceof ValidationException) {
                $this->assertInstanceOf(ValidationException::class, $e->getPrevious());
            } else {
                // Vérifier que c'est bien une erreur de validation
                $this->assertStringContainsString('requis', $e->getMessage());
            }
        }
    }
    
    public function testUpdateWithIdAndData(): void
    {
        // Créer une entité d'abord
        $this->controller->create(['name' => 'Test']);
        
        $response = $this->controller->update(1, ['name' => 'Updated']);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testUpdateNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        
        $this->controller->update(999, ['name' => 'Updated']);
    }
    
    public function testDelete(): void
    {
        // Créer une entité d'abord
        $this->controller->create(['name' => 'Test']);
        
        $response = $this->controller->delete(1);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }
    
    public function testDeleteNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        
        $this->controller->delete(999);
    }
    
    public function testErrorResponse(): void
    {
        // Utiliser la réflexion pour appeler la méthode protected
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);
        
        $exception = new NotFoundException('Not found');
        $response = $method->invoke($this->controller, $exception);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        
        $content = $response->getContent();
        $body = json_decode($content, true);
        $this->assertArrayHasKey('type', $body);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('status', $body);
    }
}
