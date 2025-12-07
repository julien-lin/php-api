<?php

declare(strict_types=1);

namespace JulienLinard\Api\Annotation;

use Attribute;

/**
 * Annotation pour exposer une relation comme sous-ressource
 * 
 * Permet d'accéder aux relations via des routes dédiées :
 * GET /api/products/{id}/orders
 * GET /api/products/{id}/orders/{orderId}
 * 
 * @example
 * #[ApiSubresource(maxDepth: 1)]
 * public Collection $orders;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiSubresource
{
    /**
     * @param int $maxDepth Profondeur maximale de sérialisation (évite les boucles infinies)
     * @param array<string> $operations Opérations disponibles (GET, POST, etc.)
     * @param array<string> $groups Groupes de sérialisation pour cette sous-ressource
     */
    public function __construct(
        public int $maxDepth = 1,
        public array $operations = ['GET'],
        public array $groups = ['read']
    ) {}
}
