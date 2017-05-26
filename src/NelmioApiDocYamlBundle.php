<?php
namespace PhpSolution\NelmioApiDocYamlBundle;

use PhpSolution\NelmioApiDocYamlBundle\DependencyInjection\Compiler\ChainExtractorPass;
use PhpSolution\NelmioApiDocYamlBundle\DependencyInjection\Compiler\ExtractorParserPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class NelmioApiDocYamlBundle
 */
class NelmioApiDocYamlBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new ExtractorParserPass())
            ->addCompilerPass(new ChainExtractorPass(), PassConfig::TYPE_OPTIMIZE);
    }
}
