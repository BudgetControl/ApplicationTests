<?php

namespace Budgetcontrol\ApplicationTests\Support;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    /**
     * Removes the specified properties from the given data array.
     *
     * @param array $data The data array from which to remove the properties.
     * @param array $properties The properties to be removed from the data array.
     * @return array
     */
    public function removeProperty(array &$data, $properties) {
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_array($value)) {
                    $this->removeProperty($value, $properties);
                }
            }
            foreach ($properties as $property) {
                unset($data[$property]);
            }
        }
    }
}
