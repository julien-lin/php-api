<?php

declare(strict_types=1);

namespace JulienLinard\Api\Controller;

use JulienLinard\Core\Controller\Controller;
use JulienLinard\Router\Response;
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
     * @param array<string, mixed> $queryParams Paramètres de requête (pagination, filtres)
     * @return Response
     */
    public function index(array $queryParams = []): Response
    {
        try {
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
     * @param int|string $id Identifiant de l'entité
     * @return Response
     */
    public function show(int|string $id): Response
    {
        try {
            $entity = $this->getOne($id);
            
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
     * @param array<string, mixed> $data Données de l'entité
     * @return Response
     */
    public function create(array $data): Response
    {
        try {
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
     * @param int|string $id Identifiant de l'entité
     * @param array<string, mixed> $data Données à mettre à jour
     * @return Response
     */
    public function update(int|string $id, array $data): Response
    {
        try {
            $entity = $this->getOne($id);
            
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
     * @param int|string $id Identifiant de l'entité
     * @return Response
     */
    public function delete(int|string $id): Response
    {
        try {
            $entity = $this->getOne($id);
            
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
