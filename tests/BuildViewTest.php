<?php

namespace Sebaks\ViewTest;

use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use Zend\View\Model\ViewModel;
use Zend\View\View;

class BuildViewTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $renderer = new PhpRenderer();

        $resolver = new Resolver\AggregateResolver();

        $map = new Resolver\TemplateMapResolver(array(
            'layout'      => __DIR__ . '/view/layout.phtml',
            'index/index' => __DIR__ . '/view/index/index.phtml',
        ));
        $stack = new Resolver\TemplatePathStack(array(
            'script_paths' => array(
                __DIR__ . '/view',
            )
        ));

        $resolver->attach($map)
            ->attach($stack)
            ->attach(new Resolver\RelativeFallbackResolver($map))
            ->attach(new Resolver\RelativeFallbackResolver($stack));

        $renderer->setResolver($resolver);

        $view = new View();

        $layoutViewModel = new ViewModel();
        $layoutViewModel->setTemplate('layout');

        $viewModel = new ViewModel();
        $viewModel->setTemplate('index/index');

        $layoutViewModel->addChild($viewModel, 'content');

        $result = $renderer->render($layoutViewModel);

        $this->assertEquals('<div></div>', $result);
    }
}
