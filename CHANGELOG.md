# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

## [1.3.2] - 2025-01-07

### 🐛 Corrections

- **ApiController** : Amélioration de la gestion des erreurs pour inclure le message d'erreur original
  - Le message d'erreur original est maintenant inclus dans l'ApiException pour faciliter le debugging
  - Format : "Erreur lors de la récupération des ressources: [message original]"
- **ProblemDetails** : Ajout des détails de l'exception précédente dans les extensions
  - En cas d'exception chaînée, les détails (message, fichier, ligne, trace) sont inclus dans `extensions.previous`
  - Facilite le debugging en mode production

## [1.3.1] - 2025-01-07

### 🐛 Corrections

- **SwaggerGenerator** : Correction de l'affichage des paramètres de filtres dans Swagger UI
  - Suppression de `deepObject` avec `explode: true` qui générait tous les sous-paramètres par défaut
  - Simplification des paramètres de filtres (SearchFilter, DateFilter, RangeFilter) en format string
  - Correction du paramètre `order` pour éviter les URLs trop longues
  - Résolution de l'erreur 500 lors de l'exécution depuis Swagger UI

## [1.3.0] - 2025-01-07

### ✨ Nouvelles fonctionnalités

#### Relations et sous-ressources (Phase 4)

- **ApiSubresource** : Annotation pour configurer les relations exposées
- **RelationSerializer** : Sérialisation des relations Doctrine avec profondeur configurable
- **Embedding de relations** : Paramètre `embed` dans query params pour inclure les relations
  - Format : `GET /api/products?embed=category,orderItems`
  - Support des relations ManyToOne et OneToMany
  - Profondeur maximale configurable via `ApiSubresource`
- **SubresourceController** : Contrôleur pour accéder aux relations via routes dédiées
  - `GET /api/{resource}/{id}/{subresource}` : Collection de sous-ressources
  - `GET /api/{resource}/{id}/{subresource}/{subId}` : Élément spécifique
- **Intégration Swagger** : Paramètre `embed` documenté automatiquement

#### Système d'événements (Phase 7)

- **ApiEvent** : Classe d'événement API avec constantes standardisées
- **Intégration EventDispatcher** : Utilisation du `EventDispatcher` de `core-php`
- **Événements disponibles** :
  - `api.pre_create` / `api.post_create`
  - `api.pre_update` / `api.post_update`
  - `api.pre_delete` / `api.post_delete`
- **Dispatch automatique** : Événements déclenchés automatiquement dans `ApiController`

#### Pagination améliorée

- **Métadonnées complètes** : Format de réponse enrichi avec métadonnées de pagination
  - `total` : Nombre total d'éléments
  - `page` : Page actuelle
  - `limit` : Nombre d'éléments par page
  - `totalPages` : Nombre total de pages
  - `hasNextPage` : Indicateur de page suivante
  - `hasPreviousPage` : Indicateur de page précédente
- **Support du comptage séparé** : Méthode `getAllWithPagination()` pour pagination efficace

### 🔧 Améliorations

- **JsonSerializer** : Support des relations avec embedding et profondeur
- **ApiController** : Gestion automatique de l'embedding depuis query params
- **SwaggerGenerator** : Documentation du paramètre `embed`
- **Compatibilité** : S'adapte parfaitement à `core-php` et `doctrine-php`

### 🧪 Tests

- **41 nouveaux tests unitaires** pour les nouvelles fonctionnalités
  - 14 tests pour `RelationSerializer`
  - 7 tests pour `JsonSerializer` avec relations
  - 4 tests pour `ApiEvent`
  - 6 tests supplémentaires pour `ApiController` (pagination, embedding, événements)
  - 10 tests pour `SubresourceController`
- **Total : 119 tests** (78 existants + 41 nouveaux)
- **245 assertions** (136 existantes + 109 nouvelles)
- **Taux de réussite : 100%** ✅

### 📝 Documentation

- Nouveau document : `documentation/IMPLEMENTATION_COMPLETE.md`
- `README.md` et `README.fr.md` mis à jour avec les relations et événements
- `tests/README.md` mis à jour avec les nouveaux tests
- Exemples d'utilisation des relations et événements

## [1.2.0] - 2025-01-07

### ✨ Nouvelles fonctionnalités

#### Suite complète de tests unitaires

