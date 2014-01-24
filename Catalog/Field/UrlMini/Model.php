<?php
namespace Catalog\Field\UrlMini;

use Ideal\Core\Config;
use Ideal\Core\PluginBroker;

class Model extends \Ideal\Field\Url\Model
{
    protected $fieldName; // TODO сделать возможность определять url Не только по полю url
    protected $parentUrl;

    public function __construct($fieldName = 'url')
    {
        // TODO доработать тут и в контроллере возможность указывать кастомное название поля url
        $this->fieldName = $fieldName;
    }

}