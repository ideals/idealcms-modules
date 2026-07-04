<?php

namespace Gallery\Structure\Fancy\Site;

use Ideal\Core\Site\Model;

class ModelAbstract extends Model
{
    public function detectPageByUrl($path, $url): Model
    {
        // Отдельная фотография не имеет своего URL, поэтому если мы сюда попали, то это ошибка
        $this->path = $path;
        $this->is404 = true;
        return $this;
    }

    /**
     * @return non-empty-list[]
     */
    public function getList($page = null): array
    {
        $list = parent::getList($page);
        $photos = [];
        foreach ($list as $v) {
            $title = [];
            if (strlen($v['cat']) < 1) {
                $v['cat'] = 'non-cat';
            }

            $v['dir_img'] = trim($v['dir_img'], ' /\\');
            $tmp = preg_split('/(\r\n|\r|\n)/su', $v['info']);
            foreach ($tmp as $val) {
                $tmp2 = explode(':', $val);
                $tmp2[1] ??= '';
                $title[trim($tmp2[0])] = trim($tmp2[1]);
            }

            $v['images'] = glob($v['dir_img'] . '/*.{jpg,png,gif}', GLOB_BRACE);
            natsort($v['images']);
            foreach ($v['images'] as $key => $val) {
                unset($v['images'][$key]);
                $v['images'][$key]['src'] = '/' . $val;
                $pos = strrpos($val, '/');
                $pos = substr($val, $pos + 1);
                $v['images'][$key]['title'] = $title[$pos] ?? $v['name'];
            }

            $v['amount'] = count($v['images']);
            $photos[$v['cat']][] = $v;
        }

        return $photos;
    }

    /**
     * @return array{}
     */
    public function getStructureElements(): array
    {
        return [];
    }

    protected function getWhere($where): string
    {
        return 'WHERE ' . $where . ' AND is_active=1';
    }
}
