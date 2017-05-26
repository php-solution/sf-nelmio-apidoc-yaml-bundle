<?php
namespace PhpSolution\NelmioApiDocYamlBundle\EventListener;

use PhpSolution\NelmioApiDocYamlBundle\Extractor\ApiDocExtractorInterface;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class RequestListener
 */
class RequestListener
{
    /**
     * @var \Nelmio\ApiDocBundle\Extractor\ApiDocExtractor
     */
    protected $extractor;
    /**
     * @var \Nelmio\ApiDocBundle\Formatter\FormatterInterface
     */
    protected $formatter;
    /**
     * @var string
     */
    protected $parameter;

    /**
     * @param ApiDocExtractorInterface $extractor
     * @param FormatterInterface       $formatter
     * @param string                   $parameter
     */
    public function __construct(ApiDocExtractorInterface $extractor, FormatterInterface $formatter, $parameter)
    {
        $this->extractor = $extractor;
        $this->formatter = $formatter;
        $this->parameter = $parameter;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->query->has($this->parameter)) {
            return;
        }

        $controller = $request->attributes->get('_controller');
        $route = $request->attributes->get('_route');
        if (null !== $annotation = $this->extractor->get($controller, $route)) {
            $result = $this->formatter->formatOne($annotation);
            $event->setResponse(
                new Response($result, 200, ['Content-Type' => 'text/html'])
            );
        }
    }
}