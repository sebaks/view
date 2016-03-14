<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class CommentsViewModel extends ViewModel
{
    private $data;

    public function setRequireData($data)
    {

        $this->data = $data;
    }
}
