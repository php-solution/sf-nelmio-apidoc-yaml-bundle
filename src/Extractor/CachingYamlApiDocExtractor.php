<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Class CachingYamlApiDocExtractor
 */
class CachingYamlApiDocExtractor implements ApiDocExtractorInterface
{
    /**
     * @var YamlMetadataProvider
     */
    private $metadataProvider;
    /**
     * @var YamlApiDocExtractor
     */
    private $extractor;
    /**
     * @var string
     */
    private $cacheFile;
    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @param YamlMetadataProvider $metadataProvider
     * @param YamlApiDocExtractor  $extractor
     * @param string               $cacheFile
     * @param bool|false           $debug
     */
    public function __construct(YamlMetadataProvider $metadataProvider, YamlApiDocExtractor $extractor, $cacheFile, $debug = false)
    {
        $this->metadataProvider = $metadataProvider;
        $this->extractor = $extractor;
        $this->cacheFile = $cacheFile;
        $this->debug = $debug;
    }

    /**
     * @param string $controller
     * @param string $route
     *
     * @return ApiDoc|null
     */
    public function get($controller, $route)
    {
        return $this->extractor->get($controller, $route);
    }

    /**
     * Extracts annotations from all known routes
     *
     * @param string $view
     *
     * @return array
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        $cache = new ConfigCache($this->cacheFile, $this->debug);
        if (false === $cache->isFresh()) {
            $cacheResources = [];
            foreach ($this->metadataProvider->getMetadataFiles() as $metadataFile) {
                $cacheResources[] = new FileResource($metadataFile);
            }
            $data = $this->extractor->all($view);
            $cache->write(serialize($data), $cacheResources);

            return $data;
        }

        return unserialize(file_get_contents($this->cacheFile));
    }
}