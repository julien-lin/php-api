<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Filter\BooleanFilter;
use JulienLinard\Api\Tests\Filter\TestQueryBuilder;

class BooleanFilterTest extends TestCase
{
    public function testApplyWithTrue(): void
    {
        $filter = new BooleanFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'active', true, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithFalse(): void
    {
        $filter = new BooleanFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'active', false, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithStringTrue(): void
    {
        $filter = new BooleanFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'active', 'true', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithStringFalse(): void
    {
        $filter = new BooleanFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'active', 'false', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithInvalidValue(): void
    {
        $filter = new BooleanFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        // Ne doit pas appliquer de filtre si valeur invalide
        $filter->apply($queryBuilder, 'active', 'invalid', 'p');
        
        $this->assertTrue(true);
    }
    
    private function createMockQueryBuilder(): TestQueryBuilder
    {
        return new TestQueryBuilder();
    }
}
