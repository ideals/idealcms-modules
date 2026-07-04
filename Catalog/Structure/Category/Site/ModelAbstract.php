<?php

namespace Catalog\Structure\Category\Site;

use Ideal\Field\Cid\Model;

class ModelAbstract extends \Ideal\Structure\Part\Site\ModelAbstract
{
    public function getSubCategories(): void
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

        $cidModel = new Model($this->params['levels'], $this->params['digits']);
        return $cidModel->getCidByLevel($this->pageData['cid'], $this->pageData['lvl'], false);
    }
}
