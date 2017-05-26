<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class ChainApiDocExtractor
 */
class ChainApiDocExtractor implements ApiDocExtractorInterface
{
    /**
     * @var \ArrayObject
     */
    private $extractors;

    /**
     * @return \ArrayObject|ApiDocExtractorInterface[]
     */
    public function getExtractors()
    {
        if (!$this->extractors instanceof \ArrayObject) {
            $this->extractors = new \ArrayObject();
        }

        return $this->extractors;
    }

    /**
     * @param mixed $extractor
     */
    public function addExtractor($extractor)
    {
        $this->getExtractors()->append($extractor);
    }

    /**
     * @param string $view
     *
     * @return array
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        $result = [];
        foreach ($this->getExtractors() as $extractor) {
            $extractorResult = $extractor->all($view);
            $result = array_merge($extractorResult, $result);
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
        foreach ($this->getExtractors() as $extractor) {
            if (!is_null($result = $extractor->get($controller, $route))) {
                break;
            }
        }

        return $result;
    }
}