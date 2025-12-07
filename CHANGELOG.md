# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

## [0.1.0] - 2025-01-XX

### Ajouté
- Annotation `ApiResource` pour exposer des entités en API
- Annotation `ApiProperty` pour configurer la sérialisation des propriétés
- Annotation `Groups` pour définir les groupes de sérialisation
- Classe `JsonSerializer` pour la sérialisation JSON automatique
- Classe `ApiController` avec opérations CRUD standard
- Exceptions `ApiException` et `NotFoundException`
- Support des groupes de sérialisation (`read`, `write`)
- Documentation complète en français et anglais
- Exemples d'utilisation
- Tests unitaires de base

### Structure
- `/src/Api/Annotation/` - Annotations pour les entités et propriétés
- `/src/Api/Serializer/` - Sérialisation JSON
- `/src/Api/Controller/` - Contrôleurs API de base
- `/src/Api/Exception/` - Exceptions API
