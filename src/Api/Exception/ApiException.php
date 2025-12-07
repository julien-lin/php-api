<?php

declare(strict_types=1);

namespace JulienLinard\Api\Exception;

use Exception;

/**
 * Exception de base pour les erreurs API
 */
class ApiException extends Exception
{
    private int $statusCode;
    
    /**
     * @param string $message Message d'erreur
     * @param int $statusCode Code HTTP
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $message = '',
        int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }
    
    /**
     * Récupère le code de statut HTTP
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
