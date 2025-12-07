<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

use ReflectionClass;
use JulienLinard\Api\Filter\ApiFilter;
use JulienLinard\Api\Filter\SearchFilter;
use JulienLinard\Api\Filter\DateFilter;
use JulienLinard\Api\Filter\RangeFilter;
use JulienLinard\Api\Filter\BooleanFilter;
use JulienLinard\Api\Filter\OrderFilter;

/**
 * Gestionnaire de filtres pour les entités API
 * 
 * Applique automatiquement les filtres définis via ApiFilter aux QueryBuilder
 */
class FilterManager
{
    private array $filters = [];
    
    /**
     * Enregistre les filtres d'une entité
     */
    public function registerEntityFilters(string $entityClass): void
    {
        $reflection = new ReflectionClass($entityClass);
        $attributes = $reflection->getAttributes(ApiFilter::class);
        
        foreach ($attributes as $attribute) {
            $apiFilter = $attribute->newInstance();
            $this->filters[$entityClass][] = $apiFilter;
        }
    }
    
    /**
     * Applique les filtres au QueryBuilder depuis les query params
     * 
     * @param object $queryBuilder QueryBuilder (doctrine-php ou Doctrine DBAL)
     * @param string $entityClass
     * @param array<string, mixed> $queryParams
     * @param string $alias Alias de la table (ex: 'p')
     * @return void
     */
    public function applyFilters(object $queryBuilder, string $entityClass, array $queryParams, string $alias = 'e'): void
    {
        // Enregistrer les filtres si pas déjà fait
        if (!isset($this->filters[$entityClass])) {
            $this->registerEntityFilters($entityClass);
        }
        
        // Extraire le paramètre 'order' (tri)
        $orderParams = $queryParams['order'] ?? [];
        if (!empty($orderParams) && is_array($orderParams)) {
            OrderFilter::applyMultiple($queryBuilder, $orderParams, $alias);
            unset($queryParams['order']);
        }
        
        // Appliquer les filtres enregistrés
        if (!isset($this->filters[$entityClass])) {
            return;
        }
        
        foreach ($this->filters[$entityClass] as $apiFilter) {
            $filterClass = $apiFilter->filterClass;
            
            // Vérifier que la classe existe
            if (!class_exists($filterClass)) {
                continue;
            }
            
            // Appliquer le filtre pour chaque propriété
            foreach ($apiFilter->properties as $property) {
                if (!isset($queryParams[$property])) {
                    continue;
                }
                
                $value = $queryParams[$property];
                
                // Appliquer selon le type de filtre
                if ($filterClass === SearchFilter::class) {
                    SearchFilter::applyFromParams($queryBuilder, $property, $value, $alias);
                } elseif ($filterClass === DateFilter::class) {
                    DateFilter::applyFromParams($queryBuilder, $property, $value, $alias);
                } elseif ($filterClass === RangeFilter::class) {
                    RangeFilter::applyFromParams($queryBuilder, $property, $value, $alias);
                } elseif ($filterClass === BooleanFilter::class) {
                    $filter = new BooleanFilter();
                    $filter->apply($queryBuilder, $property, $value, $alias);
                } elseif ($filterClass === OrderFilter::class) {
                    // OrderFilter est géré séparément
                    continue;
                } elseif (is_subclass_of($filterClass, FilterInterface::class)) {
                    // Filtre personnalisé
                    $filter = new $filterClass(...($apiFilter->options ?? []));
                    if ($filter instanceof FilterInterface) {
                        $filter->apply($queryBuilder, $property, $value, $alias);
                    }
                }
            }
        }
    }
    
    /**
     * Récupère les filtres enregistrés pour une entité
     */
    public function getEntityFilters(string $entityClass): array
    {
        if (!isset($this->filters[$entityClass])) {
            $this->registerEntityFilters($entityClass);
        }
        
        return $this->filters[$entityClass] ?? [];
    }
}
