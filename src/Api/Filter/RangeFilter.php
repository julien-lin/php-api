<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

// QueryBuilder peut être de doctrine-php ou Doctrine DBAL

/**
 * Filtre pour les plages de valeurs (nombres, dates)
 * 
 * Supporte les stratégies :
 * - gt : Greater Than (>)
 * - gte : Greater Than or Equal (>=)
 * - lt : Less Than (<)
 * - lte : Less Than or Equal (<=)
 * - between : Entre deux valeurs
 * 
 * Format : property[gt|gte|lt|lte|between]=value
 * Exemple : price[gte]=100&price[lte]=500
 * Exemple : price[between]=100,500
 */
class RangeFilter implements FilterInterface
{
    public const STRATEGY_GT = 'gt';
    public const STRATEGY_GTE = 'gte';
    public const STRATEGY_LT = 'lt';
    public const STRATEGY_LTE = 'lte';
    public const STRATEGY_BETWEEN = 'between';
    
    private string $strategy;
    
    public function __construct(string $strategy = self::STRATEGY_GTE)
    {
        $this->strategy = $strategy;
    }
    
    public function apply(object $queryBuilder, string $property, mixed $value, string $alias = 'e'): void
    {
        if ($value === null || $value === '') {
            return;
        }
        
        $paramName = ':' . $property . '_range';
        
        // Support à la fois andWhere (doctrine-php) et where (Doctrine DBAL)
        $whereMethod = method_exists($queryBuilder, 'andWhere') ? 'andWhere' : 'where';
        
        switch ($this->strategy) {
            case self::STRATEGY_GT:
                $queryBuilder->$whereMethod("{$alias}.{$property} > {$paramName}")
                            ->setParameter($paramName, $this->castValue($value));
                break;
                
            case self::STRATEGY_GTE:
                $queryBuilder->$whereMethod("{$alias}.{$property} >= {$paramName}")
                            ->setParameter($paramName, $this->castValue($value));
                break;
                
            case self::STRATEGY_LT:
                $queryBuilder->$whereMethod("{$alias}.{$property} < {$paramName}")
                            ->setParameter($paramName, $this->castValue($value));
                break;
                
            case self::STRATEGY_LTE:
                $queryBuilder->$whereMethod("{$alias}.{$property} <= {$paramName}")
                            ->setParameter($paramName, $this->castValue($value));
                break;
                
            case self::STRATEGY_BETWEEN:
                // Format : value1,value2
                if (is_string($value) && str_contains($value, ',')) {
                    [$min, $max] = explode(',', $value, 2);
                    $minParam = $paramName . '_min';
                    $maxParam = $paramName . '_max';
                    $queryBuilder->$whereMethod("{$alias}.{$property} >= {$minParam} AND {$alias}.{$property} <= {$maxParam}")
                                ->setParameter($minParam, $this->castValue(trim($min)))
                                ->setParameter($maxParam, $this->castValue(trim($max)));
                }
                break;
                
            default:
                // Par défaut, gte
                $queryBuilder->$whereMethod("{$alias}.{$property} >= {$paramName}")
                            ->setParameter($paramName, $this->castValue($value));
        }
    }
    
    /**
     * Cast une valeur en nombre si possible
     */
    private function castValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return is_float($value) || (is_string($value) && str_contains($value, '.')) 
                ? (float)$value 
                : (int)$value;
        }
        
        return $value;
    }
    
    /**
     * Applique un filtre de plage depuis les query params
     */
    public static function applyFromParams(object $queryBuilder, string $property, array|string $value, string $alias = 'e'): void
    {
        if (is_string($value)) {
            // Format simple : property=value (gte par défaut)
            $filter = new self(self::STRATEGY_GTE);
            $filter->apply($queryBuilder, $property, $value, $alias);
        } elseif (is_array($value)) {
            // Format avec stratégie : property[strategy]=value
            foreach ($value as $strategy => $rangeValue) {
                if (in_array($strategy, [self::STRATEGY_GT, self::STRATEGY_GTE, self::STRATEGY_LT, self::STRATEGY_LTE, self::STRATEGY_BETWEEN], true)) {
                    $filter = new self($strategy);
                    $filter->apply($queryBuilder, $property, $rangeValue, $alias);
                }
            }
        }
    }
}
