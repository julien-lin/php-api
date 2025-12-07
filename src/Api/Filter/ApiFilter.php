<?php

declare(strict_types=1);

namespace JulienLinard\Api\Filter;

use Attribute;

/**
 * Annotation pour définir un filtre sur une entité
 * 
 * @example
 * #[ApiFilter(SearchFilter::class, properties: ['name', 'description'])]
 * #[ApiFilter(DateFilter::class, properties: ['createdAt'])]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ApiFilter
{
    /**
     * @param string $filterClass Classe du filtre à utiliser
     * @param array<string> $properties Propriétés sur lesquelles appliquer le filtre
     * @param array<string, mixed> $options Options supplémentaires pour le filtre
     */
    public function __construct(
        public string $filterClass,
        public array $properties = [],
        public array $options = []
    ) {}
}
