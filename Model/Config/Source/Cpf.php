<?php
namespace Paymee\Core\Model\Config\Source;

class Cpf
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [
            ["value" => "billing", 'label' => __("Billing vat_id")],
            ["value" => "shipping", 'label' => __("Shipping vat_id")],
            ["value" => "customer", 'label' => __("Customer taxvat")],
            ["value" => "paymee", 'label' => __("Paymee Cpf")],
        ];

        return $arr;
    }
}
