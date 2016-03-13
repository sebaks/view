<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class UserViewModel extends ViewModel
{
    public function handleRequireDataFromParent($dataForChild)
    {
        $__users = [
            'u1' => [
                'id' => 'u1',
                'name' => 'John',
            ],
            'u2' => [
                'id' => 'u2',
                'name' => 'Helen',
            ],
        ];

        $userData = $__users[$dataForChild];
        $this->setVariable('name', $userData['name']);
    }
}
