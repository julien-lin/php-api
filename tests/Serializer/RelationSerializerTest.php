<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Serializer\RelationSerializer;
use JulienLinard\Doctrine\Mapping\ManyToOne;
use JulienLinard\Doctrine\Mapping\OneToMany;
use JulienLinard\Api\Annotation\ApiSubresource;
use ReflectionClass;
use ReflectionProperty;

class RelationTestCategory
{
    public ?int $id = null;
    public string $name = '';
}

class RelationTestProduct
{
    public ?int $id = null;
    public string $name = '';
    
    #[ManyToOne(targetEntity: RelationTestCategory::class)]
    #[ApiSubresource(maxDepth: 1)]
    public ?RelationTestCategory $category = null;
    
    #[OneToMany(targetEntity: RelationTestOrderItem::class, mappedBy: 'product')]
    #[ApiSubresource(maxDepth: 2)]
    public array $orderItems = [];
}

class RelationTestOrderItem
{
    public ?int $id = null;
    public int $quantity = 0;
}

class RelationSerializerTest extends TestCase
{
    private RelationSerializer $serializer;
    
    protected function setUp(): void
    {
        $this->serializer = new RelationSerializer();
    }
    
    public function testIsRelationWithManyToOne(): void
    {
        $reflection = new ReflectionClass(RelationTestProduct::class);
        $property = $reflection->getProperty('category');
        
        $this->assertTrue($this->serializer->isRelation($property));
        $this->assertTrue($this->serializer->hasManyToOne($property));
        $this->assertFalse($this->serializer->hasOneToMany($property));
    }
    
    public function testIsRelationWithOneToMany(): void
    {
        $reflection = new ReflectionClass(RelationTestProduct::class);
        $property = $reflection->getProperty('orderItems');
        
        $this->assertTrue($this->serializer->isRelation($property));
        $this->assertFalse($this->serializer->hasManyToOne($property));
        $this->assertTrue($this->serializer->hasOneToMany($property));
    }
    
    public function testIsRelationWithNonRelation(): void
    {
        $reflection = new ReflectionClass(RelationTestProduct::class);
        $property = $reflection->getProperty('name');
        
        $this->assertFalse($this->serializer->isRelation($property));
    }
    
    public function testGetApiSubresource(): void
    {
        $reflection = new ReflectionClass(RelationTestProduct::class);
        $property = $reflection->getProperty('category');
        
        $subresource = $this->serializer->getApiSubresource($property);
        
        $this->assertInstanceOf(ApiSubresource::class, $subresource);
        $this->assertEquals(1, $subresource->maxDepth);
    }
    
    public function testGetManyToOne(): void
    {
        $reflection = new ReflectionClass(RelationTestProduct::class);
        $property = $reflection->getProperty('category');
        
        $manyToOne = $this->serializer->getManyToOne($property);
        
        $this->assertInstanceOf(ManyToOne::class, $manyToOne);
        $this->assertEquals(RelationTestCategory::class, $manyToOne->targetEntity);
    }
    
    public function testGetOneToMany(): void
    {
        $reflection = new ReflectionClass(RelationTestProduct::class);
        $property = $reflection->getProperty('orderItems');
        
        $oneToMany = $this->serializer->getOneToMany($property);
        
        $this->assertInstanceOf(OneToMany::class, $oneToMany);
        $this->assertEquals(RelationTestOrderItem::class, $oneToMany->targetEntity);
    }
    
    public function testSerializeRelationWithNull(): void
    {
        $result = $this->serializer->serializeRelation(null, ['read'], 0, 1);
        
        $this->assertNull($result);
    }
    
    public function testSerializeRelationWithMaxDepthReached(): void
    {
        $category = new RelationTestCategory();
        $category->id = 1;
        $category->name = 'Electronics';
        
        $result = $this->serializer->serializeRelation($category, ['read'], 0, 0);
        
        $this->assertEquals(1, $result);
    }
    
    public function testSerializeRelationWithObject(): void
    {
        $category = new RelationTestCategory();
        $category->id = 1;
        $category->name = 'Electronics';
        
        $result = $this->serializer->serializeRelation($category, ['read'], 0, 1);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Electronics', $result['name']);
    }
    
    public function testSerializeRelationWithCollection(): void
    {
        $item1 = new RelationTestOrderItem();
        $item1->id = 1;
        $item1->quantity = 2;
        
        $item2 = new RelationTestOrderItem();
        $item2->id = 2;
        $item2->quantity = 3;
        
        $collection = [$item1, $item2];
        
        $result = $this->serializer->serializeRelation($collection, ['read'], 0, 1);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[0]['quantity']);
    }
    
    public function testSerializeRelationWithCollectionMaxDepth(): void
    {
        $item1 = new RelationTestOrderItem();
        $item1->id = 1;
        
        $item2 = new RelationTestOrderItem();
        $item2->id = 2;
        
        $collection = [$item1, $item2];
        
        $result = $this->serializer->serializeRelation($collection, ['read'], 0, 0);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]);
        $this->assertEquals(2, $result[1]);
    }
    
    public function testExtractIdWithIdProperty(): void
    {
        $category = new RelationTestCategory();
        $category->id = 42;
        
        $reflection = new ReflectionClass($category);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        
        // Utiliser la réflexion pour accéder à extractId via serializeRelation avec maxDepth 0
        $result = $this->serializer->serializeRelation($category, [], 0, 0);
        
        $this->assertEquals(42, $result);
    }
    
    public function testExtractIdWithGetIdMethod(): void
    {
        $entity = new class {
            private int $id = 99;
            public function getId(): int { return $this->id; }
        };
        
        $result = $this->serializer->serializeRelation($entity, [], 0, 0);
        
        $this->assertEquals(99, $result);
    }
    
    public function testExtractIdWithoutId(): void
    {
        $entity = new class {
            public string $name = 'test';
        };
        
        $result = $this->serializer->serializeRelation($entity, [], 0, 0);
        
        $this->assertNull($result);
    }
}
