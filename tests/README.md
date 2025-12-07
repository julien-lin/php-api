# Tests unitaires php-api

## Structure des tests

```
tests/
├── Controller/
│   ├── ApiControllerTest.php          # Tests du contrôleur API (pagination, événements)
│   └── SubresourceControllerTest.php  # Tests des sous-ressources
├── Event/
│   └── ApiEventTest.php               # Tests des événements API
├── Exception/
│   ├── ApiExceptionTest.php           # Tests des exceptions API
│   ├── ProblemDetailsTest.php         # Tests du format Problem Details
│   └── ValidationExceptionTest.php    # Tests des exceptions de validation
├── Filter/
│   ├── BooleanFilterTest.php          # Tests du filtre booléen
│   ├── DateFilterTest.php             # Tests du filtre de dates
│   ├── FilterManagerTest.php          # Tests du gestionnaire de filtres
│   ├── OrderFilterTest.php             # Tests du filtre de tri
│   ├── RangeFilterTest.php             # Tests du filtre de plages
│   ├── SearchFilterTest.php            # Tests du filtre de recherche
│   └── TestQueryBuilder.php           # Mock QueryBuilder pour les tests
├── Serializer/
│   ├── JsonSerializerRelationsTest.php # Tests sérialisation avec relations
│   └── RelationSerializerTest.php      # Tests du sérialiseur de relations
├── Swagger/
│   └── SwaggerGeneratorFiltersTest.php # Tests Swagger avec filtres
├── Validator/
│   └── ApiValidatorTest.php           # Tests du validateur
├── SwaggerGeneratorTest.php            # Tests du générateur Swagger
└── README.md                           # Ce fichier
```

## Exécution des tests

### Tous les tests

```bash
php vendor/bin/phpunit
```

### Tests avec affichage détaillé

```bash
php vendor/bin/phpunit --testdox
```

### Tests d'une suite spécifique

```bash
# Tests des filtres
php vendor/bin/phpunit tests/Filter/

# Tests de validation
php vendor/bin/phpunit tests/Validator/

# Tests des exceptions
php vendor/bin/phpunit tests/Exception/
```

### Tests d'un fichier spécifique

```bash
php vendor/bin/phpunit tests/Filter/SearchFilterTest.php
```

## Couverture des tests

### Tests des filtres (36 tests)

- ✅ **SearchFilter** : 7 tests
  - Stratégies (exact, partial, start, end, word_start)
  - Application depuis query params (string et array)
  - Gestion des valeurs vides

- ✅ **BooleanFilter** : 5 tests
  - Valeurs true/false
  - Conversion depuis strings
  - Gestion des valeurs invalides

- ✅ **DateFilter** : 6 tests
  - Stratégies (exact, before, after)
  - Application depuis query params
  - Gestion des dates invalides

- ✅ **RangeFilter** : 8 tests
  - Stratégies (gt, gte, lt, lte, between)
  - Application depuis query params
  - Conversion des valeurs numériques

- ✅ **OrderFilter** : 5 tests
  - Tri ASC/DESC
  - Tri multi-colonnes
  - Gestion des directions invalides
  - Validation des noms de propriétés

- ✅ **FilterManager** : 5 tests
  - Enregistrement des filtres d'entités
  - Application des filtres
  - Gestion du tri
  - Entités sans filtres

### Tests de validation (6 tests)

- ✅ **ApiValidator** : 6 tests
  - Validation réussie
  - Propriétés requises manquantes
  - Types incorrects
  - Propriétés optionnelles
  - Validation par groupes
  - Récupération des violations

### Tests des exceptions (9 tests)

- ✅ **ApiException** : 5 tests
  - Constructeur
  - Code de statut
  - Valeur par défaut
  - Exception précédente
  - NotFoundException

- ✅ **ValidationException** : 4 tests
  - Constructeur avec violations
  - Constructeur sans violations
  - Ajout de violations
  - Récupération des violations

- ✅ **ProblemDetails** : 9 tests
  - Constructeur
  - Conversion en tableau
  - Champs optionnels
  - Conversion depuis ApiException
  - Conversion depuis ValidationException
  - Conversion depuis NotFoundException
  - Conversion depuis exception générique
  - URL de base
  - Types depuis codes de statut

### Tests du contrôleur (12 tests)

- ✅ **ApiController** : 12 tests
  - Index avec array et Request
  - Show avec ID
  - Show not found
  - Create avec array et Request
  - Create validation error
  - Update avec ID et données
  - Update not found
  - Delete
  - Delete not found
  - Error response

### Tests Swagger (6 tests)

- ✅ **SwaggerGenerator** : 3 tests
  - Génération de la spec
  - Chemins présents
  - Schémas présents

- ✅ **SwaggerGeneratorFilters** : 3 tests
  - Génération avec filtres
  - Structure des paramètres de filtres
  - Paramètre de tri

### Tests des relations (14 tests)

- ✅ **RelationSerializer** : 14 tests
  - Détection des relations (ManyToOne, OneToMany)
  - Récupération des annotations (ApiSubresource, ManyToOne, OneToMany)
  - Sérialisation avec profondeur
  - Extraction d'IDs
  - Gestion des collections

### Tests de sérialisation avec relations (7 tests)

- ✅ **JsonSerializerRelations** : 7 tests
  - Sérialisation sans embedding
  - Sérialisation avec embedding
  - Sérialisation avec plusieurs relations
  - Gestion de la profondeur maximale
  - Sérialisation de tableaux d'entités

### Tests des événements (4 tests)

- ✅ **ApiEvent** : 4 tests
  - Constantes d'événements
  - Constructeur avec entité
  - Constructeur sans entité
  - Données vides

### Tests du contrôleur amélioré (18 tests)

- ✅ **ApiController** : 18 tests (12 existants + 6 nouveaux)
  - Pagination avec métadonnées
  - Embedding de relations
  - Dispatch d'événements (create, update, delete)
  - Tests existants (index, show, create, update, delete)

### Tests des sous-ressources (10 tests)

- ✅ **SubresourceController** : 10 tests
  - Conversion nom de ressource → classe
  - Récupération de valeurs de relations
  - Recherche dans les relations
  - Gestion des erreurs

## Statistiques

- **Total de tests** : 119 (78 existants + 41 nouveaux)
- **Assertions** : 245 (136 existantes + 109 nouvelles)
- **Taux de réussite** : 100% ✅

## Notes

- Les tests utilisent un `TestQueryBuilder` mock pour simuler le comportement du QueryBuilder
- Les constantes des filtres sont publiques pour faciliter les tests
- Les tests couvrent les cas de succès et d'échec
- Les tests vérifient la compatibilité avec différents QueryBuilders (doctrine-php et Doctrine DBAL)
