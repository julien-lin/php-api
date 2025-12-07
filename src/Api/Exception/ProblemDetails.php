<?php

declare(strict_types=1);

namespace JulienLinard\Api\Exception;

/**
 * Format RFC 7807 Problem Details for HTTP APIs
 * 
 * @see https://tools.ietf.org/html/rfc7807
 */
class ProblemDetails
{
    /**
     * Type URI qui identifie le type de problème
     */
    public string $type;
    
    /**
     * Titre court du problème
     */
    public string $title;
    
    /**
     * Code de statut HTTP
     */
    public int $status;
    
    /**
     * Description détaillée du problème
     */
    public ?string $detail = null;
    
    /**
     * URI de l'instance qui a causé le problème
     */
    public ?string $instance = null;
    
    /**
     * Extensions supplémentaires
     */
    public array $extensions = [];
    
    /**
     * @param string $type Type URI (ex: 'https://example.com/problems/validation-error')
     * @param string $title Titre court
     * @param int $status Code HTTP
     * @param string|null $detail Description
     * @param string|null $instance URI de l'instance
     * @param array<string, mixed> $extensions Extensions
     */
    public function __construct(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = []
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->instance = $instance;
        $this->extensions = $extensions;
    }
    
    /**
     * Convertit en tableau pour sérialisation JSON
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];
        
        if ($this->detail !== null) {
            $data['detail'] = $this->detail;
        }
        
        if ($this->instance !== null) {
            $data['instance'] = $this->instance;
        }
        
        // Ajouter les extensions
        foreach ($this->extensions as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
    
    /**
     * Crée un ProblemDetails depuis une exception
     */
    public static function fromException(\Throwable $exception, ?string $baseUrl = null): self
    {
        $status = 500;
        $type = 'https://example.com/problems/internal-server-error';
        $title = 'Internal Server Error';
        
        if ($exception instanceof ApiException) {
            $status = $exception->getStatusCode();
            $type = self::getTypeFromStatusCode($status);
            $title = self::getTitleFromStatusCode($status);
        }
        
        $instance = null;
        if ($baseUrl !== null && isset($_SERVER['REQUEST_URI'])) {
            $instance = $baseUrl . $_SERVER['REQUEST_URI'];
        }
        
        $extensions = [];
        
        // Ajouter les violations si ValidationException
        if ($exception instanceof ValidationException) {
            $extensions['violations'] = $exception->getViolations();
        }
        
        // En mode debug, ajouter la trace de l'exception précédente si elle existe
        if ($exception instanceof ApiException && $exception->getPrevious() !== null) {
            $previous = $exception->getPrevious();
            $extensions['previous'] = [
                'message' => $previous->getMessage(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
                'trace' => $previous->getTraceAsString(),
            ];
        }
        
        return new self(
            type: $type,
            title: $title,
            status: $status,
            detail: $exception->getMessage(),
            instance: $instance,
            extensions: $extensions
        );
    }
    
    /**
     * Récupère le type URI depuis un code de statut
     */
    private static function getTypeFromStatusCode(int $status): string
    {
        return match ($status) {
            400 => 'https://example.com/problems/bad-request',
            401 => 'https://example.com/problems/unauthorized',
            403 => 'https://example.com/problems/forbidden',
            404 => 'https://example.com/problems/not-found',
            422 => 'https://example.com/problems/validation-error',
            500 => 'https://example.com/problems/internal-server-error',
            default => 'https://example.com/problems/error',
        };
    }
    
    /**
     * Récupère le titre depuis un code de statut
     */
    private static function getTitleFromStatusCode(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
