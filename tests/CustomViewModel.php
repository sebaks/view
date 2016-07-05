<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class CustomViewModel extends ViewModel
{
    public function getVariable($name, $default = null)
    {
        if ($name == 'parentCallVar') {
            return 'parentCallVarValue';
        }

        return parent::getVariable($name, $default);
    }
}
