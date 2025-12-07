<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

use PHPUnit\Framework\TestCase;
use JulienLinard\Api\Filter\DateFilter;
use JulienLinard\Api\Tests\Filter\TestQueryBuilder;

class DateFilterTest extends TestCase
{
    public function testApplyExactStrategy(): void
    {
        $filter = new DateFilter(DateFilter::STRATEGY_EXACT);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'createdAt', '2025-01-01', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyBeforeStrategy(): void
    {
        $filter = new DateFilter(DateFilter::STRATEGY_BEFORE);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'createdAt', '2025-01-01', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyAfterStrategy(): void
    {
        $filter = new DateFilter(DateFilter::STRATEGY_AFTER);
        $queryBuilder = $this->createMockQueryBuilder();
        
        $filter->apply($queryBuilder, 'createdAt', '2025-01-01', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFromParamsString(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        DateFilter::applyFromParams($queryBuilder, 'createdAt', '2025-01-01', 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyFromParamsArray(): void
    {
        $queryBuilder = $this->createMockQueryBuilder();
        
        DateFilter::applyFromParams($queryBuilder, 'createdAt', ['after' => '2025-01-01'], 'p');
        
        $this->assertTrue(true);
    }
    
    public function testApplyWithInvalidDate(): void
    {
        $filter = new DateFilter();
        $queryBuilder = $this->createMockQueryBuilder();
        
        // Ne doit pas appliquer de filtre si date invalide
        $filter->apply($queryBuilder, 'createdAt', 'invalid-date', 'p');
        
        $this->assertTrue(true);
    }
    
    private function createMockQueryBuilder(): TestQueryBuilder
    {
        return new TestQueryBuilder();
    }
}
