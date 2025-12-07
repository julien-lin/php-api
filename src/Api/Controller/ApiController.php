<?php

declare(strict_types=1);

namespace JulienLinard\Api\Controller;

use JulienLinard\Core\Controller\Controller;
use JulienLinard\Router\Response;
use JulienLinard\Router\Request;
use JulienLinard\Api\Serializer\JsonSerializer;
use JulienLinard\Api\Exception\ApiException;
use JulienLinard\Api\Exception\NotFoundException;

/**
 * Contrôleur de base pour les APIs REST
 * 
 * Fournit les opérations CRUD standard
 */
abstract class ApiController extends Controller
{
    protected JsonSerializer $serializer;
    protected string $entityClass;

    /**
     * @param string $entityClass Classe de l'entité
     * @param JsonSerializer $serializer Sérialiseur JSON
     */
    public function __construct(string $entityClass, JsonSerializer $serializer)
    {
        $this->entityClass = $entityClass;
        $this->serializer = $serializer;
    }

    /**
     * Liste toutes les entités (GET /api/resource)
     * 
     * @param Request|array<string, mixed> $requestOrParams Requête HTTP ou paramètres de requête (pagination, filtres)
     * @return Response
     */
    public function index(Request|array $requestOrParams = []): Response
    {
        try {
            $queryParams = $requestOrParams instanceof Request 
                ? $requestOrParams->getQueryParams() 
                : (is_array($requestOrParams) ? $requestOrParams : []);
            
            $entities = $this->getAll($queryParams);
            $data = $this->serializer->serialize($entities, ['read']);

            return $this->json([
                'data' => $data,
                'total' => count($entities),
            ]);
        } catch (\Throwable $e) {
            throw new ApiException('Erreur lors de la récupération des ressources', 500, $e);
        }
    }

