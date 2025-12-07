<?php

declare(strict_types=1);

namespace JulienLinard\Api\Controller;

use JulienLinard\Core\Controller\Controller;
use JulienLinard\Core\Application;
use JulienLinard\Router\Response;
use JulienLinard\Router\Request;
use JulienLinard\Router\Attributes\Route;
use JulienLinard\Api\Serializer\JsonSerializer;
use JulienLinard\Api\Exception\NotFoundException;
use JulienLinard\Api\Annotation\ApiResource;
use ReflectionClass;
use ReflectionProperty;
use JulienLinard\Doctrine\Mapping\ManyToOne;
use JulienLinard\Doctrine\Mapping\OneToMany;
use JulienLinard\Api\Annotation\ApiSubresource;

/**
 * Contrôleur pour les sous-ressources (relations)
 * 
 * Permet d'accéder aux relations via des routes dédiées :
 * GET /api/products/{id}/orders
 * GET /api/products/{id}/orders/{orderId}
 */
class SubresourceController extends Controller
{
    private JsonSerializer $serializer;
    private array $entityClasses = [];

    public function __construct(array $entityClasses = [])
    {
        $this->serializer = new JsonSerializer();
        $this->entityClasses = $entityClasses;
    }

    /**
     * Récupère une collection de sous-ressources
     * GET /api/{resource}/{id}/{subresource}
     * 
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/{resource}/{id}/{subresource}', methods: ['GET'], name: 'api.subresource.collection')]
    public function collection(Request $request): Response
    {
        try {
            $resource = $request->getRouteParam('resource');
            $id = $request->getRouteParam('id');
            $subresource = $request->getRouteParam('subresource');

            if (!$resource || !$id || !$subresource) {
                throw new \InvalidArgumentException('Paramètres manquants');
            }

            // Récupérer l'entité principale
            $entity = $this->getEntity($resource, (int)$id);
            if ($entity === null) {
                throw new NotFoundException("Ressource {$resource} avec l'ID {$id} introuvable");
            }

            // Récupérer la relation
            $relationValue = $this->getRelationValue($entity, $subresource);
            if ($relationValue === null) {
                throw new NotFoundException("Relation '{$subresource}' introuvable sur {$resource}");
            }

            // Sérialiser
            $embedRelations = [];
            if (isset($request->getQueryParams()['embed'])) {
                $embedRelations = explode(',', $request->getQueryParams()['embed']);
            }
            $this->serializer->setEmbedRelations($embedRelations);

            $data = $this->serializer->serialize($relationValue, ['read']);

            return $this->json([
                'data' => is_array($relationValue) ? $data : [$data],
                'total' => is_array($relationValue) ? count($relationValue) : 1,
            ]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Erreur lors de la récupération de la sous-ressource: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère un élément spécifique d'une sous-ressource
     * GET /api/{resource}/{id}/{subresource}/{subId}
     * 
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/{resource}/{id}/{subresource}/{subId}', methods: ['GET'], name: 'api.subresource.item')]
    public function item(Request $request): Response
    {
        try {
            $resource = $request->getRouteParam('resource');
            $id = $request->getRouteParam('id');
            $subresource = $request->getRouteParam('subresource');
            $subId = $request->getRouteParam('subId');

            if (!$resource || !$id || !$subresource || !$subId) {
                throw new \InvalidArgumentException('Paramètres manquants');
            }

            // Récupérer l'entité principale
            $entity = $this->getEntity($resource, (int)$id);
            if ($entity === null) {
                throw new NotFoundException("Ressource {$resource} avec l'ID {$id} introuvable");
            }

            // Récupérer la relation
            $relationValue = $this->getRelationValue($entity, $subresource);
            if ($relationValue === null) {
                throw new NotFoundException("Relation '{$subresource}' introuvable sur {$resource}");
            }

            // Trouver l'élément spécifique
            $item = $this->findInRelation($relationValue, (int)$subId);
            if ($item === null) {
                throw new NotFoundException("Élément avec l'ID {$subId} introuvable dans {$subresource}");
            }

            // Sérialiser
            $embedRelations = [];
            if (isset($request->getQueryParams()['embed'])) {
                $embedRelations = explode(',', $request->getQueryParams()['embed']);
            }
            $this->serializer->setEmbedRelations($embedRelations);

            $data = $this->serializer->serialize($item, ['read']);

            return $this->json(['data' => $data]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Erreur lors de la récupération de la sous-ressource: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère une entité par son nom de ressource et son ID
     */
    private function getEntity(string $resourceName, int $id): ?object
    {
        // Convertir le nom de ressource en nom de classe
        // Ex: 'products' -> 'Product'
        $className = $this->resourceNameToClassName($resourceName);
        
        if (!class_exists($className)) {
            return null;
        }

        $app = Application::getInstance();
        if ($app === null) {
            return null;
        }

        $em = $app->getEntityManager();
        $repository = $em->getRepository($className);
        
        return $repository->find($id);
    }

