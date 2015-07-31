<?php
namespace Shop\Structure\Service\Load1cV2\Offer;

use Shop\Structure\Service\Load1cV2\AbstractDb;
use Ideal\Field\Url;
use Ideal\Core\Db;
use Shop\Structure\Service\Load1cV2\Category;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:31
 */

class DbOffer extends AbstractDb
{
    protected $parse = array();
    /**
     *  Установка полей класса - полного имени таблиц с префикс
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->prefix . 'offers_good';
    }

    /**
     * Парсинг товаров из БД
     *
     * @return array ключ - id_1c, значение - все необходимые поля (в SQL)
     */
    public function parse()
    {
        if (count($this->parse) != 0) {
            return $this->parse;
        }

        $db = Db::getInstance();

        $sql = "SELECT * FROM `{$this->table}`";
        $result = $db->select($sql);

        foreach ($result as $item) {
            if ($item['offer_id'] == $item['good_id']) {
                $this->parse[$item['good_id']] = $item;
            } else {
                $this->parse[$item['good_id'] . '#' . $item['offer_id']] = $item;
            }
        }

        return $this->parse;
    }

    // квадрат чилса используя 2 переменных и только + и -
}
