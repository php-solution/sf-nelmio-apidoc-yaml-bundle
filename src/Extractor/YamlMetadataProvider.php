<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Extractor;

use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

/**
 * Class YamlMetadataProvider
 */
class YamlMetadataProvider
{
    /**
     * @var array
     */
    private $bundles;
    /**
     * @var string
     */
    private $metadataFileName;
    /**
     * @var array
     */
    private $metadataList;
    /**
     * @var array
     */
    private $metadataFiles;
    /**
     * @var string
     */
    private $defaults;
    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * @param array  $bundles
     * @param string $metadataFileName
     * @param string $defaults
     */
    public function __construct(array $bundles, $metadataFileName, $defaults)
    {
        $this->bundles = $bundles;
        $this->metadataFileName = $metadataFileName;
        $this->defaults = $defaults;
    }

    /**
     * @return array
     */
    public function getMetaDataList()
    {
        if (is_null($this->metadataList)) {
            $this->metadataList = [];
            $content = '';
            foreach ($this->getMetadataFiles() as $file) {
                $content .= file_get_contents($file).PHP_EOL;
            }
            
            if (!empty($content)) {
                $parsedMetadata = Yaml::parse($content);
                foreach ($parsedMetadata as $name => $metadata) {
                    $this->metadataList[$name] = $metadata;
                }
            }
        }

        return $this->metadataList;
    }

    /**
     * @return array
     */
    public function getMetadataFiles()
    {
        if (is_array($this->metadataFiles)) {
            return $this->metadataFiles;
        }

        $this->metadataFiles = [];

        if (!empty($this->defaults)) {
            $this->metadataFiles[] = $this->fileLocator->locate($this->defaults);
        }

        foreach ($this->bundles as $name => $class) {
            if (
                strpos($class, 'Symfony\\Bundle') !== false
                || strpos($class, 'Doctrine\\Bundle') !== false
                || strpos($class, 'Sensio\\Bundle') !== false
            ) {
                continue;
            }
            $directory = dirname((new \ReflectionClass($class))->getFileName());
            $filePath = $directory
                .DIRECTORY_SEPARATOR.'Resources'
                .DIRECTORY_SEPARATOR.'config'
                .DIRECTORY_SEPARATOR.$this->metadataFileName;

            if (file_exists($filePath)) {
                if (is_dir($filePath)) {
                    $finder = new Finder();
                    foreach ($finder->files()->in($filePath) as $filePathItem) {
                        $this->metadataFiles[] = $filePathItem;
                    }
                } else {
                    $this->metadataFiles[] = $filePath;
                }
            }
        }

        return $this->metadataFiles;
    }

    /**
     * @param FileLocator $fileLocator
     */
    public function setFileLocator(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }
}