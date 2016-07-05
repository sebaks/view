<?php

namespace Sebaks\View;

use Zend\View\Model\ViewModel as ZendViewModel;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ViewModel;

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
     * @param $config
     * @param array $data
     * @return array|object|ViewModel
     */
    public function build($config, array $data = array())
    {
        $allOptions = $this->config->getOptions();

        $i = 0;
        $queue = new \SplQueue();
        $queue->enqueue($config);

        while ($queue->count() > 0) {
            $options = $queue->dequeue();
            $options = $this->config->applyInheritance($options);

            if (isset($options['viewModel'])) {
                $this->serviceLocator->setShared($options['viewModel'], false);
                $viewModel = $this->serviceLocator->get($options['viewModel']);
            } else {
                $viewModel = new ZendViewModel();
            }
            if ($i == 0) {
                $rootViewModel = $viewModel;
            }

            if (isset($options['template'])) {
                $viewModel->setTemplate($options['template']);
            }

            if (isset($options['capture'])) {
                $viewModel->setCaptureTo($options['capture']);
            } elseif (isset($options['id'])) {
                $viewModel->setCaptureTo($options['id']);
            }

            if (isset($options['data']['static'])) {
                $viewModel->setVariables($options['data']['static']);
            }

            if (isset($options['data']['fromGlobal'])) {
                $globalVar = $options['data']['fromGlobal'];

                if (is_array($globalVar)) {
                    foreach ($globalVar as $globalVarName => $viewVarName) {
                        $globalVarValue = $this->getVarValue($globalVarName, $data);
                        $viewModel->setVariable($viewVarName, $globalVarValue);
                    }
                } else {
                    $globalVarValue = $this->getVarValue($globalVar, $data);
                    $viewModel->setVariable($globalVar, $globalVarValue);
                }
            }

            if (isset($options['parent'])) {
                /** @var ViewModel $parent */
                $parent = $options['parent'];
                $parent->addChild($viewModel, $viewModel->captureTo(), true);

                if (isset($options['data']['fromParent'])) {
                    $varFromParent = $options['data']['fromParent'];
                    $parentVars = $parent->getVariables();

                    if (is_array($varFromParent)) {

                        foreach ($varFromParent as $varFromParentName => $viewVarName) {

                            $fromParentVal = $this->getVarValue($varFromParentName, $parentVars);

                            if ($fromParentVal === null) {
                                $fromParentVal = $parent->getVariable($varFromParentName);
                            }

                            if (is_array($viewVarName)) {
                                $dataFromParent = [];
                                foreach ($viewVarName as $varName) {
                                    $dataFromParent[$varName] = $fromParentVal;
                                }
                            } else {
                                $dataFromParent = [$viewVarName => $fromParentVal];
                            }

                            $viewModel->setVariables($dataFromParent);
                        }
                    } else {
                        $viewVarName = $options['data']['fromParent'];
                        $fromParentVal = $this->getVarValue($viewVarName, $parentVars);

                        if ($fromParentVal === null) {
                            $fromParentVal = $parent->getVariable($viewVarName);
                        }

                        $viewModel->setVariables([$viewVarName => $fromParentVal]);
                    }
                }
            }

            if (!empty($options['children'] )) {
                foreach ($options['children'] as $childId => $child) {

                    if (is_string($child)) {
                        $childId = $child;
                        $child = $allOptions[$child];
                    }

                    if (isset($options['childrenDynamicLists'][$childId])) {
                        continue;
                    }

                    $child['id'] = $childId;
                    $child['parent'] = $viewModel;

                    $queue->enqueue($child);
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
                        $child = $options['children'][$childName];
                    } else {
                        if (in_array($childName, $options['children'])) {
                            $child = $allOptions[$childName];
                        } else {
                            throw new \UnexpectedValueException("Cannot build children list of '$childName' by '$listName' list . Child '$childName' not found");
                        }
                    }

                    $child['id'] = $childName;
                    $child['parent'] = $viewModel;
                    if (isset($child['data']['fromParent'])) {
                        $varFromParent = $child['data']['fromParent'];
                    }

                    foreach ($list as $entry) {

                        if (isset($varFromParent)) {
                            if (is_array($varFromParent)) {
                                foreach ($varFromParent as $varFromParentName => $viewVarName) {
                                    $dataForChild = [$viewVarName => $entry];
                                }
                            } else {
                                $dataForChild = [$varFromParent => $entry];
                            }

                            if (!isset($child['data']['static'])) {
                                $child['data']['static'] = [];
                            }

                            $child['data']['static'] = array_merge($child['data']['static'], $dataForChild);
                            unset($child['data']['fromParent']);
                        }

                        $queue->enqueue($child);
                    }
                }
            }

            $i++;
        }

        return $rootViewModel;
    }

    /**
     * @param string $varName
     * @param array $data
     * @return mixed
     */
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
}
