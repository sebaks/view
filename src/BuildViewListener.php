<?php

namespace Sebaks\View;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\View\Model\ViewModel as ZendViewModel;

class BuildViewListener
{
    public function injectLayout(MvcEvent $e)
    {
        $request = $e->getRequest();
        if (! $request instanceof HttpRequest) {
            return;
        }

        $result = $e->getResult();
        if (! $result instanceof ZendViewModel) {
            return;
        }

        $response = $e->getResponse();
        if ($response->getStatusCode() != 200) {
            return;
        }

        $matchedRoute = $e->getRouteMatch();
        if (!$matchedRoute) {
            return;
        }
        $matchedRouteName = $matchedRoute->getMatchedRouteName();

        $serviceLocator = $e->getApplication()->getServiceManager();
        $config = $serviceLocator->get('config');
        $options = $config['sebaks-view'];

        if (!isset($options['contents'][$matchedRouteName])) {
            return;
        }
        $viewConfig = $options['contents'][$matchedRouteName];

        $config = new Config(array_merge($options['layouts'], $options['contents'], $options['blocks']));
        $viewConfig = $config->applyInheritance($viewConfig);
        $viewBuilder = new ViewBuilder($config, $serviceLocator);
        $data = $result->getVariables()->getArrayCopy();

        if (isset($viewConfig['layout'])) {
            $viewComponentLayout = $viewConfig['layout'];
            if (!isset($options['layouts'][$viewComponentLayout])) {
                throw new \Exception("Layout '$viewComponentLayout' not found for view component '$matchedRouteName'");
            }

            $options['layouts'][$viewComponentLayout]['children']['content'] = $viewConfig;

            $viewComponent = $viewBuilder->build($options['layouts'][$viewComponentLayout], $data);
        } else {
            $viewComponent = $viewBuilder->build($viewConfig, $data);
        }

        $viewComponent->setTerminal(true);
        $e->setViewModel($viewComponent);
        $e->setResult($viewComponent);
    }
}
