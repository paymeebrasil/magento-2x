<?php
namespace Paymee\Core\Model\Config\Source;

class Environment
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [
            ["value" => "sandbox", 'label' => __("Sandbox")],
            ["value" => "live", 'label' => __("Production")]
        ];

        return $arr;
    }
}
