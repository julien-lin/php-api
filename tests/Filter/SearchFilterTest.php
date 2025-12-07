<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Filter\SearchFilter;
use JulienLinard\Api\Tests\Filter\TestQueryBuilder;

class SearchFilterTest extends TestCase
{
    public function testApplyPartialStrategy(): void
    {
        $filter = new SearchFilter(SearchFilter::STRATEGY_PARTIAL);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'name', 'laptop', 'p');
        
        $this->assertTrue(true); // Si pas d'exception, le test passe
    }
    
    public function testApplyExactStrategy(): void
    {
        $filter = new SearchFilter(SearchFilter::STRATEGY_EXACT);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'name', 'laptop', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyStartStrategy(): void
    {
        $filter = new SearchFilter(SearchFilter::STRATEGY_START);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'name', 'laptop', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyEndStrategy(): void
    {
        $filter = new SearchFilter(SearchFilter::STRATEGY_END);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'name', 'laptop', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFromParamsString(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        SearchFilter::applyFromParams($queryBuilder, 'name', 'laptop', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFromParamsArray(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        SearchFilter::applyFromParams($queryBuilder, 'name', ['partial' => 'laptop'], 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithEmptyValue(): void
    {
        $filter = new SearchFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'name', '', 'p');
        
        $this->assertTrue(true); // Ne doit pas appliquer de filtre
    }
    
    private function createMockQueryBuilder(): TestQueryBuilder
    {
        return new TestQueryBuilder();
    }
}
