<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class CommentsViewModel extends ViewModel
{
    public function handleRequireData($data, $commentConfig, $parentViewModel)
    {

        die(var_dump($data));
    }
}
