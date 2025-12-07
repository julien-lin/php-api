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
    public array $dispatchedEvents = [];
    
    public function __construct()
    {
        parent::__construct(TestEntity::class, new JsonSerializer(), new ApiValidator());
    }
    
    protected function getAll(array $queryParams = []): array
    {
        // Simuler la pagination en retournant data et total
        if (isset($queryParams['page']) || isset($queryParams['limit'])) {
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            $paginated = array_slice($this->entities, $offset, $limit);
            
            return [
                'data' => $paginated,
                'total' => count($this->entities),
            ];
        }
        
        return $this->entities;
    }
    
    protected function dispatchEvent(string $eventName, ?object $entity = null, array $data = []): void
    {
        // Capturer les événements pour les tests
        $this->dispatchedEvents[] = [
            'eventName' => $eventName,
            'entity' => $entity,
            'data' => $data,
        ];
        
        // Appeler la méthode parente si Application est disponible
        try {
            parent::dispatchEvent($eventName, $entity, $data);
        } catch (\Throwable $e) {
            // Ignorer si Application n'est pas disponible
        }
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
    
    public function testIndexWithPaginationMetadata(): void
    {
        // Créer plusieurs entités
        for ($i = 1; $i <= 25; $i++) {
            $this->controller->create(['name' => "Entity {$i}"]);
        }
        
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn(['page' => 1, 'limit' => 10]);
        
        $response = $this->controller->index($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $content = $response->getContent();
        $body = json_decode($content, true);
        
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('total', $body['meta']);
        $this->assertArrayHasKey('page', $body['meta']);
        $this->assertArrayHasKey('limit', $body['meta']);
        $this->assertArrayHasKey('totalPages', $body['meta']);
        $this->assertArrayHasKey('hasNextPage', $body['meta']);
        $this->assertArrayHasKey('hasPreviousPage', $body['meta']);
        
        $this->assertEquals(25, $body['meta']['total']);
        $this->assertEquals(1, $body['meta']['page']);
        $this->assertEquals(10, $body['meta']['limit']);
        $this->assertEquals(3, $body['meta']['totalPages']);
        $this->assertTrue($body['meta']['hasNextPage']);
        $this->assertFalse($body['meta']['hasPreviousPage']);
    }
    
    public function testIndexWithEmbedRelations(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getQueryParams')->willReturn(['embed' => 'category']);
        
        $response = $this->controller->index($request);
        
        $this->assertInstanceOf(Response::class, $response);
        // Le serializer devrait avoir les relations embed configurées
    }
    
    public function testShowWithEmbedRelations(): void
    {
        $this->controller->create(['name' => 'Test']);
        
        $request = $this->createMock(Request::class);
        $request->method('getRouteParam')->willReturn('1');
        $request->method('getQueryParams')->willReturn(['embed' => 'category']);
        
        $response = $this->controller->show($request);
        
        $this->assertInstanceOf(Response::class, $response);
    }
    
    public function testCreateDispatchesEvents(): void
    {
        $this->controller->create(['name' => 'Test']);
        
        $this->assertCount(2, $this->controller->dispatchedEvents);
        $this->assertEquals('api.pre_create', $this->controller->dispatchedEvents[0]['eventName']);
        $this->assertEquals('api.post_create', $this->controller->dispatchedEvents[1]['eventName']);
        $this->assertNull($this->controller->dispatchedEvents[0]['entity']); // pre_create n'a pas encore l'entité
        $this->assertNotNull($this->controller->dispatchedEvents[1]['entity']); // post_create a l'entité
    }
    
    public function testUpdateDispatchesEvents(): void
    {
        $this->controller->create(['name' => 'Test']);
        
        $this->controller->dispatchedEvents = []; // Reset
        
        $this->controller->update(1, ['name' => 'Updated']);
        
        $this->assertCount(2, $this->controller->dispatchedEvents);
        $this->assertEquals('api.pre_update', $this->controller->dispatchedEvents[0]['eventName']);
        $this->assertEquals('api.post_update', $this->controller->dispatchedEvents[1]['eventName']);
        $this->assertNotNull($this->controller->dispatchedEvents[0]['entity']);
        $this->assertArrayHasKey('data', $this->controller->dispatchedEvents[0]['data']);
    }
    
    public function testDeleteDispatchesEvents(): void
    {
        $this->controller->create(['name' => 'Test']);
        
        $this->controller->dispatchedEvents = []; // Reset
        
        $this->controller->delete(1);
        
        $this->assertCount(2, $this->controller->dispatchedEvents);
        $this->assertEquals('api.pre_delete', $this->controller->dispatchedEvents[0]['eventName']);
        $this->assertEquals('api.post_delete', $this->controller->dispatchedEvents[1]['eventName']);
        $this->assertNotNull($this->controller->dispatchedEvents[0]['entity']);
    }
}
