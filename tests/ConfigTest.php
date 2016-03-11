<?php

namespace Sebaks\ViewTest;

use Sebaks\View\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $options;

    public function setUp()
    {
        $this->options = [
            'block1' => [
                'extend' => 'block2',
            ],
            'block2' => [
                'extend' => 'block3',
                'children' => [
                    'block4/template'
                ],
                'variables' => [
                    'block2/variable' => 'b2v',
                    'block3/variable' => 'b2v'
                ],
            ],
            'block3' => [
                'template' => 'block3/template',
                'variables' => [
                    'block3/variable' => 'b3v',
                    'block3/variable2' => 'b3v2'
                ],
            ],
            'block4' => [
                'template' => 'block4/template',
                'variables' => [
                    'block4/variable' => 'b4v'
                ],
            ],
            'block5' => [
                'extend' => 'block6',
            ],
            'block6' => [
                'extend' => 'block5',
            ],
            'block7' => [
                'extend' => 'fake',
            ]
        ];
        $this->config = new Config($this->options);
    }

    public function testApplyInheritance()
    {
        $result = $this->config->applyInheritance($this->options['block1']);

        $expect = [
            'extend' => 'block2',
            'template' => 'block3/template',
            'children' => [
                'block4/template'
            ],
            'variables' => [
                'block2/variable' => 'b2v',
                'block3/variable' => 'b2v',
                'block3/variable2' => 'b3v2',
            ],
        ];

        $this->assertEquals($expect, $result);
    }

    public function testGetInheritanceChain()
    {
        $result = $this->config->getInheritanceChain($this->options['block1']);

        $expect = [
            $this->options['block2'],
            $this->options['block3'],
        ];

        $this->assertEquals($expect, $result);
    }

    public function testGetInheritanceChainThrowExceptionIfOneParentAlreadyInChain()
    {
        $this->setExpectedException('UnexpectedValueException', "Parent view 'block6' already exists in inheritance chain");

        $this->config->getInheritanceChain($this->options['block5']);
    }

    public function testGetInheritanceChainThrowExceptionIfOneParentIsUndefined()
    {
        $this->setExpectedException('UnexpectedValueException', "Parent view 'fake' not found");

        $this->config->getInheritanceChain($this->options['block7']);
    }
}
