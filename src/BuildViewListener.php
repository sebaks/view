<?php

namespace Sebaks\View;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface as Events;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\View\Model\ViewModel as ZendViewModel;

class BuildViewListener extends AbstractListenerAggregate
{
    public function attach(Events $events)
    {
        $sharedEvents = $events->getSharedManager();
        $sharedEvents->attach('Zend\Stdlib\DispatchableInterface', MvcEvent::EVENT_DISPATCH, [$this, 'injectLayout'], -70);
    }

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
        $data = $result->getVariables();

        $viewComponent = $viewBuilder->buildView($viewConfig, [], $data);

        $response = $e->getResponse();
        if ($response->getStatusCode() != 200) {
            if ($result->getTemplate()) {
                $viewComponent->setTemplate($result->getTemplate());
            }
        }

        $e->setResult($viewComponent);

        if (isset($viewConfig['layout'])) {
            $viewComponentLayout = $viewConfig['layout'];
            if (!isset($options['layouts'][$viewComponentLayout])) {
                throw new \Exception("Layout '$viewComponentLayout' not found for view component '$matchedRouteName'");
            }

            $layout = $viewBuilder->buildView($options['layouts'][$viewComponentLayout]);

            $layout->addChild($viewComponent, 'content');
            $layout->setTerminal(true);
            $e->setViewModel($layout);
        } else {
            $viewComponent->setTerminal(true);
            $e->setViewModel($viewComponent);
        }
    }
}
