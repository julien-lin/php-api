<?php

declare(strict_types=1);

namespace JulienLinard\Api\Serializer;

use ReflectionClass;
use ReflectionProperty;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Api\Annotation\Groups;
use JulienLinard\Api\Annotation\ApiResource;

/**
 * Sérialiseur JSON pour les entités API
 * 
 * Prend en charge les annotations ApiProperty et Groups
 */
class JsonSerializer
{
    /**
     * Sérialise un objet en JSON selon les groupes spécifiés
     * 
     * @param object|array $data Données à sérialiser
     * @param array<string> $groups Groupes de sérialisation
     * @return array Données sérialisées
     */
    public function serialize(mixed $data, array $groups = ['read']): array
    {
        if (is_array($data)) {
            return array_map(fn($item) => $this->serializeObject($item, $groups), $data);
        }

        return $this->serializeObject($data, $groups);
    }

    /**
     * Sérialise un objet unique
     */
    private function serializeObject(object $object, array $groups): array
    {
        $reflection = new ReflectionClass($object);
        $result = [];

        // Récupérer l'annotation ApiResource si présente
        $apiResource = $this->getApiResource($reflection);

        foreach ($reflection->getProperties() as $property) {
            // Vérifier si la propriété doit être sérialisée
            if (!$this->shouldSerializeProperty($property, $groups, $apiResource)) {
                continue;
            }

            $property->setAccessible(true);
            $value = $property->getValue($object);

            // Sérialiser la valeur
            $result[$property->getName()] = $this->serializeValue($value, $groups);
        }

        return $result;
    }

    /**
     * Détermine si une propriété doit être sérialisée
     */
    private function shouldSerializeProperty(
        ReflectionProperty $property,
        array $groups,
        ?ApiResource $apiResource
    ): bool {
        // Récupérer les annotations
        $apiProperty = $this->getApiProperty($property);
        $groupsAnnotation = $this->getGroups($property);

        // Si ApiProperty existe, utiliser ses groupes
        if ($apiProperty !== null) {
            if (!$apiProperty->readable) {
                return false;
            }

            // Vérifier si au moins un groupe correspond
            return !empty(array_intersect($groups, $apiProperty->groups));
        }

        // Si Groups existe, utiliser ses groupes
        if ($groupsAnnotation !== null) {
            return !empty(array_intersect($groups, $groupsAnnotation->groups));
        }

        // Si aucun groupe spécifié et contexte par défaut, inclure toutes les propriétés
        if ($apiResource !== null) {
            $defaultGroups = $apiResource->normalizationContext['groups'] ?? [];
            return !empty(array_intersect($groups, $defaultGroups));
        }

        // Par défaut, inclure toutes les propriétés publiques
        return $property->isPublic();
    }

    /**
     * Sérialise une valeur (peut être un objet, un tableau, etc.)
     */
    private function serializeValue(mixed $value, array $groups): mixed
    {
        if (is_object($value)) {
            // Si c'est une entité Doctrine ou un objet avec toArray()
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            // Sérialiser récursivement
            return $this->serializeObject($value, $groups);
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->serializeValue($item, $groups), $value);
        }

        return $value;
    }

    /**
     * Récupère l'annotation ApiResource d'une classe
     */
    private function getApiResource(ReflectionClass $reflection): ?ApiResource
    {
        $attributes = $reflection->getAttributes(ApiResource::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Récupère l'annotation ApiProperty d'une propriété
     */
    private function getApiProperty(ReflectionProperty $property): ?ApiProperty
    {
        $attributes = $property->getAttributes(ApiProperty::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Récupère l'annotation Groups d'une propriété
     */
    private function getGroups(ReflectionProperty $property): ?Groups
    {
        $attributes = $property->getAttributes(Groups::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }
}
