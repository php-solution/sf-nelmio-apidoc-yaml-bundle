# NelmioApiDocYamlBundle

This bundle allows you to use NelmioApiDocBundle and store all apidocs on yml config files.

Bundle includes 2 new apidoc extractors: 
* Chain extractor 
* Yaml extractor.

Bundle change definition for standard api doc extractor and now @nelmio_api_doc.extractor.api_doc_extractor definition of PhpSolution\NelmioApiDocYamlBundle\Extractor\ChainApiDocExtractor.

You can use standard ApiDocExtractor with @nelmio_api_doc.extractor.api_doc_extractor.phpdoc service name.

Chain extractor use Yaml extractor and phpdoc extractor.

Yaml extractor check all @Bundle\Resources\config\api_doc.yml and create config for apidoc.

All configuration parameters you can find on https://github.com/nelmio/NelmioApiDocBundle documentation. 

## Using
Example of yml config:
````
rest_api_security_login_check: # route name
    section: "Authorization"
    resource: false
    description: ""
    https: false
    deprecated: false
    tags: []
    filters: []
    output:
        class: PhpSolution\NelmioApiDocYamlBundle\Form\SimpleType
        options:
            fields:
                - {name: "token",  type: "text"}
                - {name: "expDate",  type: "integer", options: {description: "timestamp"}}
                - {name: "expDateOffset",  type: "integer", options: {description: "time offset"}}
    requirements:
        - { name: "username",  dataType: "string"}
        - { name: "password",  dataType: "string"}
    statusCodes: {"200": "Returned when successful", 401: "Returned when the user is not authorized"}
    views: []
    documentation: 'Get authorization token for access to api endpoints'
    authentication: true
````

If you want to define input options without creation new class for apidoc you can use like this:
````        
requirements:
    - { name: "username",  dataType: "string"}
    - { name: "password",  dataType: "string"}
````

If yo want to define output parameters without creation new class for apidoc you can use like this:
````
output:
    class: PhpSolution\NelmioApiDocYamlBundle\Form\SimpleType
    options:
        fields:
            - {name: "token",  type: "text"}
            - {name: "expDate",  type: "integer", options: {description: "timestamp"}}
            - {name: "expDateOffset",  type: "integer", options: {description: "time offset"}}
````

## Config
1) Add route to app/config/routing.yml
````
NelmioApiDocBundle:
    resource: "@NelmioApiDocBundle/Resources/config/routing.yml"
    prefix:   /rest-api/doc 
````   
2) If you want change default setting of bundle you can change it on app/config/config.yml:
````    
    ...
    nelmio_api_doc_yaml:
        metadata:
            file_path: api_doc.yml
            defaults: ~ #path to defaults
        cache:
            enabled: false
            file: %kernel.cache_dir%/api-doc-yml.cache
````

## Installing
1. Add to your composer.json
````
    "require": {
        ...
        "php-solution/nelmio-apidoc-yaml-bundle": "dev-master"
        ...
    }
````
2. Add to AppKernel.php:
````
    public function registerBundles()
    {
        $bundles = array(
        ...
        new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
        new PhpSolution\NelmioApiDocYamlBundle\CiklumNelmioApiDocYamlBundle(),
        ...
````
3. run: 
````
    composer update php-solution/nelmio-apidoc-yaml-bundle
````

## TODO
* Coverage more than 80%