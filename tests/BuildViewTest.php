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
                    [
                        'capture' => 'comments-list',
                        'template' => 'comments-list',
                        'children' => [
                            [
                                'capture' => 'comment',
                                'viewModel' => \Sebaks\ViewTest\CommentViewModel::class,
                                'template' => 'comment',
                                'children' => [
                                    [
                                        'capture' => 'user',
                                        'viewModel' => \Sebaks\ViewTest\UserViewModel::class,
                                        'template' => 'user',
                                        'requireDataFromParent' => 'userId',
                                        'data' => [
                                            'fromParent' => 'userId', // will be set by calling getVariable('userId') from parent
                                            'static' => [ // will be set as variables
                                                'class' => 'user'
                                            ],
                                        ],
                                    ]
                                ],
                                'data' => [
                                    'fromParent' => 'comment', // will be set by calling getVariable('userId') from parent
                                ],
                            ],
                        ],
                        'dynamicLists' => [
                            'comment' => 'comments', // Builder will create 'comment' views for every entry in 'comments' array
                        ],
                        'requireData' => 'comments',
                    ],
                    [
                        'capture' => 'comment-create',
                        'template' => 'comment-create',
                        'children' => [
                            [
                                'capture' => 'myself-info',
                                'viewModel' => \Sebaks\ViewTest\MyselfViewModel::class,
                                'template' => 'user',
                            ],
                            [
                                'capture' => 'comment-create-form',
                                'template' => 'form',
                                'children' => [
                                    [
                                        'capture' => 'form-element',
                                        'template' => 'form-element-textarea',
                                    ],
                                    [
                                        'capture' => 'form-element',
                                        'template' => 'form-element-button',
                                    ],
                                ],
                            ],
                        ],
                    ],
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

        $expected = '<ul><li>text of c1<div class="user">John</div></li><li>text of c2<div class="user">Helen</div></li></ul>'
            .'<div class="">Me</div>'
            .'<form><textarea></textarea><button type="submit">Submit</button></form>';

        $this->assertEquals($expected, $result);
    }

    private function buildView($route, array $viewConfig, $data)
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate($viewConfig[$route]['template']);

        foreach ($viewConfig[$route]['children'] as $childOptions) {
            $childName = $childOptions['capture'];
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
            foreach ($options['children'] as $childOptions) {

                $childName = $childOptions['capture'];

                if (isset($options['dynamicLists'])) {
                    if (($options['dynamicLists'][$childName])) {
                        $listName = $options['dynamicLists'][$childName];

                        $list = $viewModel->getVariable($listName);
                        foreach ($list as $item) {
                            //$dataForChild = [$itemName => $item];

                            $dataForChild = [];
                            if (isset($childOptions['data']['fromParent'])) {
                                $paramName = $childOptions['data']['fromParent'];
                                $dataForChild = [$paramName => $item];

                                //$child->setVariable($paramName, $dataForChild);
                            }

                            $child = $this->buildChildView($childOptions, $dataForChild);

                            $viewModel->addChild($child, $childName);
                        }
                    }
                } else {
                    $child = $this->buildChildView($childOptions, $data);

                    if (isset($childOptions['data']['fromParent'])) {
                        $paramName = $childOptions['data']['fromParent'];

                        $dataForChild = $viewModel->getVariable($paramName);

                        $child->setVariable($paramName, $dataForChild);
                    }

                    if (isset($childOptions['data']['static'])) {
                        $staticData = $childOptions['data']['static'];
                        $child->setVariables($staticData);
                    }

                    $viewModel->addChild($child, $childName);
                }
            }
        }

        return $viewModel;
    }
}
