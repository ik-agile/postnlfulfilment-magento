<?php
namespace Postnl\Ecs\Model\System\Config\Source;


class Address {
    
    const FIELD_STREET = 'Street';
    const FIELD_HOUSE = 'HouseNo';
    const FIELD_ANNEX = 'Annex';
    
    public function toOptionArray($isActiveOnlyFlag=false)
    {
        return array(
            array(
                'label' => __('Street'),
                'value' => self::FIELD_STREET,
            ),
            array(
                'label' => __('House No'),
                'value' => self::FIELD_HOUSE,
            ),
            array(
                'label' => __('Annex'),
                'value' => self::FIELD_ANNEX,
            ),
        );
    }
    
}
