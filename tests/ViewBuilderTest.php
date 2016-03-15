<?php

namespace Sebaks\ViewTest;

use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use Zend\View\Model\ViewModel;
use Zend\View\View;
use Zend\View\Strategy\PhpRendererStrategy;
use Zend\Http\Response;
use Sebaks\View\ViewBuilder;

class ViewBuilderTest extends \PHPUnit_Framework_TestCase
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
                                    'fromParent' => 'comment', // will be set by calling getVariable('comment') from parent
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

        $viewBuilder = new ViewBuilder();
        $pageViewModel = $viewBuilder->buildView($viewConfig['page'], array(), $data);

        /////////////////////

        $view->render($pageViewModel);
        $result = $response->getBody();

        $expected = '<ul><li>text of c1<div class="user">John</div></li><li>text of c2<div class="user">Helen</div></li></ul>'
            .'<div class="">Me</div>'
            .'<form><textarea></textarea><button type="submit">Submit</button></form>';

        $this->assertEquals($expected, $result);
    }
}