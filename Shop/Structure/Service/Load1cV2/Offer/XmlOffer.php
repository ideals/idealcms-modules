<?php
namespace Shop\Structure\Service\Load1cV2\Offer;

use Shop\Structure\Service\Load1cV2\AbstractXml;
use Ideal\Field\Url;
use Shop\Structure\Service\Load1cV2\Directory\DbDirectory;

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
        $directoryModel = new DbDirectory();
        parent::parse();

        foreach ($this->data as $key => $value) {
            $ids = explode('#', $key);
            $this->data[$key]['good_id'] = $ids[0];
            $this->data[$key]['offer_id'] = isset($ids[1]) ? $ids[1] : $ids[0];
            if (isset($value['dir_params'])) {
                $this->data[$key]['dir_ids'] = $directoryModel->getDirectory($value['dir_params']);
                unset($this->data[$key]['dir_params']);
            }
        }

        return $this->data;
    }

    public function parsePrice()
    {
        $this->configs['fields'] = $this->configs['priceFields'];

        parent::parse();

        return $this->data;
    }

    public function parseRests()
    {
        $this->configs['fields'] = $this->configs['priceRests'];

        parent::parse();

        return $this->data;
    }
}
