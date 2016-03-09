<?php

namespace Sebaks\View;

use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\EventManager\EventInterface;

class Module implements BootstrapListenerInterface
{
    public function onBootstrap(EventInterface $e)
    {
        /** @var \Zend\EventManager\EventManager $eventManager */
        $eventManager = $e->getApplication()->getEventManager();

        $injectLayoutListener = new BuildViewListener();
        $eventManager->attach($injectLayoutListener);
    }
}