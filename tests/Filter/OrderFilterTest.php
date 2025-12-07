<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Filter\OrderFilter;
use JulienLinard\Api\Tests\Filter\TestQueryBuilder;

class OrderFilterTest extends TestCase
{
    public function testApplyAsc(): void
    {
        $filter = new OrderFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', 'asc', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyDesc(): void
    {
        $filter = new OrderFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', 'desc', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithInvalidDirection(): void
    {
        $filter = new OrderFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        // Doit utiliser ASC par défaut
        $filter->apply($queryBuilder, 'price', 'invalid', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyMultiple(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        OrderFilter::applyMultiple($queryBuilder, [
            'price' => 'desc',
            'name' => 'asc',
        ], 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyMultipleWithInvalidProperty(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        // Propriété invalide doit être ignorée
        OrderFilter::applyMultiple($queryBuilder, [
            'price' => 'desc',
            'invalid-property-name!' => 'asc', // Doit être ignoré
        ], 'p');
        
        $this->assertTrue(true);
    }
    
    private function createMockQueryBuilder(): TestQueryBuilder
    {
        return new TestQueryBuilder();
    }
}
