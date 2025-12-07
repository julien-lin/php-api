<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

// QueryBuilder peut être de doctrine-php ou Doctrine DBAL

/**
 * Filtre de recherche textuelle
 * 
 * Supporte les stratégies :
 * - exact : Égalité exacte
 * - partial : LIKE %value%
 * - start : LIKE value%
 * - end : LIKE %value
 * - word_start : LIKE value% (début de mot)
 * 
 * Format : property[exact|partial|start|end|word_start]=value
 * Exemple : name[partial]=laptop
 */
class SearchFilter implements FilterInterface
{
    public const STRATEGY_EXACT = 'exact';
    public const STRATEGY_PARTIAL = 'partial';
    public const STRATEGY_START = 'start';
    public const STRATEGY_END = 'end';
    public const STRATEGY_WORD_START = 'word_start';
    
    private string $strategy;
    
    public function __construct(string $strategy = self::STRATEGY_PARTIAL)
    {
        $this->strategy = $strategy;
    }
    
    public function apply(object $queryBuilder, string $property, mixed $value, string $alias = 'e'): void
    {
        if (empty($value) || !is_string($value)) {
            return;
        }
        
        $paramName = ':' . $property . '_search';
        
        // Support à la fois andWhere (doctrine-php) et where (Doctrine DBAL)
        $whereMethod = method_exists($queryBuilder, 'andWhere') ? 'andWhere' : 'where';
        
        switch ($this->strategy) {
            case self::STRATEGY_EXACT:
                $queryBuilder->$whereMethod("{$alias}.{$property} = {$paramName}")
                            ->setParameter($paramName, $value);
                break;
                
            case self::STRATEGY_PARTIAL:
                $queryBuilder->$whereMethod("{$alias}.{$property} LIKE {$paramName}")
                            ->setParameter($paramName, '%' . $value . '%');
                break;
                
            case self::STRATEGY_START:
                $queryBuilder->$whereMethod("{$alias}.{$property} LIKE {$paramName}")
                            ->setParameter($paramName, $value . '%');
                break;
                
            case self::STRATEGY_END:
                $queryBuilder->$whereMethod("{$alias}.{$property} LIKE {$paramName}")
                            ->setParameter($paramName, '%' . $value);
                break;
                
            case self::STRATEGY_WORD_START:
                // Recherche par début de mot (espace avant ou début de chaîne)
                $queryBuilder->$whereMethod("({$alias}.{$property} LIKE {$paramName} OR {$alias}.{$property} LIKE {$paramName}2)")
                            ->setParameter($paramName, $value . '%')
                            ->setParameter($paramName . '2', '% ' . $value . '%');
                break;
                
            default:
                // Par défaut, utiliser partial
                $queryBuilder->$whereMethod("{$alias}.{$property} LIKE {$paramName}")
                            ->setParameter($paramName, '%' . $value . '%');
        }
    }
    
    /**
     * Applique une recherche avec stratégie depuis les query params
     * 
     * @param QueryBuilder $queryBuilder
     * @param string $property
     * @param array<string, string>|string $value Format: ['exact' => 'value'] ou 'value' (partial par défaut)
     * @param string $alias
     * @return void
     */
    public static function applyFromParams(object $queryBuilder, string $property, array|string $value, string $alias = 'e'): void
    {
        if (is_string($value)) {
            // Format simple : property=value (partial par défaut)
            $filter = new self(self::STRATEGY_PARTIAL);
            $filter->apply($queryBuilder, $property, $value, $alias);
        } elseif (is_array($value)) {
            // Format avec stratégie : property[strategy]=value
            foreach ($value as $strategy => $searchValue) {
                if (in_array($strategy, [self::STRATEGY_EXACT, self::STRATEGY_PARTIAL, self::STRATEGY_START, self::STRATEGY_END, self::STRATEGY_WORD_START], true)) {
                    $filter = new self($strategy);
                    $filter->apply($queryBuilder, $property, $searchValue, $alias);
                }
            }
        }
    }
}
