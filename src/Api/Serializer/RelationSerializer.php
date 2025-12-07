<?php

declare(strict_types=1);

namespace JulienLinard\Api\Serializer;

use ReflectionClass;
use ReflectionProperty;
use JulienLinard\Doctrine\Mapping\ManyToOne;
use JulienLinard\Doctrine\Mapping\OneToMany;
use JulienLinard\Api\Annotation\ApiSubresource;

/**
 * Gestionnaire de sérialisation des relations
 */
class RelationSerializer
{
    /**
     * Détecte si une propriété est une relation Doctrine
     */
    public function isRelation(ReflectionProperty $property): bool
    {
        return $this->hasManyToOne($property) || $this->hasOneToMany($property);
    }

    /**
     * Vérifie si une propriété a l'annotation ManyToOne
     */
    public function hasManyToOne(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(ManyToOne::class);
        return !empty($attributes);
    }

    /**
     * Vérifie si une propriété a l'annotation OneToMany
     */
    public function hasOneToMany(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(OneToMany::class);
        return !empty($attributes);
    }

    /**
     * Récupère l'annotation ApiSubresource d'une propriété
     */
    public function getApiSubresource(ReflectionProperty $property): ?ApiSubresource
    {
        $attributes = $property->getAttributes(ApiSubresource::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Récupère l'annotation ManyToOne d'une propriété
     */
    public function getManyToOne(ReflectionProperty $property): ?ManyToOne
    {
        $attributes = $property->getAttributes(ManyToOne::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Récupère l'annotation OneToMany d'une propriété
     */
    public function getOneToMany(ReflectionProperty $property): ?OneToMany
    {
        $attributes = $property->getAttributes(OneToMany::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Sérialise une relation selon les groupes et la profondeur
     */
    public function serializeRelation(
        mixed $value,
        array $groups,
        int $currentDepth = 0,
        int $maxDepth = 1
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || ($value instanceof \Traversable)) {
            // Collection (OneToMany, ManyToMany)
            $result = [];
            foreach ($value as $item) {
                if ($currentDepth >= $maxDepth) {
                    $result[] = $this->extractId($item);
                } else {
                    $result[] = $this->serializeRelationItem($item, $groups, $currentDepth, $maxDepth);
                }
            }
            return $result;
        }

        if (is_object($value)) {
            // Relation simple (ManyToOne, OneToOne)
            if ($currentDepth >= $maxDepth) {
                return $this->extractId($value);
            }
            return $this->serializeRelationItem($value, $groups, $currentDepth, $maxDepth);
        }

        return $value;
    }

    /**
     * Sérialise un élément de relation
     */
    private function serializeRelationItem(
        object $item,
        array $groups,
        int $currentDepth,
        int $maxDepth
    ): array {
        $reflection = new ReflectionClass($item);
        $result = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $propValue = $property->getValue($item);
            $propName = $property->getName();

            // Ignorer les relations pour éviter les boucles infinies
            // Les relations seront gérées par JsonSerializer avec embed
            if ($this->isRelation($property)) {
                // Si on a atteint la profondeur max, retourner juste l'ID
                if ($currentDepth + 1 >= $maxDepth) {
                    $result[$propName] = $this->extractId($propValue);
                } else {
                    // Sinon, sérialiser récursivement
                    $subresource = $this->getApiSubresource($property);
                    $relationMaxDepth = $subresource?->maxDepth ?? $maxDepth;
                    $result[$propName] = $this->serializeRelation(
                        $propValue,
                        $groups,
                        $currentDepth + 1,
                        $relationMaxDepth
                    );
                }
            } else {
                // Propriété simple
                $result[$propName] = $propValue;
            }
        }

        return $result;
    }

    /**
     * Extrait l'ID d'un objet
     */
    private function extractId(object $object): mixed
    {
        $reflection = new ReflectionClass($object);
        
        // Chercher une propriété 'id'
        if ($reflection->hasProperty('id')) {
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            return $idProperty->getValue($object);
        }

        // Chercher une méthode getId()
        if ($reflection->hasMethod('getId')) {
            return $object->getId();
        }

        // Par défaut, retourner null
        return null;
    }
}
