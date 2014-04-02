<?php
namespace Catalog\Structure\Category\Site;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getSubCategories()
    {
        // todo сделать получение подкатегорий на основании текущей категории
        // с ключами массива в виде айдишников категорий и с дополнительным параметром parent_url
        // для каждой категории
    }

    public function getCidSegment()
    {
        $prev = $this->path[count($this->path) - 2];

        if ($prev['structure'] !== 'Catalog_Category') {
            return '';
        }

        $cidModel = new \Ideal\Field\Cid\Model($this->params['levels'], $this->params['digits']);
        $cid = $cidModel->getCidByLevel($this->pageData['cid'], $this->pageData['lvl'], false);
        return $cid;
    }
}
