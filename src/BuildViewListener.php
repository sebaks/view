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

        $viewComponent = $this->createViewModel($options['blocks'], $options['contents'][$matchedRouteName], $serviceLocator, $matchedRouteName);
        $viewComponent->setVariables($result->getVariables());

        $response = $e->getResponse();
        if ($response->getStatusCode() != 200) {
            if ($result->getTemplate()) {
                $viewComponent->setTemplate($result->getTemplate());
            }
        }

        $e->setResult($viewComponent);

        if (!isset($options['contents'][$matchedRouteName]['layout'])) {
            throw new \Exception("Missing required parameter 'layout' for view component '$matchedRouteName''");
        }

        $viewComponentLayout = $options['contents'][$matchedRouteName]['layout'];
        if (!isset($options['layouts'][$viewComponentLayout])) {
            throw new \Exception("Layout '$viewComponentLayout' not found for view component '$matchedRouteName'");
        }

        $layout = $this->createViewModel($options['blocks'], $options['layouts'][$viewComponentLayout], $serviceLocator, $viewComponentLayout);
        $layout->pushChild($viewComponent, 'content');
        $layout->setTerminal(true);

        $e->setViewModel($layout);

        /** @var \Zend\View\Renderer\PhpRenderer $renderer */
        $renderer = $e->getApplication()->getServiceManager()->get('viewrenderer');
        $renderer->setCanRenderTrees(true);
    }

    private function createViewModel(array $options, array $viewConfig, $serviceLocator, $requestedName)
    {
        $config = new Config($options);
        $viewConfig = $config->applyInheritance($viewConfig);

        if (empty($viewConfig['template'])) {
            throw new \Exception("Empty template for $requestedName");
        }

        $template = $viewConfig['template'];

        if (!empty($viewConfig['viewModel'])) {
            $viewModelClass = $viewConfig['viewModel'];
            $viewModel = $serviceLocator->get($viewModelClass);
        } else {
            $viewModel = new ViewModel();
        }

        $variables = [];
        if (!empty($viewConfig['variables'])) {
            $variables = $viewConfig['variables'];
        }

        $children = [];
        if (!empty($viewConfig['children'])) {
            $children = $viewConfig['children'];
        }

        $viewModel->setName($requestedName);
        $viewModel->setTemplate($template);
        $viewModel->setVariables($variables);
        $viewModel->prepare();

        foreach ($children as $childAlias => $child) {

            if (is_array($child)) {

                $childViewConfig = $child;

                $childViewModel = $this->createViewModel($options, $childViewConfig, $serviceLocator, $childAlias);

                if (!$childViewModel) {
                    throw new \Exception("Cannot create child view '$child' for '$requestedName' view");
                }

                $viewModel->pushChild($childViewModel, $childAlias);

            } elseif (is_string($child)) {

                if (is_int($childAlias)) {
                    $childAlias = $child;
                }

                if (!isset($options[$child])) {
                    throw new \Exception("Cannot create child view '$child' for '$requestedName' view");
                }
                $childViewModel = $this->createViewModel($options, $options[$child], $serviceLocator, $childAlias);

                $viewModel->pushChild($childViewModel, $childAlias);

            } else {
                throw new \Exception("Wrong child configuration for view $requestedName. It must be string or array.");
            }
        }

        return $viewModel;
    }
}
