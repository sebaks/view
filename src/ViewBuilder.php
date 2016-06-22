<?php

namespace Sebaks\View;

use Zend\View\Model\ViewModel as ZendViewModel;
use Zend\ServiceManager\ServiceManager;

class ViewBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ServiceManager
     */
    private $serviceLocator;

    /**
     * @param Config $config
     * @param ServiceManager $serviceLocator
     */
    public function __construct(Config $config, ServiceManager $serviceLocator)
    {
        $this->config = $config;
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @param array $options
     * @param array $data
     * @param array $globalData
     * @return ZendViewModel
     */
    public function buildView(array $options, array $data = array(), $globalData = array())
    {
        $allOptions = $this->config->getOptions();
        $options = $this->config->applyInheritance($options);

        if (isset($options['viewModel'])) {
            $this->serviceLocator->setShared($options['viewModel'], false);
            $viewModel = $this->serviceLocator->get($options['viewModel']);
        } else {
            $viewModel = new ZendViewModel();
        }

        if (isset($options['template'])) {
            $viewModel->setTemplate($options['template']);
        }

        if (isset($options['capture'])) {
            $viewModel->setCaptureTo($options['capture']);
        }

        $viewModel->setVariables($data);

        if (isset($options['data']['static'])) {
            $viewModel->setVariables($options['data']['static']);
        }

        if (isset($options['data']['fromGlobal'])) {
            $globalVar = $options['data']['fromGlobal'];

            if (is_array($globalVar)) {
                foreach ($globalVar as $globalVarName => $viewVarName) {
                    if (isset($globalData[$globalVarName])) {
                        $globalVarValue = $globalData[$globalVarName];
                        $viewModel->setVariable($viewVarName, $globalVarValue);
                    }
                }
            } else {
                if (isset($globalData[$globalVar])) {
                    $globalVarValue = $globalData[$globalVar];
                    $viewModel->setVariable($globalVar, $globalVarValue);
                }
            }
        }

        if (isset($options['childrenDynamicLists'])) {
            foreach ($options['childrenDynamicLists'] as $childName => $listName) {

                $list = $viewModel->getVariable($listName);

                if ($list === null) {
                    throw new \UnexpectedValueException("Cannot build children list of '$childName' by '$listName' list . View does not contain variable '$listName'.");
                }
                if (!is_array($list) && !($list instanceof \Traversable)) {
                    throw new \UnexpectedValueException("Cannot build children list of '$childName' by '$listName' list . List '$listName' must be array " . gettype($list) . " given.");
                }

                if (array_key_exists($childName, $options['children'])) {
                    $childOptions = $options['children'][$childName];
                } else {
                    if (in_array($childName, $options['children'])) {
                        $childOptions = $allOptions[$childName];
                    } else {
                        throw new \UnexpectedValueException("Cannot build children list of '$childName' by '$listName' list . Child '$childName' not found");
                    }
                }

                foreach ($list as $entry) {
                    $varFromParent = $childOptions['data']['fromParent'];

                    if (is_array($varFromParent)) {
                        foreach ($varFromParent as $varFromParentName => $viewVarName) {
                            $dataForChild = [$viewVarName => $entry];
                        }
                    } else {
                        $dataForChild = [$varFromParent => $entry];
                    }

                    $childView = $this->buildView($childOptions, $dataForChild, $globalData);

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

                if (is_string($childOptions)) {
                    $childName = $childOptions;
                    $childOptions = $allOptions[$childName];
                }

                if (isset($options['childrenDynamicLists'][$childName])) {
                    continue;
                }

                $dataForChild = [];

                if (isset($childOptions['data']['static'])) {
                    $dataForChild = array_merge($dataForChild, $childOptions['data']['static']);
                }

                if (isset($childOptions['data']['fromParent'])) {
                    $varFromParent = $childOptions['data']['fromParent'];

                    if (is_array($varFromParent)) {
                        foreach ($varFromParent as $varFromParentName => $viewVarName) {
                            $fromParentVal = $viewModel->getVariable($varFromParentName);

                            $dataForChild = array_merge($dataForChild, [$viewVarName => $fromParentVal]);
                        }
                    } else {
                        $viewVarName = $childOptions['data']['fromParent'];
                        $fromParentVal = $viewModel->getVariable($viewVarName);

                        $dataForChild = array_merge($dataForChild, [$viewVarName => $fromParentVal]);
                    }
                }

                $child = $this->buildView($childOptions, $dataForChild, $globalData);

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
