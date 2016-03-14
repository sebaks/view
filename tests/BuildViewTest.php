<?php

namespace Sebaks\ViewTest;

use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use Zend\View\Model\ViewModel;
use Zend\View\View;
use Zend\View\Strategy\PhpRendererStrategy;
use Zend\Http\Response;

class BuildViewTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $renderer = new PhpRenderer();

        $resolver = new Resolver\AggregateResolver();

        $map = new Resolver\TemplateMapResolver(array(
            'page'      => __DIR__ . '/view/page.phtml',
            'comments-list' => __DIR__ . '/view/comments-list.phtml',
            'comment' => __DIR__ . '/view/comment.phtml',
            'user' => __DIR__ . '/view/user.phtml',
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
        $response = new Response();
        $view->setResponse($response);
        $strategy = new PhpRendererStrategy($renderer);
        $strategy->attach($view->getEventManager());

        /////////////////////////////////////////////////


        $viewConfig = [
            'page' => [
                'template' => 'page',
                'children' => [
                    'comments-list' => [
                        'viewModel' => 'Sebaks\ViewTest\CommentsViewModel',
                        'template' => 'comments-list',
                        'children' => [
                            'comment' => [
                                'viewModel' => 'Sebaks\ViewTest\CommentViewModel',
                                'template' => 'comment',
                                'children' => [
                                    'user' => [
                                        'viewModel' => 'Sebaks\ViewTest\UserViewModel',
                                        'template' => 'user',
                                        'requireData' => 'userId',
                                    ]
                                ],
                                'requireData' => 'comment',
                            ],
                        ],
                        'dynamicLists' => [
                            'comment' => [
                                'list' => 'comments',
                                'item' => 'comment',
                            ],
                        ],
                        'requireData' => 'comments',
                    ]
                ],
            ],
        ];

        $data = [
            'comments' => [
                [
                    'id' => 'c1',
                    'userId' => 'u1',
                    'text' => 'text of c1',
                ],
                [
                    'id' => 'c2',
                    'userId' => 'u2',
                    'text' => 'text of c2',
                ],
            ],
        ];

        /////////////////////

        $pageViewModel = $this->buildView('page', $viewConfig, $data);

        /////////////////////


        $view->render($pageViewModel);
        $result = $response->getBody();


        $this->assertEquals('<div><ul><li>text of c1<span>John</span></li><li>text of c2<span>Helen</span></li></ul></div>', $result);
    }

    private function buildView($route, array $viewConfig, $data)
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate($viewConfig[$route]['template']);

        foreach ($viewConfig[$route]['children'] as $childName => $childOptions) {
            $child = $this->buildChildView($childOptions, $data);
            $viewModel->addChild($child, $childName);
        }

        return $viewModel;
    }

    private function buildChildView(array $options, $data)
    {
        if (isset($options['viewModel'])) {
            $viewModel = new $options['viewModel']();
        } else {
            $viewModel = new ViewModel();
        }

        $viewModel->setTemplate($options['template']);

        if (isset($options['requireData'])) {
            $requireData = $data[$options['requireData']];
            $viewModel->setVariable($options['requireData'], $requireData);
        }

        if (isset($options['children'])) {
            foreach ($options['children'] as $childName => $childOptions) {

                if (isset($options['dynamicLists'])) {
                    if (($options['dynamicLists'][$childName])) {
                        $listName = $options['dynamicLists'][$childName]['list'];
                        $itemName = $options['dynamicLists'][$childName]['item'];

                        $list = $viewModel->getVariable($listName);
                        foreach ($list as $item) {
                            $dataForChild = [$itemName => $item];
                            $child = $this->buildChildView($childOptions, $dataForChild);

                            $viewModel->addChild($child, $childName);
                        }
                    }
                } else {
                    $child = $this->buildChildView($childOptions, $data);
                    $viewModel->addChild($child, $childName);
                }
            }
        }

        return $viewModel;
    }
}
