<?php

declare(strict_types=1);

namespace JulienLinard\Api\Annotation;

use Attribute;

/**
 * Annotation pour exposer une entité en API
 * 
 * Inspirée d'API Platform de Symfony
 * 
 * @example
 * #[ApiResource(
 *     operations: ['GET', 'POST', 'PUT', 'DELETE'],
 *     routePrefix: '/api',
 *     normalizationContext: ['groups' => ['read']],
 *     denormalizationContext: ['groups' => ['write']]
 * )]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ApiResource
{
    /**
     * @param array<string> $operations Opérations disponibles (GET, POST, PUT, DELETE, PATCH)
     * @param string|null $routePrefix Préfixe de route (ex: '/api')
     * @param array<string, mixed> $normalizationContext Contexte de normalisation (sérialisation)
     * @param array<string, mixed> $denormalizationContext Contexte de dénormalisation (désérialisation)
     * @param string|null $shortName Nom court de la ressource (par défaut: nom de la classe)
     * @param bool $paginationEnabled Activer la pagination
     * @param int $itemsPerPage Nombre d'éléments par page
     */
    public function __construct(
        public array $operations = ['GET', 'POST', 'PUT', 'DELETE'],
        public ?string $routePrefix = '/api',
        public array $normalizationContext = ['groups' => ['read']],
        public array $denormalizationContext = ['groups' => ['write']],
        public ?string $shortName = null,
        public bool $paginationEnabled = true,
        public int $itemsPerPage = 30
    ) {}
}
