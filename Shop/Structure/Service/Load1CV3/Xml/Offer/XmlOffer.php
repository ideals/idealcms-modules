<?php
namespace Shop\Structure\Service\Load1CV3\Xml\Offer;

use Shop\Structure\Service\Load1CV3\Xml\AbstractXml;

class XmlOffer extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'ПакетПредложений/Предложения';

    public function parse()
    {
        parent::parse();

        foreach ($this->data as $key => $value) {
            // todo доделать, когда будет нужно, обработку реквизитов предложений
            if (isset($this->data[$key]['dir_params'])) {
                unset($this->data[$key]['dir_params']);
            }

            $ids = explode('#', $key);
            $this->data[$key]['good_id'] = $ids[0];
            $this->data[$key]['offer_id'] = $ids[1] ?? $ids[0];

            // Если в конфиге определено поле is_active, то получаем данные по нему из выгрузки
            if (isset($value['is_active'])) {
                $this->data[$key]['is_active'] = $value['is_active'] === 'false' ? '1' : '0';
            } else { // Иначе считаем что предложение активно, так как оно есть в выгрузке
                $this->data[$key]['is_active'] = 1;
            }
        }

        return $this->data;
    }
}
