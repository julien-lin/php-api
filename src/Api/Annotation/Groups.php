<?php

declare(strict_types=1);

namespace JulienLinard\Api\Annotation;

use Attribute;

/**
 * Annotation pour définir les groupes de sérialisation
 * 
 * Alternative à ApiProperty pour une syntaxe plus simple
 * 
 * @example
 * #[Groups(['read', 'write'])]
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Groups
{
    /**
     * @param array<string> $groups Groupes de sérialisation
     */
    public function __construct(
        public array $groups
    ) {}
}
