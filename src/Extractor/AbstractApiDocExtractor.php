<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Nelmio\ApiDocBundle\Parser\PostParserInterface;

/**
 * Class AbstractApiDocExtractor
 */
abstract class AbstractApiDocExtractor implements ApiDocExtractorInterface
{
    /**
     * @var array
     */
    protected $parsers = [];

    /**
     * @param ParserInterface $parser
     */
    public function addParser(ParserInterface $parser)
    {
        $this->parsers[] = $parser;
    }

    /**
     * @param array $parameters
     *
     * @return array|ParserInterface[]
     */
    protected function getParsers(array $parameters)
    {
        if (isset($parameters['parsers'])) {
            $parsers = [];
            foreach ($this->parsers as $parser) {
                if (in_array(get_class($parser), $parameters['parsers'])) {
                    $parsers[] = $parser;
                }
            }
        } else {
            $parsers = $this->parsers;
        }

        return $parsers;
    }

    /**
     * input (populates 'parameters' for the formatters)
     *
     * @param ApiDoc $apiDoc
     */
    protected function initApiDocParameters(ApiDoc $apiDoc)
    {
        $input = $apiDoc->getInput();
        if (is_null($input)) {
            return;
        }

        $normalizedInput = $this->normalizeClassParameter($input);
        $parameters = $this->getParametersByNormalized($normalizedInput);
        if ('PUT' === $apiDoc->getMethod()) {
            // All parameters are optional with PUT (update)
            array_walk(
                $parameters, function ($val, $key) use (&$parameters) {
                $parameters[$key]['required'] = false;
            }
            );
        }

        $apiDoc->setParameters($parameters);
    }

    /**
     * input (populates 'parameters' for the formatters)
     *
     * @param ApiDoc $apiDoc
     */
    protected function initApiDocResponse(ApiDoc $apiDoc)
    {
        $output = $apiDoc->getOutput();
        if (is_null($output)) {
            return;
        }
        $normalizedOutput = $this->normalizeClassParameter($output);
        $parameters = $this->getParametersByNormalized($normalizedOutput);

        $apiDoc->setResponse($parameters);
        $apiDoc->setResponseForStatusCode($parameters, $normalizedOutput, 200);
    }

    /**
     * @param ApiDoc $apiDoc
     */
    protected function initApiDocResponseStatusCode(ApiDoc $apiDoc)
    {
        if (count($apiDoc->getResponseMap()) === 0) {
            return;
        }

        foreach ($apiDoc->getResponseMap() as $code => $modelName) {
            if ('200' === (string)$code && isset($modelName['type']) && isset($modelName['model'])) {
                // Model was already parsed as the default `output` for this ApiDoc.
                continue;
            }
            $normalizedModel = $this->normalizeClassParameter($modelName);
            $response = $this->getParametersByNormalized($normalizedModel);
            $apiDoc->setResponseForStatusCode($response, $normalizedModel, $code);
        }
    }

    /**
     * @param array $normalizedInput
     *
     * @return array
     */
    protected function getParametersByNormalized(array $normalizedInput)
    {
        $result = [];
        $supportedParsers = [];
        foreach ($this->getParsers($normalizedInput) as $parser) {
            if ($parser->supports($normalizedInput)) {
                $supportedParsers[] = $parser;
                $result = $this->mergeParameters($result, $parser->parse($normalizedInput));
            }
        }
        foreach ($supportedParsers as $parser) {
            if ($parser instanceof PostParserInterface) {
                $postParseResult = $parser->postParse($normalizedInput, $result);
                $result = $this->mergeParameters($result, $postParseResult);
            }
        }
        $result = $this->clearClasses($result);

        return $this->generateHumanReadableTypes($result);
    }

    /**
     * Clears the temporary 'class' parameter from the parameters array before it is returned.
     *
     * @param  array $array The source array.
     *
     * @return array The cleared array.
     */
    protected function clearClasses($array)
    {
        if (is_array($array)) {
            unset($array['class']);
            foreach ($array as $name => $item) {
                $array[$name] = $this->clearClasses($item);
            }
        }

        return $array;
    }

