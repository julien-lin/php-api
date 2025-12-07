<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Swagger\SwaggerGenerator;
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
class TestEntity
{
    #[Id]
    #[Column(type: 'integer', autoIncrement: true)]
    #[ApiProperty(groups: ['read'])]
    public ?int $id = null;

    #[Column(type: 'string', length: 255)]
    #[ApiProperty(groups: ['read', 'write'], required: true)]
    public string $name = '';

    #[Column(type: 'integer')]
    #[ApiProperty(groups: ['read', 'write'])]
    public int $count = 0;
}

class SwaggerGeneratorTest extends TestCase
{
    public function testGenerateSwaggerSpec(): void
    {
        $generator = new SwaggerGenerator();
        $spec = $generator->generate(
            [TestEntity::class],
            'Test API',
            '1.0.0',
            '/api'
        );

        $this->assertArrayHasKey('openapi', $spec);
        $this->assertEquals('3.0.0', $spec['openapi']);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
    }

    public function testSwaggerSpecContainsPaths(): void
    {
        $generator = new SwaggerGenerator();
        $spec = $generator->generate(
            [TestEntity::class],
            'Test API',
            '1.0.0',
            '/api'
        );

        $this->assertArrayHasKey('/testentity', $spec['paths']);
        $this->assertArrayHasKey('/testentity/{id}', $spec['paths']);
    }

    public function testSwaggerSpecContainsSchemas(): void
    {
        $generator = new SwaggerGenerator();
        $spec = $generator->generate(
            [TestEntity::class],
            'Test API',
            '1.0.0',
            '/api'
        );

        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertArrayHasKey('TestEntity', $spec['components']['schemas']);
        $this->assertArrayHasKey('properties', $spec['components']['schemas']['TestEntity']);
    }
}
