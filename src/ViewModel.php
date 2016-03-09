<?php

namespace Sebaks\View;

use Zend\View\Model\ViewModel as ZendViewModel;
use Zend\View\Model\ModelInterface;

class ViewModel extends ZendViewModel
{
    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param ModelInterface $child
     * @param $name
     */
    public function pushChild(ModelInterface $child, $name)
    {
        $this->children[$name] = $child;
    }

    /**
     * @param $name
     * @return ModelInterface
     */
    public function getChild($name)
    {
        if (!isset($this->children[$name])) {
            throw new \RuntimeException("View $this->name does not contain child $name");
        }

        return $this->children[$name];
    }
}
