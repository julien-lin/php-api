<?php

declare(strict_types=1);

namespace JulienLinard\Api\Serializer;

use ReflectionClass;
use ReflectionProperty;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Api\Annotation\Groups;
use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Serializer\RelationSerializer;

/**
 * Sérialiseur JSON pour les entités API
 * 
 * Prend en charge les annotations ApiProperty et Groups
 */
class JsonSerializer
{
    private RelationSerializer $relationSerializer;
    private array $embedRelations = [];
    private int $maxDepth = 1;

    public function __construct()
    {
        $this->relationSerializer = new RelationSerializer();
    }

    /**
     * Définit les relations à embed (inclure dans la sérialisation)
     * 
     * @param array<string> $relations Liste des noms de relations à embed
     * @return self
     */
    public function setEmbedRelations(array $relations): self
    {
        $this->embedRelations = $relations;
        return $this;
    }

    /**
     * Définit la profondeur maximale de sérialisation
     * 
     * @param int $maxDepth Profondeur maximale
     * @return self
     */
    public function setMaxDepth(int $maxDepth): self
    {
        $this->maxDepth = $maxDepth;
        return $this;
    }

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
            $propertyName = $property->getName();

            // Vérifier si c'est une relation Doctrine
            if ($this->relationSerializer->isRelation($property)) {
                // Vérifier si cette relation doit être embed
                if (empty($this->embedRelations) || in_array($propertyName, $this->embedRelations, true)) {
                    $subresource = $this->relationSerializer->getApiSubresource($property);
                    $maxDepth = $subresource?->maxDepth ?? $this->maxDepth;
                    
                    // Sérialiser la relation avec profondeur
                    $result[$propertyName] = $this->relationSerializer->serializeRelation(
                        $value,
                        $groups,
                        0,
                        $maxDepth
                    );
                } else {
                    // Relation non embed, retourner juste l'ID
                    $result[$propertyName] = $this->extractRelationId($value);
                }
            } else {
                // Sérialiser la valeur normalement
                $result[$propertyName] = $this->serializeValue($value, $groups);
            }
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

    /**
     * Extrait l'ID d'une relation (pour les relations non embed)
     */
    private function extractRelationId(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || ($value instanceof \Traversable)) {
            // Collection : retourner un tableau d'IDs
            $ids = [];
            foreach ($value as $item) {
                if (is_object($item)) {
                    $ids[] = $this->relationSerializer->serializeRelation($item, [], 0, 0);
                }
            }
            return $ids;
        }

        if (is_object($value)) {
            // Relation simple : retourner l'ID
            return $this->relationSerializer->serializeRelation($value, [], 0, 0);
        }

        return null;
    }
}
