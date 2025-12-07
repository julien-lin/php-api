<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

// QueryBuilder peut être de doctrine-php ou Doctrine DBAL

/**
 * Interface pour tous les filtres API
 */
interface FilterInterface
{
    /**
     * Applique le filtre au QueryBuilder
     * 
     * @param object $queryBuilder QueryBuilder (doctrine-php ou Doctrine DBAL)
     * @param string $property Nom de la propriété à filtrer
     * @param mixed $value Valeur du filtre
     * @param string $alias Alias de la table (ex: 'p')
     * @return void
     */
    public function apply(object $queryBuilder, string $property, mixed $value, string $alias = 'e'): void;
}
