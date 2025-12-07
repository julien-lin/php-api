<?php

declare(strict_types=1);

namespace JulienLinard\Api\Validator;

use ReflectionClass;
use ReflectionProperty;
use JulienLinard\Api\Exception\ValidationException;
use JulienLinard\Api\Annotation\ApiProperty;

/**
 * Validateur pour les entités API
 * 
 * Valide les données selon les annotations ApiProperty
 */
class ApiValidator
{
    /**
     * Valide les données d'une entité
     * 
     * @param array<string, mixed> $data Données à valider
     * @param string $entityClass Classe de l'entité
     * @param array<string> $groups Groupes de validation (ex: ['create', 'update'])
     * @return void
     * @throws ValidationException Si la validation échoue
     */
    public function validate(array $data, string $entityClass, array $groups = ['Default']): void
    {
        $violations = [];
        $reflection = new ReflectionClass($entityClass);
        
        // Vérifier les propriétés requises
        foreach ($reflection->getProperties() as $property) {
            $apiProperty = $this->getApiProperty($property);
            
            if ($apiProperty === null) {
                continue;
            }
            
            $propertyName = $property->getName();
            
            // Vérifier si la propriété est requise
            if ($apiProperty->required && !isset($data[$propertyName])) {
                $violations[] = [
                    'property' => $propertyName,
                    'message' => "Le champ '{$propertyName}' est requis",
                ];
                continue;
            }
            
            // Vérifier si la propriété est dans les groupes de validation
            if (!empty($apiProperty->groups)) {
                $hasGroup = false;
                foreach ($groups as $group) {
                    if (in_array($group, $apiProperty->groups, true)) {
                        $hasGroup = true;
                        break;
                    }
                }
                
                if (!$hasGroup) {
                    continue; // Cette propriété n'est pas dans les groupes de validation
                }
            }
            
            // Si la propriété est présente, valider sa valeur
            if (isset($data[$propertyName])) {
                $value = $data[$propertyName];
                
                // Validation basique selon le type
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    $error = $this->validateType($value, $typeName, $propertyName);
                    if ($error !== null) {
                        $violations[] = $error;
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            throw new ValidationException($violations);
        }
    }
    
    /**
     * Valide le type d'une valeur
     */
    private function validateType(mixed $value, string $typeName, string $propertyName): ?array
    {
        return match ($typeName) {
            'int' => !is_int($value) ? ['property' => $propertyName, 'message' => "Le champ '{$propertyName}' doit être un entier"] : null,
            'float' => !is_numeric($value) ? ['property' => $propertyName, 'message' => "Le champ '{$propertyName}' doit être un nombre"] : null,
            'bool' => !is_bool($value) && !in_array($value, ['0', '1', 0, 1, 'true', 'false'], true) ? ['property' => $propertyName, 'message' => "Le champ '{$propertyName}' doit être un booléen"] : null,
            'string' => !is_string($value) ? ['property' => $propertyName, 'message' => "Le champ '{$propertyName}' doit être une chaîne de caractères"] : null,
            'array' => !is_array($value) ? ['property' => $propertyName, 'message' => "Le champ '{$propertyName}' doit être un tableau"] : null,
            default => null,
        };
    }
    
    /**
     * Récupère l'annotation ApiProperty
     */
    private function getApiProperty(ReflectionProperty $property): ?ApiProperty
    {
        $attributes = $property->getAttributes(ApiProperty::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }
}