- **78 tests unitaires** couvrant toutes les fonctionnalités
- **136 assertions** pour garantir la qualité du code
- Tests pour tous les filtres (SearchFilter, DateFilter, RangeFilter, BooleanFilter, OrderFilter)
- Tests pour le FilterManager
- Tests pour ApiValidator et ValidationException
- Tests pour ProblemDetails et ApiException
- Tests pour ApiController (toutes les opérations CRUD)
- Tests pour SwaggerGenerator avec filtres
- Mock QueryBuilder pour les tests (TestQueryBuilder)

### 🔧 Améliorations

- **Constantes publiques** dans les filtres pour faciliter les tests
- **Compatibilité QueryBuilder** : Support à la fois doctrine-php et Doctrine DBAL
- **Documentation des tests** : README.md dans le dossier tests/
- **TestQueryBuilder** : Mock réutilisable pour tous les tests de filtres

### 📝 Documentation

- Ajout de `tests/README.md` avec documentation complète des tests
- Structure des tests documentée
- Exemples d'exécution des tests

## [1.1.0] - 2025-01-07

### ✨ Nouvelles fonctionnalités

#### Système de filtres avancé (Phase 2)

- **SearchFilter** : Recherche textuelle avec stratégies (exact, partial, start, end, word_start)
- **DateFilter** : Filtrage par dates (exact, before, after)
- **RangeFilter** : Filtrage par plages numériques (gt, gte, lt, lte, between)
- **BooleanFilter** : Filtrage booléen
- **OrderFilter** : Tri multi-colonnes (asc, desc)
- **FilterManager** : Gestionnaire automatique des filtres
- **Annotation ApiFilter** : Définition des filtres sur les entités

#### Validation automatique (Phase 3)

- **ApiValidator** : Validation automatique des données entrantes
- **ValidationException** : Exception spécialisée pour les erreurs de validation
- Validation des types (int, float, bool, string, array)
- Validation des propriétés requises
- Validation par groupes (create, update, Default)
- Messages d'erreur structurés

#### Gestion d'erreurs standardisée (Phase 5)

- **ProblemDetails** : Format RFC 7807 pour les erreurs
- **ApiException::getStatusCode()** : Méthode pour récupérer le code HTTP
- Conversion automatique des exceptions en Problem Details
- Support des codes HTTP standards (400, 401, 403, 404, 422, 500)
- Extensions pour les violations de validation

#### Documentation Swagger améliorée

- Paramètres de filtres automatiquement documentés
- Paramètres de tri documentés
- Exemples de requêtes avec filtres
- Support des filtres dans l'interface Swagger UI

### 🔧 Améliorations

- Intégration du FilterManager dans les contrôleurs
- Support des union types dans le Router (Request|array)
- Méthode `errorResponse()` dans ApiController
- Documentation mise à jour avec exemples de filtres

### 📝 Documentation

- Nouveau document : `documentation/ANALYSE_API_PLATFORM.md`
- Nouveau document : `documentation/FONCTIONNALITES_PRODUCTION.md`
- README mis à jour avec les nouvelles fonctionnalités
- Exemples d'utilisation des filtres

### 🐛 Corrections

- Correction du conflit de méthode `json()` dans SwaggerController
- Support des union types dans le Router pour l'injection de Request

## [1.0.3] - 2025-01-07

### 🐛 Corrections

- Renommage de `json()` en `getJson()` dans SwaggerController pour éviter le conflit avec la méthode du parent Controller

## [1.0.2] - 2025-01-07

### ✨ Nouvelles fonctionnalités

- Support de `Request` dans `ApiController` pour intégration avec le Router
- Toutes les méthodes acceptent maintenant `Request|type` comme premier paramètre
- Extraction automatique des données depuis Request

## [1.0.1] - 2025-01-07

### ✨ Nouvelles fonctionnalités

- Documentation Swagger/OpenAPI automatique
- SwaggerGenerator pour générer la spec OpenAPI 3.0
- SwaggerController pour servir l'interface Swagger UI
- Export JSON et YAML

## [1.0.0] - 2025-01-07

### ✨ Première version

- Annotations ApiResource et ApiProperty
- Sérialisation JSON avec groupes
- Contrôleur de base ApiController
- Opérations CRUD automatiques
- Pagination basique
- Intégration avec Core PHP
