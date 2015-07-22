<?php
namespace Shop\Structure\Service\Load1c_v2\Offer;

use Shop\Structure\Service\Load1c_v2\AbstractDb;
use Ideal\Field\Url;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1c_v2\Category;
use Shop\Structure\Service\Load1c_v2\Good\DbGood;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbOffer extends AbstractDb
{
    /**
     *  Установка полей класса - полного имени таблиц с префикс
     */
    public function __construct()
    {
        parent::__construct();
        $this->structureGood = $this->prefix . $this->structureGood;
        $this->table = $this->prefix . 'offers_good';
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        $db = Db::getInstance();

        $sql = "SELECT * FROM `{$this->table}`";
        $result = $db->select($sql);
        $data = array();

        foreach ($result as $item) {
            if ($item['offer_id'] == $item['good_id']) {
                $data[$item['good_id']] = $item;
            } else {
                $data[$item['good_id'] . '#' . $item['offer_id']] = $item;
            }
        }

        return $data;
    }

    // квадрат чилса используя 2 переменных и только + и -
}
