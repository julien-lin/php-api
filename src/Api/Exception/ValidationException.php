<?php

declare(strict_types=1);

namespace JulienLinard\Api\Exception;

/**
 * Exception pour les erreurs de validation
 */
class ValidationException extends ApiException
{
    private array $violations = [];
    
    /**
     * @param array<array{property: string, message: string}> $violations Liste des violations
     * @param string $message Message d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(array $violations = [], string $message = 'Les données fournies sont invalides', ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
        $this->violations = $violations;
    }
    
    /**
     * Récupère les violations
     * 
     * @return array<array{property: string, message: string}>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
    
    /**
     * Ajoute une violation
     */
    public function addViolation(string $property, string $message): void
    {
        $this->violations[] = [
            'property' => $property,
            'message' => $message,
        ];
    }
}
