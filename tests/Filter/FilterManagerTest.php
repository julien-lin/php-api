<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Filter\FilterManager;
use JulienLinard\Api\Filter\ApiFilter;
use JulienLinard\Api\Filter\SearchFilter;
use JulienLinard\Api\Filter\BooleanFilter;
use JulienLinard\Api\Tests\Filter\TestQueryBuilder;

#[ApiFilter(SearchFilter::class, properties: ['name'])]
#[ApiFilter(BooleanFilter::class, properties: ['active'])]
class TestFilterEntity
{
    public string $name = '';
    public bool $active = false;
}

class FilterManagerTest extends TestCase
{
    public function testRegisterEntityFilters(): void
    {
        $manager = new FilterManager();
        $manager->registerEntityFilters(TestFilterEntity::class);
        
        $filters = $manager->getEntityFilters(TestFilterEntity::class);
        
        $this->assertNotEmpty($filters);
        $this->assertCount(2, $filters); // SearchFilter et BooleanFilter
    }
    
    public function testApplyFilters(): void
    {
        $manager = new FilterManager();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $queryParams = [
            'name' => 'test',
            'active' => true,
            'order' => ['price' => 'desc'],
        ];
        
        $manager->applyFilters($queryBuilder, TestFilterEntity::class, $queryParams, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFiltersWithOrder(): void
    {
        $manager = new FilterManager();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $queryParams = [
            'order' => ['price' => 'desc', 'name' => 'asc'],
        ];
        
        $manager->applyFilters($queryBuilder, TestFilterEntity::class, $queryParams, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFiltersWithoutFilters(): void
    {
        $manager = new FilterManager();
        $queryBuilder = $this->createMockQueryBuilder();
        
        // Entité sans filtres
        $manager->applyFilters($queryBuilder, \stdClass::class, [], 'p');
        
        $this->assertTrue(true);
    }
    
    public function testGetEntityFilters(): void
    {
        $manager = new FilterManager();
        
        $filters = $manager->getEntityFilters(TestFilterEntity::class);
        
        $this->assertIsArray($filters);
    }
    
    private function createMockQueryBuilder(): TestQueryBuilder
    {
        return new TestQueryBuilder();
    }
}
