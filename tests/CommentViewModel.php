<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class CommentViewModel extends ViewModel
{
    public function handleRequireData($data, $commentConfig, $parentViewModel)
    {
        foreach ($data as $commentData) {

            $commentViewModel = new ViewModel();
            $commentViewModel->setTemplate($commentConfig['template']);

            $commentViewModel->setVariable('text', $commentData['text']);


//            foreach ($commentConfig['children'] as $children => $childrenConfig) {
//
//                $userViewModel = new UserViewModel();
//                $userViewModel->setTemplate($childrenConfig['template']);
//                $commentViewModel->addChild($userViewModel, $children);
//
//                if (isset($childrenConfig['requireDataFromParent'])) {
//                    $dataForChild = $commentData[$childrenConfig['requireDataFromParent']];
//                    $userViewModel->handleRequireDataFromParent($dataForChild);
//                }
//            }
//
            $parentViewModel->addChild($commentViewModel, 'comment');
        }
    }
}
