<?php
namespace Shop\Structure\Service\Load1c_v2\Offer;

use Shop\Structure\Service\Load1c_v2\AbstractXml;
use Ideal\Field\Url;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:32
 */

class XmlOffer extends AbstractXml
{
    /** @var string путь к категориям в XML */
    public $part = 'ПакетПредложений/Предложения';

    public function parse()
    {
        parent::parse();

        foreach ($this->data as $key => $value) {
            $ids = explode('#', $key);
            $this->data[$key]['good_id'] = $ids[0];
            $this->data[$key]['offer_id'] = isset($ids[1]) ? $ids[1] : $ids[0];
        }

        return $this->data;
    }
}
