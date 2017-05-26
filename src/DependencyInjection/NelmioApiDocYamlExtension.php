<?php
namespace PhpSolution\NelmioApiDocYamlBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class NelmioApiDocYamlExtension
 */
class NelmioApiDocYamlExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter(
            'nelmio_api_doc.event_listener.request.class',
            'PhpSolution\NelmioApiDocYamlBundle\EventListener\RequestListener'
        );
        $container->getDefinition('nelmio_api_doc_yaml.yaml.provider')
            ->replaceArgument(1, $config['metadata']['file_path'])
            ->replaceArgument(2, $config['metadata']['defaults']);

        if ($config['cache']['enabled']) {
            $container->getDefinition('nelmio_api_doc_yaml.extractor.yaml_caching')
                ->replaceArgument(2, $config['cache']['file']);
            $container->setParameter('nelmio_api_doc_yaml.cache.enabled', true);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!array_key_exists('NelmioApiDocBundle', $bundles)) {
            return;
        }

        $configs = $container->getExtensionConfig('nelmio_api_doc');
        $config = $this->processConfiguration(new \Nelmio\ApiDocBundle\DependencyInjection\Configuration(), $configs);
        $container->prependExtensionConfig($this->getAlias(), ['cache' => ['enabled' => $config['cache']['enabled']]]);
    }
}
