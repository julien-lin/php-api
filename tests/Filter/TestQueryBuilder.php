<?php

declare(strict_types=1);

namespace JulienLinard\Api\Tests\Filter;

/**
 * Mock QueryBuilder pour les tests
 */
class TestQueryBuilder
{
    public array $where = [];
    public array $orderBy = [];
    public array $parameters = [];
    
    public function andWhere(string $condition): self
    {
        $this->where[] = $condition;
        return $this;
    }
    
    public function where(string $condition): self
    {
        $this->where[] = $condition;
        return $this;
    }
    
    public function setParameter(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;
        return $this;
    }
    
    public function addOrderBy(string $field, string $direction): self
    {
        $this->orderBy[] = [$field, $direction];
        return $this;
    }
    
    public function orderBy(string $field, string $direction): self
    {
        $this->orderBy[] = [$field, $direction];
        return $this;
    }
}
