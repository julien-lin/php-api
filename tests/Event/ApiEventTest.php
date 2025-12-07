<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Event;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Event\ApiEvent;

class TestEntity
{
    public ?int $id = null;
    public string $name = '';
}

class ApiEventTest extends TestCase
{
    public function testEventConstants(): void
    {
        $this->assertEquals('api.pre_create', ApiEvent::PRE_CREATE);
        $this->assertEquals('api.post_create', ApiEvent::POST_CREATE);
        $this->assertEquals('api.pre_update', ApiEvent::PRE_UPDATE);
        $this->assertEquals('api.post_update', ApiEvent::POST_UPDATE);
        $this->assertEquals('api.pre_delete', ApiEvent::PRE_DELETE);
        $this->assertEquals('api.post_delete', ApiEvent::POST_DELETE);
        $this->assertEquals('api.pre_read', ApiEvent::PRE_READ);
        $this->assertEquals('api.post_read', ApiEvent::POST_READ);
    }
    
    public function testEventConstructor(): void
    {
        $entity = new TestEntity();
        $entity->id = 1;
        $entity->name = 'Test';
        
        $event = new ApiEvent(
            ApiEvent::PRE_CREATE,
            $entity,
            ['data' => ['name' => 'Test']]
        );
        
        $this->assertEquals(ApiEvent::PRE_CREATE, $event->eventName);
        $this->assertSame($entity, $event->entity);
        $this->assertEquals(['data' => ['name' => 'Test']], $event->data);
    }
    
    public function testEventWithoutEntity(): void
    {
        $event = new ApiEvent(
            ApiEvent::PRE_CREATE,
            null,
            ['data' => ['name' => 'Test']]
        );
        
        $this->assertNull($event->entity);
        $this->assertEquals(['data' => ['name' => 'Test']], $event->data);
    }
    
    public function testEventWithEmptyData(): void
    {
        $entity = new TestEntity();
        
        $event = new ApiEvent(ApiEvent::POST_DELETE, $entity);
        
        $this->assertEquals([], $event->data);
    }
}
