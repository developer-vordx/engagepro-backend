<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'L5 Swagger UI',  // Title of the API documentation
            ],
            'routes' => [
                /*
                 * Route for accessing API documentation interface
                 */
                'api' => 'api/documentation',  // Swagger UI route
            ],
            'paths' => [
                /*
                 * Edit to include full URL in UI for assets
                 */
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),

                /*
                 * Edit to set the path where Swagger UI assets should be stored
                 */
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),

                /*
                 * File name of the generated JSON documentation file
                 */
                'docs_json' => 'api-docs.json',

                /*
                 * Use the path of your manually created swagger.yaml
                 * Set to the full path to your swagger.yaml in the `docs` directory
                 */
                // 'docs_yaml' => 'swagger.yaml',
                'docs_yaml' => 'swagger.yaml',


                /*
                 * Set this to `json` or `yaml` to determine which documentation file to use in the UI
                 */
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'yaml'),  // Use YAML for docs

                /*
                 * Absolute paths to directory containing the Swagger annotations
                 */
                'annotations' => [
                    base_path('app'),
                ],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            /*
             * Route for accessing parsed Swagger annotations.
             */
            'docs' => 'docs',  // This is the route for the parsed Swagger annotations

            /*
             * Route for OAuth2 authentication callback.
             */
            'oauth2_callback' => 'api/oauth2-callback',  // OAuth2 callback route

            /*
             * Middleware to prevent unexpected access to API documentation
             */
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],

            /*
             * Route group options
             */
            'group_options' => [],
        ],

        'paths' => [
            /*
             * Absolute path to location where parsed annotations will be stored
             */
            'docs' => storage_path('api-docs'),  // Storage path for API docs

            /*
             * Absolute path to directory where to export views
             */
            'views' => base_path('resources/views/vendor/l5-swagger'),  // Views location

            /*
             * Edit to set the API's base path
             */
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            /*
             * Absolute path to directories that should be excluded from scanning
             * @deprecated Please use `scanOptions.exclude`
             * `scanOptions.exclude` overwrites this
             */
            'excludes' => [],
        ],

        'scanOptions' => [
            /*
             * Default processors configuration. Allows to pass processor configurations to Swagger-php.
             */
            'default_processors_configuration' => [],

            /*
             * The analyser used for scanning.
             */
            'analyser' => null,

            /*
             * The analysis object used for scanning.
             */
            'analysis' => null,

            /*
             * Custom query path processors classes.
             */
            'processors' => [
                // Example: new \App\SwaggerProcessors\SchemaQueryParameter(),
            ],

            /*
             * File pattern(s) to scan (default: *.php)
             */
            'pattern' => null,

            /*
             * Directories to exclude from scanning
             * This option overwrites `paths.excludes`
             */
            'exclude' => [],

            /*
             * Allows generating specs for OpenAPI 3.0.0 or 3.1.0 (default is 3.0.0)
             */
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],

        /*
         * API security definitions. Will be generated into documentation file.
        */
        'securityDefinitions' => [
            'securitySchemes' => [
                'Bearer' => [
                    'type' => 'apiKey',
                    'description' => 'Enter JWT Token as: Bearer {token}',
                    'name' => 'Authorization',
                    'in' => 'header',
                ],
            ],
            'security' => [
                [
                    'Bearer' => [],
                ],
            ],
        ],

        /*
         * Always regenerate docs (useful for development)
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),

        /*
         * Option to generate a YAML copy of the documentation
         */
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', true),

        /*
         * Trust the proxy IP (needed for AWS Load Balancer)
         */
        'proxy' => false,

        /*
         * Additional configurations for Swagger UI (configs plugin)
         * Fetch external configs instead of passing them to SwaggerUIBundle.
         * See more at: https://github.com/swagger-api/swagger-ui#configs-plugin
         */
        'additional_config_url' => null,

        /*
         * Sort order for operations list in Swagger UI
         * Options are: 'alpha' (sort by paths alphabetically), 'method' (sort by HTTP method)
         */
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),

        /*
         * Validator URL for Swagger UI (set null to disable validation)
         */
        'validator_url' => null,

        /*
         * Swagger UI configuration parameters
         */
        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),  // Enable filtering in Swagger UI
            ],

            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        /*
         * Constants for annotations (can be used in Swagger annotations)
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://my-default-host.com'),
        ],
    ],
];
