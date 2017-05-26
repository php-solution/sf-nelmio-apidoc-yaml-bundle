<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Route;

/**
 * Class YamlApiDocExtractor
 */
class YamlApiDocExtractor extends AbstractApiDocExtractor
{
    /**
     * @var YamlMetadataProvider
     */
    private $metadataProvider;
    /**
     * @var Router
     */
    private $router;

    /**
     * @param YamlMetadataProvider $metadataProvider
     * @param Router               $router
     */
    public function __construct(YamlMetadataProvider $metadataProvider, Router $router)
    {
        $this->metadataProvider = $metadataProvider;
        $this->router = $router;
    }

    /**
     * @param string $view
     *
     * @return array
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        $result = [];
        $routeCollection = $this->router->getRouteCollection();
        foreach ($this->metadataProvider->getMetaDataList() as $routeName => $metadata) {
            $route = $routeCollection->get($routeName);
            if (!$route instanceof Route) {
                continue;
            }
            $apiDoc = $this->createApiDocFromMetadata($metadata, $route);
            $result[] = ['annotation' => $apiDoc, 'resource' => 'others'];
        }

        return $result;
    }

    /**
     * @param string $controller
     * @param string $route
     *
     * @return ApiDoc|null
     */
    public function get($controller, $route)
    {
        $result = null;
        $metadataList = $this->metadataProvider->getMetaDataList();
        if (
            array_key_exists($route, $metadataList)
            && ($routerRoute = $this->router->getRouteCollection()->get($route)) instanceof Route
        ) {
            $result = $this->createApiDocFromMetadata($metadataList[$route], $routerRoute);
        }

        return $result;
    }

    /**
     * @param array $metadata
     * @param Route $route
     *
     * @return array
     */
    public function createApiDocFromMetadata(array $metadata, Route $route)
    {
        $result = new ApiDoc($metadata);
        $result->setRoute($route);
        if (isset($metadata['documentation'])) {
            $result->setDocumentation($metadata['documentation']);
        }
        $this->initApiDocParameters($result);
        $this->initApiDocResponse($result);
        $this->initApiDocResponseStatusCode($result);

        return $result;
    }
}