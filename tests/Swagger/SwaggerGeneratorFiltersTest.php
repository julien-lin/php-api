<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Swagger;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Swagger\SwaggerGenerator;
use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Filter\ApiFilter;
use JulienLinard\Api\Filter\SearchFilter;
use JulienLinard\Api\Filter\BooleanFilter;
use JulienLinard\Api\Filter\RangeFilter;
use JulienLinard\Api\Filter\DateFilter;

#[ApiResource(
    operations: ['GET'],
    shortName: 'testproducts'
)]
#[ApiFilter(SearchFilter::class, properties: ['name'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
class TestProductEntity
{
    public string $name = '';
    public bool $active = false;
    public float $price = 0.0;
    public \DateTime $createdAt;
}

class SwaggerGeneratorFiltersTest extends TestCase
{
    public function testGenerateWithFilters(): void
    {
        $generator = new SwaggerGenerator();
        $spec = $generator->generate(
            [TestProductEntity::class],
            'Test API',
            '1.0.0',
            '/api'
        );
        
        $this->assertArrayHasKey('/testproducts', $spec['paths']);
        $getOperation = $spec['paths']['/testproducts']['get'];
        
        // Vérifier que les paramètres de filtres sont présents
        $parameters = $getOperation['parameters'];
        $parameterNames = array_column($parameters, 'name');
        
        $this->assertContains('name', $parameterNames);
        $this->assertContains('active', $parameterNames);
        $this->assertContains('price', $parameterNames);
        $this->assertContains('createdAt', $parameterNames);
        $this->assertContains('order', $parameterNames);
    }
    
    public function testFilterParametersStructure(): void
    {
        $generator = new SwaggerGenerator();
        $spec = $generator->generate(
            [TestProductEntity::class],
            'Test API',
            '1.0.0',
            '/api'
        );
        
        $getOperation = $spec['paths']['/testproducts']['get'];
        $parameters = $getOperation['parameters'];
        
        // Trouver le paramètre 'name' (SearchFilter)
        $nameParam = null;
        foreach ($parameters as $param) {
            if ($param['name'] === 'name') {
                $nameParam = $param;
                break;
            }
        }
        
        $this->assertNotNull($nameParam);
        $this->assertEquals('query', $nameParam['in']);
        $this->assertEquals('deepObject', $nameParam['style']);
    }
    
    public function testOrderParameter(): void
    {
        $generator = new SwaggerGenerator();
        $spec = $generator->generate(
            [TestProductEntity::class],
            'Test API',
            '1.0.0',
            '/api'
        );
        
        $getOperation = $spec['paths']['/testproducts']['get'];
        $parameters = $getOperation['parameters'];
        
        $orderParam = null;
        foreach ($parameters as $param) {
            if ($param['name'] === 'order') {
                $orderParam = $param;
                break;
            }
        }
        
        $this->assertNotNull($orderParam);
        $this->assertEquals('query', $orderParam['in']);
        $this->assertArrayHasKey('schema', $orderParam);
        $this->assertEquals('object', $orderParam['schema']['type']);
    }
}
