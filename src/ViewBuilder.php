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

    private function getVarValue($varName, $data)
    {
        if (strpos($varName, ':') !== false) {
            list($varArrayName, $varNameInArray) = explode(':', $varName);

            if (isset($data[$varArrayName][$varNameInArray])) {
                return $data[$varArrayName][$varNameInArray];
            }
        }

        if (isset($data[$varName])) {
            return $data[$varName];
        }
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

        $viewModel->setTemplate($options['template']);
        $viewModel->setVariables($data);

        if (isset($options['data']['static'])) {
            $viewModel->setVariables($options['data']['static']);
        }

        if (isset($options['data']['fromGlobal'])) {
            $globalVar = $options['data']['fromGlobal'];

            if (is_array($globalVar)) {
                foreach ($globalVar as $globalVarName => $viewVarName) {
                    $globalVarValue = $this->getVarValue($globalVarName, $globalData);
                    $viewModel->setVariable($viewVarName, $globalVarValue);
                }
            } else {
                $globalVarValue = $this->getVarValue($globalVar, $globalData);
                $viewModel->setVariable($globalVar, $globalVarValue);
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
                    $parentVars = $viewModel->getVariables();

                    if (is_array($varFromParent)) {

                        foreach ($varFromParent as $varFromParentName => $viewVarName) {

                            $fromParentVal = $this->getVarValue($varFromParentName, $parentVars);

                            if ($fromParentVal === null) {
                                $fromParentVal = $viewModel->getVariable($varFromParentName);
                            }

                            if (is_array($viewVarName)) {
                                $dataFromParent = [];
                                foreach ($viewVarName as $varName) {
                                    $dataFromParent[$varName] = $fromParentVal;
                                }
                            } else {
                                $dataFromParent = [$viewVarName => $fromParentVal];
                            }

                            $dataForChild = array_merge($dataForChild, $dataFromParent);
                        }
                    } else {
                        $viewVarName = $childOptions['data']['fromParent'];
                        $fromParentVal = $this->getVarValue($viewVarName, $parentVars);

                        if ($fromParentVal === null) {
                            $fromParentVal = $viewModel->getVariable($viewVarName);
                        }

                        $dataForChild = array_merge($dataForChild, [$viewVarName => $fromParentVal]);
                    }
                }

                $child = $this->buildView($childOptions, $dataForChild, $globalData);

                if ('content' === $child->captureTo()) {
                    $capture = $childName;
                    if (isset($childOptions['capture'])) {
                        $capture = $childOptions['capture'];
                    }
                } else {
                    $capture = $child->captureTo();
                }
                $viewModel->addChild($child, $capture);
            }
        }

        if (method_exists($viewModel, 'initialize')) {
            $viewModel->initialize();
        }

        return $viewModel;
    }
}
