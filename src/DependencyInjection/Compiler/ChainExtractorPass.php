<?php
namespace PhpSolution\NelmioApiDocYamlBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ChainExtractorPass
 */
class ChainExtractorPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $apiDocDefinition = $container->getDefinition('nelmio_api_doc.extractor.api_doc_extractor');
        $container->setDefinition('nelmio_api_doc.extractor.api_doc_extractor.phpdoc', $apiDocDefinition);

        $chainDefinition = $container->getDefinition('nelmio_api_doc_yaml.extractor.chain')
            ->addMethodCall('addExtractor', [new Reference('nelmio_api_doc.extractor.api_doc_extractor.phpdoc')]);

        $cacheParam = 'nelmio_api_doc_yaml.cache.enabled';
        if ($container->hasParameter($cacheParam) && $container->getParameter($cacheParam)) {
            $chainDefinition->addMethodCall('addExtractor', [new Reference('nelmio_api_doc_yaml.extractor.yaml_caching')]);
        } else {
            $chainDefinition->addMethodCall('addExtractor', [new Reference('nelmio_api_doc_yaml.extractor.yaml')]);
        }

        $container->setDefinition('nelmio_api_doc.extractor.api_doc_extractor', $chainDefinition);
    }
}