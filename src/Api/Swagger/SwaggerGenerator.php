<?php

declare(strict_types=1);

namespace JulienLinard\Api\Swagger;

use ReflectionClass;
use ReflectionProperty;
use JulienLinard\Api\Annotation\ApiResource;
use JulienLinard\Api\Annotation\ApiProperty;
use JulienLinard\Api\Filter\ApiFilter;
use JulienLinard\Api\Filter\SearchFilter;
use JulienLinard\Api\Filter\DateFilter;
use JulienLinard\Api\Filter\RangeFilter;
use JulienLinard\Api\Filter\BooleanFilter;

/**
 * Générateur de documentation OpenAPI/Swagger
 * 
 * Génère automatiquement la spécification OpenAPI à partir des annotations
 */
class SwaggerGenerator
{
    private array $paths = [];
    private array $components = [];
    private array $tags = [];
    private array $schemaToEntityMap = []; // Mapping schema name => entity class

    /**
     * Génère la spécification OpenAPI complète
     * 
     * @param array<string> $entityClasses Liste des classes d'entités exposées
     * @param string $title Titre de l'API
     * @param string $version Version de l'API
     * @param string $basePath Chemin de base de l'API
     * @return array Spécification OpenAPI
     */
    public function generate(
        array $entityClasses,
        string $title = 'API',
        string $version = '1.0.0',
        string $basePath = '/api'
    ): array {
        $this->paths = [];
        $this->components = ['schemas' => []];
        $this->tags = [];

        foreach ($entityClasses as $entityClass) {
            $this->processEntity($entityClass, $basePath);
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $title,
                'version' => $version,
                'description' => 'Documentation API générée automatiquement',
            ],
            'servers' => [
                [
                    'url' => $basePath,
                    'description' => 'Serveur API',
                ],
            ],
            'paths' => $this->paths,
            'components' => $this->components,
            'tags' => $this->tags,
        ];
    }

    /**
     * Traite une entité et génère les chemins OpenAPI
     */
    private function processEntity(string $entityClass, string $basePath): void
    {
        $reflection = new ReflectionClass($entityClass);
        
        // Récupérer l'annotation ApiResource
        $apiResource = $this->getApiResource($reflection);
        if ($apiResource === null) {
            return; // Entité non exposée
        }

        $shortName = $apiResource->shortName ?? $this->getShortName($entityClass);
        $resourcePath = strtolower($shortName);
        $fullPath = $basePath . '/' . $resourcePath;

        // Ajouter le tag
        $this->tags[] = [
            'name' => $shortName,
            'description' => "Opérations sur {$shortName}",
        ];

        // Générer le schéma de l'entité
        $schemaName = $this->generateSchema($reflection, $apiResource);
        
        // Stocker le mapping
        $this->schemaToEntityMap[$schemaName] = $entityClass;

        // Générer les opérations selon ApiResource
        $operations = $apiResource->operations;

        // GET collection
        if (in_array('GET', $operations, true)) {
            $this->paths["/{$resourcePath}"] = [
                'get' => $this->generateGetCollectionOperation($shortName, $schemaName, $apiResource, $reflection),
            ];
        }

        // GET item
        if (in_array('GET', $operations, true)) {
            $this->paths["/{$resourcePath}/{id}"] = [
                'get' => $this->generateGetItemOperation($shortName, $schemaName),
            ];
        }

        // POST
        if (in_array('POST', $operations, true)) {
            if (!isset($this->paths["/{$resourcePath}"])) {
                $this->paths["/{$resourcePath}"] = [];
            }
            $this->paths["/{$resourcePath}"]['post'] = $this->generatePostOperation($shortName, $schemaName, $apiResource);
        }

        // PUT
        if (in_array('PUT', $operations, true)) {
            if (!isset($this->paths["/{$resourcePath}/{id}"])) {
                $this->paths["/{$resourcePath}/{id}"] = [];
            }
            $this->paths["/{$resourcePath}/{id}"]['put'] = $this->generatePutOperation($shortName, $schemaName, $apiResource);
        }

        // PATCH
        if (in_array('PATCH', $operations, true)) {
            if (!isset($this->paths["/{$resourcePath}/{id}"])) {
                $this->paths["/{$resourcePath}/{id}"] = [];
            }
            $this->paths["/{$resourcePath}/{id}"]['patch'] = $this->generatePatchOperation($shortName, $schemaName, $apiResource);
        }

        // DELETE
        if (in_array('DELETE', $operations, true)) {
            if (!isset($this->paths["/{$resourcePath}/{id}"])) {
                $this->paths["/{$resourcePath}/{id}"] = [];
            }
            $this->paths["/{$resourcePath}/{id}"]['delete'] = $this->generateDeleteOperation($shortName);
        }
    }

    /**
     * Génère le schéma OpenAPI pour une entité
     */
    private function generateSchema(ReflectionClass $reflection, ApiResource $apiResource): string
    {
        $schemaName = $this->getShortName($reflection->getName());
        $properties = [];
        $required = [];

        foreach ($reflection->getProperties() as $property) {
            $apiProperty = $this->getApiProperty($property);
            
            // Ne pas inclure les propriétés non lisibles
            if ($apiProperty !== null && !$apiProperty->readable) {
                continue;
            }

            $propertyName = $property->getName();
            $propertyType = $this->getPropertyType($property);
            
            $properties[$propertyName] = $this->getPropertySchema($propertyType, $apiProperty, $property);

            // Ajouter aux required si nécessaire
            if ($apiProperty !== null && $apiProperty->required) {
                $required[] = $propertyName;
            }
        }

        $this->components['schemas'][$schemaName] = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $this->components['schemas'][$schemaName]['required'] = $required;
        }

        return $schemaName;
    }

    /**
     * Génère l'opération GET collection
     */
    private function generateGetCollectionOperation(string $tag, string $schemaName, ApiResource $apiResource, ReflectionClass $reflection): array
    {
        $operation = [
            'tags' => [$tag],
            'summary' => "Récupère la liste des {$tag}",
            'operationId' => "get{$tag}Collection",
            'parameters' => [],
            'responses' => [
                '200' => [
                    'description' => 'Liste des ressources',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => [
                                        'type' => 'array',
                                        'items' => ['$ref' => "#/components/schemas/{$schemaName}"],
                                    ],
                                    'total' => [
                                        'type' => 'integer',
                                        'description' => 'Nombre total de ressources',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Ajouter les paramètres de pagination si activée
        if ($apiResource->paginationEnabled) {
            $operation['parameters'][] = [
                'name' => 'page',
                'in' => 'query',
                'description' => 'Numéro de page',
                'required' => false,
                'schema' => ['type' => 'integer', 'default' => 1],
            ];
            $operation['parameters'][] = [
                'name' => 'limit',
                'in' => 'query',
                'description' => 'Nombre d\'éléments par page',
                'required' => false,
                'schema' => ['type' => 'integer', 'default' => $apiResource->itemsPerPage],
            ];
        }
        
        // Ajouter les paramètres de tri
        $operation['parameters'][] = [
            'name' => 'order',
            'in' => 'query',
            'description' => 'Tri par propriétés (ex: order[price]=desc&order[name]=asc)',
            'required' => false,
            'style' => 'deepObject',
            'explode' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                ],
            ],
        ];
        
        // Ajouter les paramètres de filtres depuis les annotations ApiFilter
        $filterParams = $this->generateFilterParameters($reflection);
        foreach ($filterParams as $param) {
            $operation['parameters'][] = $param;
        }

        return $operation;
    }
    
    /**
     * Génère les paramètres de filtres depuis les annotations ApiFilter
     */
    private function generateFilterParameters(ReflectionClass $reflection): array
    {
        $parameters = [];
        $attributes = $reflection->getAttributes(ApiFilter::class);
        
        foreach ($attributes as $attribute) {
            $apiFilter = $attribute->newInstance();
            $filterClass = $apiFilter->filterClass;
            
            foreach ($apiFilter->properties as $property) {
                // Générer le paramètre selon le type de filtre
                if ($filterClass === SearchFilter::class) {
                    $parameters[] = [
                        'name' => $property,
                        'in' => 'query',
                        'description' => "Recherche dans {$property} (ex: {$property}[partial]=value, {$property}[exact]=value)",
                        'required' => false,
                        'style' => 'deepObject',
                        'explode' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'exact' => ['type' => 'string'],
                                'partial' => ['type' => 'string'],
                                'start' => ['type' => 'string'],
                                'end' => ['type' => 'string'],
                                'word_start' => ['type' => 'string'],
                            ],
                        ],
                    ];
                } elseif ($filterClass === DateFilter::class) {
                    $parameters[] = [
                        'name' => $property,
                        'in' => 'query',
                        'description' => "Filtre par date pour {$property} (ex: {$property}[after]=2025-01-01)",
                        'required' => false,
                        'style' => 'deepObject',
                        'explode' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'exact' => ['type' => 'string', 'format' => 'date'],
                                'before' => ['type' => 'string', 'format' => 'date'],
                                'after' => ['type' => 'string', 'format' => 'date'],
                            ],
                        ],
                    ];
                } elseif ($filterClass === RangeFilter::class) {
                    $parameters[] = [
                        'name' => $property,
                        'in' => 'query',
                        'description' => "Filtre par plage pour {$property} (ex: {$property}[gte]=100&{$property}[lte]=500)",
                        'required' => false,
                        'style' => 'deepObject',
                        'explode' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'gt' => ['type' => 'number'],
                                'gte' => ['type' => 'number'],
                                'lt' => ['type' => 'number'],
                                'lte' => ['type' => 'number'],
                                'between' => ['type' => 'string', 'description' => 'Format: min,max'],
                            ],
                        ],
                    ];
                } elseif ($filterClass === BooleanFilter::class) {
                    $parameters[] = [
                        'name' => $property,
                        'in' => 'query',
                        'description' => "Filtre booléen pour {$property}",
                        'required' => false,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ];
                }
            }
        }
        
        return $parameters;
    }

    /**
     * Génère l'opération GET item
     */
    private function generateGetItemOperation(string $tag, string $schemaName): array
    {
        return [
            'tags' => [$tag],
            'summary' => "Récupère un {$tag} par son ID",
            'operationId' => "get{$tag}Item",
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'description' => 'Identifiant de la ressource',
                    'schema' => ['type' => 'integer'],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Ressource trouvée',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['$ref' => "#/components/schemas/{$schemaName}"],
                                ],
                            ],
                        ],
                    ],
                ],
                '404' => [
                    'description' => 'Ressource non trouvée',
                ],
            ],
        ];
    }

    /**
     * Génère l'opération POST
     */
    private function generatePostOperation(string $tag, string $schemaName, ApiResource $apiResource): array
    {
        return [
            'tags' => [$tag],
            'summary' => "Crée un nouveau {$tag}",
            'operationId' => "create{$tag}",
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $this->getWritableProperties($schemaName),
                        ],
                    ],
                ],
            ],
            'responses' => [
                '201' => [
                    'description' => 'Ressource créée',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['$ref' => "#/components/schemas/{$schemaName}"],
                                ],
                            ],
                        ],
                    ],
                ],
                '400' => [
                    'description' => 'Données invalides',
                ],
            ],
        ];
    }

    /**
     * Génère l'opération PUT
     */
    private function generatePutOperation(string $tag, string $schemaName, ApiResource $apiResource): array
    {
        return [
            'tags' => [$tag],
            'summary' => "Met à jour un {$tag}",
            'operationId' => "update{$tag}",
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'integer'],
                ],
            ],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $this->getWritableProperties($schemaName),
                        ],
                    ],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Ressource mise à jour',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'data' => ['$ref' => "#/components/schemas/{$schemaName}"],
                                ],
                            ],
                        ],
                    ],
                ],
                '404' => [
                    'description' => 'Ressource non trouvée',
                ],
                '400' => [
                    'description' => 'Données invalides',
                ],
            ],
        ];
    }

    /**
     * Génère l'opération PATCH
     */
    private function generatePatchOperation(string $tag, string $schemaName, ApiResource $apiResource): array
    {
        // PATCH est similaire à PUT mais les champs sont optionnels
        $operation = $this->generatePutOperation($tag, $schemaName, $apiResource);
        $operation['summary'] = "Met à jour partiellement un {$tag}";
        $operation['operationId'] = "patch{$tag}";
        return $operation;
    }

    /**
     * Génère l'opération DELETE
     */
    private function generateDeleteOperation(string $tag): array
    {
        return [
            'tags' => [$tag],
            'summary' => "Supprime un {$tag}",
            'operationId' => "delete{$tag}",
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'integer'],
                ],
            ],
            'responses' => [
                '204' => [
                    'description' => 'Ressource supprimée',
                ],
                '404' => [
                    'description' => 'Ressource non trouvée',
                ],
            ],
        ];
    }

    /**
     * Récupère les propriétés écrivables d'un schéma
     */
    private function getWritableProperties(string $schemaName): array
    {
        if (!isset($this->components['schemas'][$schemaName]['properties'])) {
            return [];
        }

        $properties = $this->components['schemas'][$schemaName]['properties'];
        $writable = [];

        // Filtrer pour ne garder que les propriétés écrivables
        // On doit réanalyser l'entité pour vérifier les groupes 'write'
        $entityClass = $this->findEntityClassBySchemaName($schemaName);
        if ($entityClass !== null) {
            $reflection = new ReflectionClass($entityClass);
            foreach ($reflection->getProperties() as $property) {
                $propertyName = $property->getName();
                if (!isset($properties[$propertyName])) {
                    continue;
                }

                $apiProperty = $this->getApiProperty($property);
                
                // Vérifier si la propriété est écrivable
                if ($apiProperty !== null) {
                    if (!$apiProperty->writable) {
                        continue; // Propriété non écrivable
                    }
                    // Vérifier si elle est dans le groupe 'write'
                    if (!in_array('write', $apiProperty->groups, true)) {
                        continue;
                    }
                }

                // Exclure l'ID et les dates automatiques (générées côté serveur)
                if (!in_array($propertyName, ['id', 'createdAt', 'updatedAt'], true)) {
                    $writable[$propertyName] = $properties[$propertyName];
                }
            }
        } else {
            // Fallback : exclure seulement ID et dates
            foreach ($properties as $name => $property) {
                if (!in_array($name, ['id', 'createdAt', 'updatedAt'], true)) {
                    $writable[$name] = $property;
                }
            }
        }

        return $writable;
    }

    /**
     * Trouve la classe d'entité correspondant à un nom de schéma
     */
    private function findEntityClassBySchemaName(string $schemaName): ?string
    {
        return $this->schemaToEntityMap[$schemaName] ?? null;
    }

    /**
     * Récupère le type d'une propriété
     */
    private function getPropertyType(ReflectionProperty $property): string
    {
        $type = $property->getType();
        
        if ($type === null) {
            return 'string';
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            
            // Convertir les types PHP en types OpenAPI
            return match ($typeName) {
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'array',
                'object' => 'object',
                \DateTime::class, \DateTimeImmutable::class => 'string', // Format datetime
                default => 'string',
            };
        }

        return 'string';
    }

    /**
     * Génère le schéma OpenAPI pour une propriété
     */
    private function getPropertySchema(string $type, ?ApiProperty $apiProperty, ReflectionProperty $property): array
    {
        $schema = ['type' => $type];

        // Détecter DateTime
        $propertyType = $property->getType();
        if ($propertyType instanceof \ReflectionNamedType) {
            $typeName = $propertyType->getName();
            if ($typeName === \DateTime::class || $typeName === \DateTimeImmutable::class) {
                $schema['type'] = 'string';
                $schema['format'] = 'date-time';
                $schema['example'] = '2025-01-01T00:00:00+00:00';
            }
        }

        // Gérer les types nullable
        if ($propertyType instanceof \ReflectionNamedType && $propertyType->allowsNull()) {
            // Pour OpenAPI, on peut utiliser oneOf ou simplement permettre null
            // Ici, on garde le type principal mais on note que c'est nullable
            // (OpenAPI 3.0 gère mieux les nullable avec le format spécifique)
        }

        // Ajouter description si disponible
        if ($apiProperty !== null && $apiProperty->description !== null) {
            $schema['description'] = $apiProperty->description;
        }

        // Format pour les nombres décimaux
        if ($type === 'number') {
            $schema['format'] = 'float';
        }

        // Valeur par défaut
        if ($apiProperty !== null && $apiProperty->default !== null) {
            $schema['default'] = $apiProperty->default;
        }

        return $schema;
    }

    /**
     * Récupère l'annotation ApiResource
     */
    private function getApiResource(ReflectionClass $reflection): ?ApiResource
    {
        $attributes = $reflection->getAttributes(ApiResource::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Récupère l'annotation ApiProperty
     */
    private function getApiProperty(ReflectionProperty $property): ?ApiProperty
    {
        $attributes = $property->getAttributes(ApiProperty::class);
        return !empty($attributes) ? $attributes[0]->newInstance() : null;
    }

    /**
     * Extrait le nom court d'une classe
     */
    private function getShortName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }
}
