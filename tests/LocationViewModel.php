<?php

namespace Sebaks\ViewTest;

use Zend\View\Model\ViewModel;

class LocationViewModel extends ViewModel
{
    private static $countryIds = [];
    private static $countries;

    public function setVariable($name, $value)
    {
        if ($name == 'countryId') {
            self::$countryIds[] = $value;
        }

        return parent::setVariable($name, $value);
    }

    public function getVariable($name, $default = null)
    {
        if ($name == 'country') {
            if (self::$countries === null) {
                self::$countries = $this->fetchCountries(self::$countryIds);
            }
            $countryId = $this->getVariable('countryId');
            if (isset(self::$countries[$countryId]['name'])) {
                return self::$countries[$countryId]['name'];
            }
        }

        return parent::getVariable($name, $default);
    }

    private function fetchCountries($countryIds)
    {
        $countries = [
            '1' => [
                'id' => '1',
                'name' => 'Ukraine',
            ],
            '2' => [
                'id' => '2',
                'name' => 'United States',
            ],
        ];

        $result = [];
        foreach ($countryIds as $countryId) {
            if (isset($countries[$countryId])) {
                $result[$countryId] = $countries[$countryId];
            }
        }
        return $result;
    }
}
