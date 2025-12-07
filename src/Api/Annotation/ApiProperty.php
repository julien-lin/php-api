<?php

declare(strict_types=1);

namespace JulienLinard\Api\Annotation;

use Attribute;

/**
 * Annotation pour configurer la sérialisation d'une propriété
 * 
 * Inspirée d'API Platform de Symfony
 * 
 * @example
 * #[ApiProperty(
 *     groups: ['read', 'write'],
 *     readable: true,
 *     writable: true,
 *     description: 'Email de l\'utilisateur'
 * )]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ApiProperty
{
    /**
     * @param array<string> $groups Groupes de sérialisation (ex: ['read', 'write'])
     * @param bool $readable La propriété est-elle lisible via l'API ?
     * @param bool $writable La propriété est-elle modifiable via l'API ?
     * @param string|null $description Description de la propriété
     * @param string|null $iri IRI de la propriété (pour les relations)
     * @param bool $required La propriété est-elle requise ?
     * @param mixed|null $default Valeur par défaut
     */
    public function __construct(
        public array $groups = ['read', 'write'],
        public bool $readable = true,
        public bool $writable = true,
        public ?string $description = null,
        public ?string $iri = null,
        public bool $required = false,
        public mixed $default = null
    ) {}
}
