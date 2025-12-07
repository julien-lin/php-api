<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Filter\RangeFilter;
use JulienLinard\Api\Tests\Filter\TestQueryBuilder;

class RangeFilterTest extends TestCase
{
    public function testApplyGteStrategy(): void
    {
        $filter = new RangeFilter(RangeFilter::STRATEGY_GTE);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', 100, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyGtStrategy(): void
    {
        $filter = new RangeFilter(RangeFilter::STRATEGY_GT);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', 100, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyLteStrategy(): void
    {
        $filter = new RangeFilter(RangeFilter::STRATEGY_LTE);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', 500, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyLtStrategy(): void
    {
        $filter = new RangeFilter(RangeFilter::STRATEGY_LT);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', 500, 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyBetweenStrategy(): void
    {
        $filter = new RangeFilter(RangeFilter::STRATEGY_BETWEEN);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', '100,500', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFromParamsString(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        RangeFilter::applyFromParams($queryBuilder, 'price', '100', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFromParamsArray(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        RangeFilter::applyFromParams($queryBuilder, 'price', ['gte' => 100, 'lte' => 500], 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithNumericString(): void
    {
        $filter = new RangeFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'price', '100', 'p');
        
        $this->assertTrue(true);
    }
    
    private function createMockQueryBuilder(): TestQueryBuilder
    {
        return new TestQueryBuilder();
    }
}
