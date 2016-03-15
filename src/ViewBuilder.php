<?php

namespace Sebaks\View;

use Zend\View\Model\ViewModel as ZendViewModel;

class ViewBuilder
{
    public function buildView(array $options, array $data = array(), $global = array())
    {
        if (isset($options['viewModel'])) {
            $viewModel = new $options['viewModel']();
        } else {
            $viewModel = new ZendViewModel();
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

                    $childView = $this->buildView($childOptions, $dataForChild, $global);

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

                $child = $this->buildView($childOptions, $dataForChild, $global);

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