    /**
     * Convertit un nom de ressource en nom de classe
     */
    private function resourceNameToClassName(string $resourceName): string
    {
        // Si on a une liste d'entités, chercher par shortName
        if (!empty($this->entityClasses)) {
            foreach ($this->entityClasses as $entityClass) {
                $reflection = new ReflectionClass($entityClass);
                $attributes = $reflection->getAttributes(ApiResource::class);
                
                if (!empty($attributes)) {
                    $apiResource = $attributes[0]->newInstance();
                    $shortName = $apiResource->shortName ?? $this->getShortName($entityClass);
                    
                    if (strtolower($shortName) === strtolower($resourceName)) {
                        return $entityClass;
                    }
                }
            }
        }

        // Fallback : chercher dans les namespaces connus
        $possibleNamespaces = [
            'App\\Entity\\',
            'App\\Model\\',
        ];

        $singular = ucfirst(rtrim($resourceName, 's')); // 'products' -> 'Product'
        
        foreach ($possibleNamespaces as $namespace) {
            $className = $namespace . $singular;
            if (class_exists($className)) {
                return $className;
            }
        }

        // Si pas trouvé, essayer avec le nom tel quel
        foreach ($possibleNamespaces as $namespace) {
            $className = $namespace . ucfirst($resourceName);
            if (class_exists($className)) {
                return $className;
            }
        }

        throw new \InvalidArgumentException("Classe introuvable pour la ressource '{$resourceName}'");
    }

    /**
     * Génère un nom court depuis un nom de classe
     */
    private function getShortName(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);
        return strtolower($shortName);
    }

    /**
     * Récupère la valeur d'une relation sur une entité
     */
    private function getRelationValue(object $entity, string $relationName): mixed
    {
        $reflection = new ReflectionClass($entity);
        
        if (!$reflection->hasProperty($relationName)) {
            return null;
        }

        $property = $reflection->getProperty($relationName);
        $property->setAccessible(true);
        
        return $property->getValue($entity);
    }

    /**
     * Trouve un élément dans une relation par son ID
     */
    private function findInRelation(mixed $relationValue, int $id): ?object
    {
        if (is_array($relationValue) || ($relationValue instanceof \Traversable)) {
            foreach ($relationValue as $item) {
                if (is_object($item)) {
                    $reflection = new ReflectionClass($item);
                    if ($reflection->hasProperty('id')) {
                        $idProperty = $reflection->getProperty('id');
                        $idProperty->setAccessible(true);
                        if ($idProperty->getValue($item) === $id) {
                            return $item;
                        }
                    }
                }
            }
        } elseif (is_object($relationValue)) {
            // Relation simple (ManyToOne)
            $reflection = new ReflectionClass($relationValue);
            if ($reflection->hasProperty('id')) {
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                if ($idProperty->getValue($relationValue) === $id) {
                    return $relationValue;
                }
            }
        }

        return null;
    }
}