    /**
     * Récupère une entité par son ID (GET /api/resource/{id})
     * 
     * @param Request|int|string $requestOrId Requête HTTP ou identifiant de l'entité
     * @return Response
     */
    public function show(Request|int|string $requestOrId): Response
    {
        try {
            $id = $requestOrId instanceof Request 
                ? $requestOrId->getRouteParam('id') 
                : $requestOrId;
            
            if ($id === null) {
                throw new \InvalidArgumentException('ID manquant dans la route');
            }
            
            $entity = $this->getOne((int)$id);
            
            if ($entity === null) {
                throw new NotFoundException("Ressource avec l'ID {$id} introuvable");
            }

            $data = $this->serializer->serialize($entity, ['read']);

            return $this->json(['data' => $data]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ApiException('Erreur lors de la récupération de la ressource', 500, $e);
        }
    }

    /**
     * Crée une nouvelle entité (POST /api/resource)
     * 
     * @param Request|array<string, mixed> $requestOrData Requête HTTP ou données de l'entité
     * @return Response
     */
    public function create(Request|array $requestOrData): Response
    {
        try {
            $data = $requestOrData instanceof Request 
                ? $this->extractDataFromRequest($requestOrData)
                : $requestOrData;
            
            if (empty($data)) {
                throw new \InvalidArgumentException('Données manquantes');
            }
            
            $entity = $this->createEntity($data);
            $this->save($entity);

            $serialized = $this->serializer->serialize($entity, ['read']);

            return $this->json(['data' => $serialized], 201);
        } catch (\Throwable $e) {
            throw new ApiException('Erreur lors de la création de la ressource', 400, $e);
        }
    }

    /**
     * Met à jour une entité (PUT /api/resource/{id})
     * 
     * @param Request|int|string $requestOrId Requête HTTP ou identifiant de l'entité
     * @param array<string, mixed>|null $data Données à mettre à jour (optionnel si Request fourni)
     * @return Response
     */
    public function update(Request|int|string $requestOrId, ?array $data = null): Response
    {
        try {
            if ($requestOrId instanceof Request) {
                $id = $requestOrId->getRouteParam('id');
                if ($id === null) {
                    throw new \InvalidArgumentException('ID manquant dans la route');
                }
                $data = $this->extractDataFromRequest($requestOrId);
            } else {
                $id = $requestOrId;
            }
            
            if ($data === null || empty($data)) {
                throw new \InvalidArgumentException('Données manquantes');
            }
            
            $entity = $this->getOne((int)$id);
            
            if ($entity === null) {
                throw new NotFoundException("Ressource avec l'ID {$id} introuvable");
            }

            $this->updateEntity($entity, $data);
            $this->save($entity);

            $serialized = $this->serializer->serialize($entity, ['read']);

            return $this->json(['data' => $serialized]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ApiException('Erreur lors de la mise à jour de la ressource', 400, $e);
        }
    }

    /**
     * Supprime une entité (DELETE /api/resource/{id})
     * 
     * @param Request|int|string $requestOrId Requête HTTP ou identifiant de l'entité
     * @return Response
     */
    public function delete(Request|int|string $requestOrId): Response
    {
        try {
            $id = $requestOrId instanceof Request 
                ? $requestOrId->getRouteParam('id') 
                : $requestOrId;
            
            if ($id === null) {
                throw new \InvalidArgumentException('ID manquant dans la route');
            }
            
            $entity = $this->getOne((int)$id);
            
            if ($entity === null) {
                throw new NotFoundException("Ressource avec l'ID {$id} introuvable");
            }

            $this->remove($entity);

            return $this->json(['message' => 'Ressource supprimée avec succès'], 204);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ApiException('Erreur lors de la suppression de la ressource', 500, $e);
        }
    }

    /**
     * Extrait les données JSON depuis une requête
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function extractDataFromRequest(Request $request): array
    {
        // Récupérer les données depuis le body
        $data = $request->getBody();
        if (empty($data)) {
            // Essayer de parser le JSON depuis le body brut
            $rawBody = $request->getRawBody();
            if (!empty($rawBody)) {
                $data = json_decode($rawBody, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Données JSON invalides: ' . json_last_error_msg());
                }
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Récupère toutes les entités
     * 
     * À surcharger dans les classes filles pour implémenter la logique métier
     * 
     * @param array<string, mixed> $queryParams Paramètres de requête
     * @return array<object>
     */
    protected function getAll(array $queryParams = []): array
    {
        // À implémenter dans les classes filles
        // Exemple avec Doctrine:
        // return $this->app()->getEntityManager()->getRepository($this->entityClass)->findAll();
        return [];
    }

    /**
     * Récupère une entité par son ID
     * 
     * À surcharger dans les classes filles
     * 
     * @param int|string $id
     * @return object|null
     */
    protected function getOne(int|string $id): ?object
    {
        // À implémenter dans les classes filles
        // Exemple avec Doctrine:
        // return $this->app()->getEntityManager()->getRepository($this->entityClass)->find($id);
        return null;
    }

    /**
     * Crée une nouvelle entité à partir des données
     * 
     * À surcharger dans les classes filles
     * 
     * @param array<string, mixed> $data
     * @return object
     */
    protected function createEntity(array $data): object
    {
        return new ($this->entityClass)($data);
    }

    /**
     * Met à jour une entité avec les données fournies
     * 
     * À surcharger dans les classes filles
     * 
     * @param object $entity
     * @param array<string, mixed> $data
     * @return void
     */
    protected function updateEntity(object $entity, array $data): void
    {
        if (method_exists($entity, 'fill')) {
            $entity->fill($data);
        } else {
            foreach ($data as $key => $value) {
                if (property_exists($entity, $key)) {
                    $entity->$key = $value;
                }
            }
        }
    }

    /**
     * Sauvegarde une entité
     * 
     * À surcharger dans les classes filles
     * 
     * @param object $entity
     * @return void
     */
    protected function save(object $entity): void
    {
        // À implémenter dans les classes filles
        // Exemple avec Doctrine:
        // $em = $this->app()->getEntityManager();
        // $em->persist($entity);
        // $em->flush();
    }

    /**
     * Supprime une entité
     * 
     * À surcharger dans les classes filles
     * 
     * @param object $entity
     * @return void
     */
    protected function remove(object $entity): void
    {
        // À implémenter dans les classes filles
        // Exemple avec Doctrine:
        // $em = $this->app()->getEntityManager();
        // $em->remove($entity);
        // $em->flush();
    }
}
