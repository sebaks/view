<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class UserViewModel extends ViewModel
{
    private static $userIds;
    private static $users;

    private $userId;

    public function handleRequireDataFromParent($dataForChild)
    {
        self::$userIds[] = $dataForChild;
        $this->userId = $dataForChild;
    }

    public function getName()
    {
        if (self::$users === null) {
            self::$users = $this->fetchUsers(self::$userIds);
        }

        if (isset(self::$users[$this->userId]['name'])) {
            return self::$users[$this->userId]['name'];
        }
    }

    private function fetchUsers()
    {
        return [
            'u1' => [
                'id' => 'u1',
                'name' => 'John',
            ],
            'u2' => [
                'id' => 'u2',
                'name' => 'Helen',
            ],
        ];
    }
}
