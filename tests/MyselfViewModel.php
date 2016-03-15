<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class MyselfViewModel extends ViewModel
{
    public function getVariable($name, $default = null)
    {
        if ($name == 'name') {
            $myself = $this->fetchMyself();
            return $myself['name'];
        }

        return parent::getVariable($name, $default);
    }

    private function fetchMyself()
    {
        return [
            'id' => 'u3',
            'name' => 'Me',
        ];
    }
}
