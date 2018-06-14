<?php

namespace Sebaks\View;

use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;

class Module implements BootstrapListenerInterface
{
    public function onBootstrap(EventInterface $e)
    {
        /** @var \Zend\EventManager\EventManager $eventManager */
        $eventManager = $e->getApplication()->getEventManager();

        $injectLayoutListener = new BuildViewListener();

        $sharedEvents = $eventManager->getSharedManager();
        $sharedEvents->attach(
            'Zend\Stdlib\DispatchableInterface',
            MvcEvent::EVENT_DISPATCH,
            [$injectLayoutListener, 'injectLayout'],
            -70
        );
    }
}