    /**
     * @param string $input
     *
     * @return array
     */
    protected function normalizeClassParameter($input)
    {
        // normalize strings
        if (is_string($input)) {
            $input = ['class' => $input];
        }

        $collectionData = [];
        /*
         * Match array<Fully\Qualified\ClassName> as alias; "as alias" optional.
         */
        if (preg_match_all(
            "/^array<([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*)>(?:\\s+as\\s+(.+))?$/",
            $input['class'],
            $collectionData
        )) {
            $input['class'] = $collectionData[1][0];
            $input['collection'] = true;
            $input['collectionName'] = $collectionData[2][0];
        } elseif (preg_match('/^array</', $input['class'])) { //See if a collection directive was attempted. Must be malformed.
            throw new \InvalidArgumentException(
                sprintf(
                    'Malformed collection directive: %s. ' .
                    'Proper format is: array<Fully\\Qualified\\ClassName> or array<Fully\\Qualified\\ClassName> as collectionName',
                    $input['class']
                )
            );
        }

        // normalize groups
        if (isset($input['groups']) && is_string($input['groups'])) {
            $input['groups'] = array_map('trim', explode(',', $input['groups']));
        }

        return array_merge(['class' => '', 'groups' => [], 'options' => []], $input);
    }

    /**
     * @param  array $p1
     * @param  array $p2
     *
     * @return array
     */
    protected function mergeParameters($p1, $p2)
    {
        $params = $p1;
        foreach ($p2 as $propname => $propvalue) {
            if ($propvalue === null) {
                unset($params[$propname]);
                continue;
            }

            if (!isset($p1[$propname])) {
                $params[$propname] = $propvalue;
            } elseif (is_array($propvalue)) {
                $v1 = $p1[$propname];

                foreach ($propvalue as $name => $value) {
                    if (is_array($value)) {
                        if (isset($v1[$name]) && is_array($v1[$name])) {
                            $v1[$name] = $this->mergeParameters($v1[$name], $value);
                        } else {
                            $v1[$name] = $value;
                        }
                    } elseif (!is_null($value)) {
                        if (in_array($name, ['required', 'readonly'])) {
                            $v1[$name] = $v1[$name] || $value;
                        } elseif (in_array($name, ['requirement'])) {
                            if (isset($v1[$name])) {
                                $v1[$name] .= ', ' . $value;
                            } else {
                                $v1[$name] = $value;
                            }
                        } elseif ($name == 'default') {
                            $v1[$name] = $value ?: $v1[$name];
                        } else {
                            $v1[$name] = $value;
                        }
                    }
                }

                $params[$propname] = $v1;
            }
        }

        return $params;
    }

    /**
     * Populates the `dataType` properties in the parameter array if empty. Recurses through children when necessary.
     *
     * @param  array $array
     *
     * @return array
     */
    protected function generateHumanReadableTypes(array $array)
    {
        foreach ($array as $name => $info) {
            if (empty($info['dataType'])) {
                $array[$name]['dataType'] = $this->generateHumanReadableType($info['actualType'], $info['subType']);
            }
            if (isset($info['children'])) {
                $array[$name]['children'] = $this->generateHumanReadableTypes($info['children']);
            }
        }

        return $array;
    }

    /**
     * @param  string $actualType
     * @param  string $subType
     *
     * @return string
     */
    protected function generateHumanReadableType($actualType, $subType)
    {
        if ($actualType == DataTypes::MODEL) {
            if (class_exists($subType)) {
                $parts = explode('\\', $subType);

                return sprintf('object (%s)', end($parts));
            }

            return sprintf('object (%s)', $subType);
        }

        if ($actualType == DataTypes::COLLECTION) {
            if (DataTypes::isPrimitive($subType)) {
                return sprintf('array of %ss', $subType);
            }
            if (class_exists($subType)) {
                $parts = explode('\\', $subType);

                return sprintf('array of objects (%s)', end($parts));
            }

            return sprintf('array of objects (%s)', $subType);
        }

        return $actualType;
    }
}