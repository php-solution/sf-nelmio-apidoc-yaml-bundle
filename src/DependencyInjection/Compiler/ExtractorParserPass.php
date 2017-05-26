<?php
namespace PhpSolution\NelmioApiDocYamlBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ExtractorParserPass
 */
class ExtractorParserPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('nelmio_api_doc_yaml.extractor.yaml')) {
            return;
        }

        $definition = $container->getDefinition('nelmio_api_doc_yaml.extractor.yaml');
        $sortedParsers = [];
        foreach ($container->findTaggedServiceIds('nelmio_api_doc.extractor.parser') as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $priority = isset($attributes['priority']) ? $attributes['priority'] : 0;
                $sortedParsers[$priority][] = $id;
            }
        }

        // add parsers
        if (!empty($sortedParsers)) {
            krsort($sortedParsers);
            $sortedParsers = call_user_func_array('array_merge', $sortedParsers);
            foreach ($sortedParsers as $id) {
                $definition->addMethodCall('addParser', [new Reference($id)]);
            }
        }
    }
}
