<?php
namespace Shop\Structure\Service\Load1CV208\Xml\Offer;

use Shop\Structure\Service\Load1CV208\Xml\AbstractXml;

class XmlOffer extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'ПакетПредложений/Предложения';

    public function parse()
    {
        parent::parse();

        if (!empty($this->data)) {
            foreach ($this->data as $key => $value) {
                $ids = explode('#', $key);
                $this->data[$key]['good_id'] = $ids[0];
                $this->data[$key]['offer_id'] = isset($ids[1]) ? $ids[1] : $ids[0];

                // Если в конфиге определено поле is_active, то получаем данные по нему из выгрузки
                if (isset($value['is_active'])) {
                    $this->data[$key]['is_active'] = $value['is_active'] == 'false' ? '1' : '0';
                } else { // Иначе считаем что предложение активно, так как оно есть в выгрузке
                    $this->data[$key]['is_active'] = 1;
                }
            }
        }
        return $this->data;
    }
}
