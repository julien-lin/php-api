<?php

declare(strict_types=1);

namespace JulienLinard\Api\Event;

/**
 * Événement API de base
 */
class ApiEvent
{
    public const PRE_CREATE = 'api.pre_create';
    public const POST_CREATE = 'api.post_create';
    public const PRE_UPDATE = 'api.pre_update';
    public const POST_UPDATE = 'api.post_update';
    public const PRE_DELETE = 'api.pre_delete';
    public const POST_DELETE = 'api.post_delete';
    public const PRE_READ = 'api.pre_read';
    public const POST_READ = 'api.post_read';

    /**
     * @param string $eventName Nom de l'événement
     * @param object|null $entity Entité concernée
     * @param array<string, mixed> $data Données associées
     */
    public function __construct(
        public string $eventName,
        public ?object $entity = null,
        public array $data = []
    ) {}
}
