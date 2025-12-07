<?php

declare(strict_types=1);

namespace JulienLinard\Api\Exception;

/**
 * Exception pour les ressources non trouvées (404)
 */
class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Ressource introuvable', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
