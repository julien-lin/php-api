<?php

declare(strict_types=1);

namespace JulienLinard\Api\Controller;

use JulienLinard\Core\Controller\Controller;
use JulienLinard\Router\Response;
use JulienLinard\Api\Swagger\SwaggerGenerator;

/**
 * Contrôleur pour servir la documentation Swagger/OpenAPI
 */
class SwaggerController extends Controller
{
    private SwaggerGenerator $generator;
    private array $entityClasses;
    private string $title;
    private string $version;
    private string $basePath;

    /**
     * @param array<string> $entityClasses Liste des classes d'entités exposées
     * @param string $title Titre de l'API
     * @param string $version Version de l'API
     * @param string $basePath Chemin de base de l'API
     */
    public function __construct(
        array $entityClasses,
        string $title = 'API',
        string $version = '1.0.0',
        string $basePath = '/api'
    ) {
        $this->generator = new SwaggerGenerator();
        $this->entityClasses = $entityClasses;
        $this->title = $title;
        $this->version = $version;
        $this->basePath = $basePath;
    }

    /**
     * Affiche l'interface Swagger UI
     * 
     * GET /api/docs
     */
    public function ui(): Response
    {
        $specUrl = $this->basePath . '/docs.json';
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->title} - Documentation API</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '{$specUrl}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                tryItOutEnabled: true
            });
        };
    </script>
</body>
</html>
HTML;

        return new Response(200, $html, ['Content-Type' => 'text/html']);
    }

    /**
     * Retourne la spécification OpenAPI en JSON
     * 
     * GET /api/docs.json
     */
    public function getJson(): Response
    {
        $spec = $this->generator->generate(
            $this->entityClasses,
            $this->title,
            $this->version,
            $this->basePath
        );

        return parent::json($spec);
    }

    /**
     * Retourne la spécification OpenAPI en YAML
     * 
     * GET /api/docs.yaml
     */
    public function yaml(): Response
    {
        $spec = $this->generator->generate(
            $this->entityClasses,
            $this->title,
            $this->version,
            $this->basePath
        );

        $yaml = $this->arrayToYaml($spec);
        
        return new Response(200, $yaml, ['Content-Type' => 'application/x-yaml']);
    }

    /**
     * Convertit un tableau en YAML (version simple)
     */
    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    $yaml .= "{$spaces}{$key}:\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                } else {
                    foreach ($value as $item) {
                        $yaml .= "{$spaces}- ";
                        if (is_array($item)) {
                            $yaml .= "\n" . $this->arrayToYaml($item, $indent + 1);
                        } else {
                            $yaml .= $this->formatYamlValue($item) . "\n";
                        }
                    }
                }
            } else {
                $yaml .= "{$spaces}{$key}: " . $this->formatYamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * Vérifie si un tableau est associatif
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Formate une valeur pour YAML
     */
    private function formatYamlValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_string($value) && (str_contains($value, ':') || str_contains($value, ' ') || str_contains($value, "\n"))) {
            return '"' . addcslashes($value, '"\\') . '"';
        }
        return (string)$value;
    }
}
