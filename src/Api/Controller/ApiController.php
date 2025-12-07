<?php

declare(strict_types=1);

namespace JulienLinard\Api\Controller;

use JulienLinard\Core\Controller\Controller;
use JulienLinard\Core\Application;
use JulienLinard\Core\Events\EventDispatcher;
use JulienLinard\Router\Response;
use JulienLinard\Router\Request;
use JulienLinard\Api\Serializer\JsonSerializer;
use JulienLinard\Api\Exception\ApiException;
use JulienLinard\Api\Exception\NotFoundException;
use JulienLinard\Api\Exception\ValidationException;
use JulienLinard\Api\Exception\ProblemDetails;
use JulienLinard\Api\Validator\ApiValidator;
use JulienLinard\Api\Event\ApiEvent;

/**
 * Contrôleur de base pour les APIs REST
 * 
 * Fournit les opérations CRUD standard
 */
abstract class ApiController extends Controller
{
    protected JsonSerializer $serializer;
    protected string $entityClass;
    protected ?ApiValidator $validator;

    /**
     * @param string $entityClass Classe de l'entité
     * @param JsonSerializer $serializer Sérialiseur JSON
     * @param ApiValidator|null $validator Validateur (optionnel)
     */
    public function __construct(string $entityClass, JsonSerializer $serializer, ?ApiValidator $validator = null)
    {
        $this->entityClass = $entityClass;
        $this->serializer = $serializer;
        $this->validator = $validator ?? new ApiValidator();
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
            
            // Gérer l'embedding de relations
            $embedRelations = [];
            if (isset($queryParams['embed']) && is_string($queryParams['embed'])) {
                $embedRelations = array_map('trim', explode(',', $queryParams['embed']));
            }
            $this->serializer->setEmbedRelations($embedRelations);
            
            // Récupérer les entités avec pagination
            $paginationResult = $this->getAllWithPagination($queryParams);
            $entities = $paginationResult['data'];
            $total = $paginationResult['total'];
            $page = $paginationResult['page'];
            $limit = $paginationResult['limit'];
            
            $data = $this->serializer->serialize($entities, ['read']);

            // Métadonnées de pagination
            $totalPages = (int)ceil($total / $limit);

            return $this->json([
                'data' => $data,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages,
                    'hasNextPage' => $page < $totalPages,
                    'hasPreviousPage' => $page > 1,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
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

            // Gérer l'embedding de relations depuis query params
            $queryParams = $requestOrId instanceof Request 
                ? $requestOrId->getQueryParams() 
                : [];
            
            $embedRelations = [];
            if (isset($queryParams['embed']) && is_string($queryParams['embed'])) {
                $embedRelations = array_map('trim', explode(',', $queryParams['embed']));
            }
            $this->serializer->setEmbedRelations($embedRelations);

            $data = $this->serializer->serialize($entity, ['read']);

            return $this->json(['data' => $data]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (ValidationException $e) {
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
            
            // Valider les données
            $this->validator->validate($data, $this->entityClass, ['create', 'Default']);
            
            // Événement pre_create
            $this->dispatchEvent(ApiEvent::PRE_CREATE, null, ['data' => $data]);
            
            $entity = $this->createEntity($data);
            $this->save($entity);
            
            // Événement post_create
            $this->dispatchEvent(ApiEvent::POST_CREATE, $entity, ['data' => $data]);

            $serialized = $this->serializer->serialize($entity, ['read']);

            return $this->json(['data' => $serialized], 201);
        } catch (ValidationException $e) {
            throw $e;
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
            
            // Valider les données
            $this->validator->validate($data, $this->entityClass, ['update', 'Default']);
            
            $entity = $this->getOne((int)$id);
            
            if ($entity === null) {
                throw new NotFoundException("Ressource avec l'ID {$id} introuvable");
            }

            // Événement pre_update
            $this->dispatchEvent(ApiEvent::PRE_UPDATE, $entity, ['data' => $data]);
            
            $this->updateEntity($entity, $data);
            $this->save($entity);
            
            // Événement post_update
            $this->dispatchEvent(ApiEvent::POST_UPDATE, $entity, ['data' => $data]);

            $serialized = $this->serializer->serialize($entity, ['read']);

            return $this->json(['data' => $serialized]);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (ValidationException $e) {
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

            // Événement pre_delete
            $this->dispatchEvent(ApiEvent::PRE_DELETE, $entity);
            
            $this->remove($entity);
            
            // Événement post_delete
            $this->dispatchEvent(ApiEvent::POST_DELETE, $entity);

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
     * Récupère toutes les entités avec pagination
     * 
     * Par défaut, appelle getAll() et calcule la pagination manuellement.
     * Peut être surchargé pour une pagination plus efficace (avec comptage séparé).
     * 
     * @param array<string, mixed> $queryParams Paramètres de requête
     * @return array{data: array<object>, total: int, page: int, limit: int}
     */
    protected function getAllWithPagination(array $queryParams = []): array
    {
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $limit = isset($queryParams['limit']) ? max(1, (int)$queryParams['limit']) : 20;
        
        // Essayer d'obtenir le total depuis getAll() si elle retourne un tableau avec 'total'
        $result = $this->getAll($queryParams);
        
        // Si getAll() retourne un tableau avec les clés 'data' et 'total', l'utiliser
        if (is_array($result) && isset($result['data']) && isset($result['total'])) {
            return [
                'data' => $result['data'],
                'total' => (int)$result['total'],
                'page' => $page,
                'limit' => $limit,
            ];
        }
        
        // Sinon, traiter comme un tableau simple d'entités
        $entities = is_array($result) ? $result : [];
        $total = count($entities);
        
        return [
            'data' => $entities,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
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
    
    /**
     * Retourne une réponse d'erreur au format Problem Details (RFC 7807)
     * 
     * @param \Throwable $exception
     * @param string|null $baseUrl URL de base pour l'instance
     * @return Response
     */
    protected function errorResponse(\Throwable $exception, ?string $baseUrl = null): Response
    {
        $problem = ProblemDetails::fromException($exception, $baseUrl);
        return $this->json($problem->toArray(), $problem->status);
    }
    
    /**
     * Dispatch un événement API via le EventDispatcher de core-php
     * 
     * @param string $eventName Nom de l'événement
     * @param object|null $entity Entité concernée
     * @param array<string, mixed> $data Données additionnelles
     * @return void
     */
    protected function dispatchEvent(string $eventName, ?object $entity = null, array $data = []): void
    {
        try {
            $app = Application::getInstance();
            if ($app === null) {
                return; // Pas d'application disponible, ignorer l'événement
            }
            
            $events = $app->getEvents();
            if ($events === null) {
                return; // Pas de EventDispatcher disponible
            }
            
            $event = new ApiEvent($eventName, $entity, array_merge($data, [
                'entityClass' => $this->entityClass,
            ]));
            
            $events->dispatch($eventName, [
                'event' => $event,
                'entity' => $entity,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            // Ne pas faire échouer la requête si le dispatch d'événement échoue
            // Log l'erreur si possible, mais continue l'exécution
        }
    }
}
