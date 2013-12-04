<?php
namespace Catalog\Structure\Category\Site;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function detectPageByUrl($path, $url)
    {
        parent::detectPageByUrl($path, $url);
        $_REQUEST['action'] = 'detail';
        return $this;
    }
}