<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Interface ApiDocExtractorInterface
 *
 * @package PhpSolution\NelmioApiDocYamlBundle\Extractor
 */
interface ApiDocExtractorInterface
{
    /**
     * @param string $controller
     * @param string $route
     *
     * @return ApiDoc|null
     */
    public function get($controller, $route);

    /**
     * Extracts annotations from all known routes
     *
     * @param string $view
     *
     * @return array
     */
    public function all($view = ApiDoc::DEFAULT_VIEW);
}