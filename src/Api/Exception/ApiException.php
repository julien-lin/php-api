<?php

declare(strict_types=1);

namespace JulienLinard\Api\Exception;

use Exception;

/**
 * Exception de base pour les erreurs API
 */
class ApiException extends Exception
{
    /**
     * @param string $message Message d'erreur
     * @param int $code Code HTTP
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $message = '',
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
