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
                        'template' => 'comments-list',
                        'children' => [
                            'comment' => [
                                'viewModel' => \Sebaks\ViewTest\CommentViewModel::class,
                                'template' => 'comment',
                                'children' => [
                                    'user' => [
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
                        'data' => [
                            'fromGlobal' => 'comments',
                        ],
                    ],
                    'comment-create' => [
                        'template' => 'comment-create',
                        'children' => [
                            'myself-info' => [
                                'viewModel' => \Sebaks\ViewTest\MyselfViewModel::class,
                                'template' => 'user',
                            ],
                            'comment-create-form' => [
                                'template' => 'form',
                                'children' => [
                                    'form-element-textarea' => [
                                        'capture' => 'form-element', // for render as group
                                        'template' => 'form-element-textarea',
                                    ],
                                    'form-element-button' => [
                                        'capture' => 'form-element', // for render as group
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

    private function buildView($route, array $viewConfig, $global = array())
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate($viewConfig[$route]['template']);

        foreach ($viewConfig[$route]['children'] as $childName => $childOptions) {
            $child = $this->buildChildView($childOptions, array(), $global);
            $viewModel->addChild($child, $childName);
        }

        return $viewModel;
    }

    private function buildChildView(array $options, $data, $global = array())
    {
        if (isset($options['viewModel'])) {
            $viewModel = new $options['viewModel']();
        } else {
            $viewModel = new ViewModel();
        }

        $viewModel->setTemplate($options['template']);
        $viewModel->setVariables($data);

        if (isset($options['data']['fromGlobal'])) {
            $dataFromGlobal = $global[$options['data']['fromGlobal']];
            $viewModel->setVariable($options['data']['fromGlobal'], $dataFromGlobal);
        }

        if (isset($options['dynamicLists'])) {
            foreach ($options['dynamicLists'] as $childName => $listName) {

                $list = $viewModel->getVariable($listName);

                $childOptions = $options['children'][$childName];

                foreach ($list as $entry) {

                    $dataFromParentName = $childOptions['data']['fromParent'];
                    $dataForChild = [$dataFromParentName => $entry];

                    $childView = $this->buildChildView($childOptions, $dataForChild);

                    $capture = $childName;
                    if (isset($childOptions['capture'])) {
                        $capture = $childOptions['capture'];
                    }
                    $viewModel->addChild($childView, $capture);
                }
            }
        }

        if (isset($options['children'])) {
            foreach ($options['children'] as $childName => $childOptions) {

                if (isset($options['dynamicLists'][$childName])) {
                    continue;
                }

                $dataForChild = [];

                if (isset($childOptions['data']['static'])) {
                    $dataForChild = array_merge($dataForChild, $childOptions['data']['static']);
                }

                if (isset($childOptions['data']['fromParent'])) {
                    $paramName = $childOptions['data']['fromParent'];
                    $fromParent = $viewModel->getVariable($paramName);

                    $dataForChild = array_merge($dataForChild, [$paramName => $fromParent]);
                }

                $child = $this->buildChildView($childOptions, $dataForChild);

                $capture = $childName;
                if (isset($childOptions['capture'])) {
                    $capture = $childOptions['capture'];
                }
                $viewModel->addChild($child, $capture);
            }
        }

        return $viewModel;
    }
}
