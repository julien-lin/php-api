<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

// QueryBuilder peut être de doctrine-php ou Doctrine DBAL

/**
 * Filtre booléen
 * 
 * Supporte : property=true|false|1|0
 * Exemple : active=true
 */
class BooleanFilter implements FilterInterface
{
    public function apply(object $queryBuilder, string $property, mixed $value, string $alias = 'e'): void
    {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        if ($boolValue === null) {
            return; // Valeur invalide, ignorer
        }
        
        $paramName = ':' . $property . '_bool';
        // Support à la fois andWhere (doctrine-php) et where (Doctrine DBAL)
        $whereMethod = method_exists($queryBuilder, 'andWhere') ? 'andWhere' : 'where';
        $queryBuilder->$whereMethod("{$alias}.{$property} = {$paramName}")
                    ->setParameter($paramName, $boolValue ? 1 : 0);
    }
}
