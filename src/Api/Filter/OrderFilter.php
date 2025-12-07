<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

// QueryBuilder peut être de doctrine-php ou Doctrine DBAL

/**
 * Filtre pour le tri (ORDER BY)
 * 
 * Supporte le format : order[property]=asc|desc
 * Exemple : order[price]=desc&order[name]=asc
 */
class OrderFilter implements FilterInterface
{
    public function apply(object $queryBuilder, string $property, mixed $value, string $alias = 'e'): void
    {
        $direction = strtoupper((string)$value);
        
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC'; // Par défaut
        }
        
        // Support à la fois addOrderBy (Doctrine DBAL) et orderBy (doctrine-php)
        if (method_exists($queryBuilder, 'addOrderBy')) {
            // Doctrine DBAL
            $queryBuilder->addOrderBy("{$alias}.{$property}", $direction);
        } elseif (method_exists($queryBuilder, 'orderBy')) {
            // doctrine-php
            $queryBuilder->orderBy("{$alias}.{$property}", $direction);
        }
    }
    
    /**
     * Applique plusieurs tris depuis les query params
     * 
     * @param QueryBuilder $queryBuilder
     * @param array<string, string> $orderParams Format: ['property' => 'asc|desc']
     * @param string $alias
     * @return void
     */
    public static function applyMultiple(object $queryBuilder, array $orderParams, string $alias = 'e'): void
    {
        $filter = new self();
        
        foreach ($orderParams as $property => $direction) {
            // Valider que la propriété est un identifiant valide
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $property)) {
                continue; // Ignorer les propriétés invalides
            }
            
            $filter->apply($queryBuilder, $property, $direction, $alias);
        }
    }
}
