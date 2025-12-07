<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

// QueryBuilder peut être de doctrine-php ou Doctrine DBAL

/**
 * Filtre pour les dates
 * 
 * Supporte les stratégies :
 * - exact : Date exacte
 * - before : Avant la date
 * - after : Après la date
 * 
 * Format : property[exact|before|after]=YYYY-MM-DD
 * Exemple : createdAt[after]=2025-01-01
 */
class DateFilter implements FilterInterface
{
    public const STRATEGY_EXACT = 'exact';
    public const STRATEGY_BEFORE = 'before';
    public const STRATEGY_AFTER = 'after';
    
    private string $strategy;
    
    public function __construct(string $strategy = self::STRATEGY_EXACT)
    {
        $this->strategy = $strategy;
    }
    
    public function apply(object $queryBuilder, string $property, mixed $value, string $alias = 'e'): void
    {
        if (empty($value)) {
            return;
        }
        
        // Parser la date
        $date = $this->parseDate($value);
        if ($date === null) {
            return; // Date invalide
        }
        
        $paramName = ':' . $property . '_date';
        
        // Support à la fois andWhere (doctrine-php) et where (Doctrine DBAL)
        $whereMethod = method_exists($queryBuilder, 'andWhere') ? 'andWhere' : 'where';
        
        switch ($this->strategy) {
            case self::STRATEGY_EXACT:
                // Comparaison de date (sans heure)
                $queryBuilder->$whereMethod("DATE({$alias}.{$property}) = DATE({$paramName})")
                            ->setParameter($paramName, $date->format('Y-m-d'));
                break;
                
            case self::STRATEGY_BEFORE:
                $queryBuilder->$whereMethod("{$alias}.{$property} < {$paramName}")
                            ->setParameter($paramName, $date->format('Y-m-d 23:59:59'));
                break;
                
            case self::STRATEGY_AFTER:
                $queryBuilder->$whereMethod("{$alias}.{$property} > {$paramName}")
                            ->setParameter($paramName, $date->format('Y-m-d 00:00:00'));
                break;
                
            default:
                // Par défaut, exact
                $queryBuilder->$whereMethod("DATE({$alias}.{$property}) = DATE({$paramName})")
                            ->setParameter($paramName, $date->format('Y-m-d'));
        }
    }
    
    /**
     * Parse une valeur en DateTime
     */
    private function parseDate(mixed $value): ?\DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }
        
        if (is_string($value)) {
            try {
                return new \DateTime($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Applique un filtre de date depuis les query params
     */
    public static function applyFromParams(object $queryBuilder, string $property, array|string $value, string $alias = 'e'): void
    {
        if (is_string($value)) {
            // Format simple : property=YYYY-MM-DD (exact par défaut)
            $filter = new self(self::STRATEGY_EXACT);
            $filter->apply($queryBuilder, $property, $value, $alias);
        } elseif (is_array($value)) {
            // Format avec stratégie : property[strategy]=YYYY-MM-DD
            foreach ($value as $strategy => $dateValue) {
                if (in_array($strategy, [self::STRATEGY_EXACT, self::STRATEGY_BEFORE, self::STRATEGY_AFTER], true)) {
                    $filter = new self($strategy);
                    $filter->apply($queryBuilder, $property, $dateValue, $alias);
                }
            }
        }
    }
}
