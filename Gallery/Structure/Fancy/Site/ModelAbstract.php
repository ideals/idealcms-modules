<?php
namespace Gallery\Structure\Fancy\Site;

class ModelAbstract extends \Ideal\Core\Site\Model
{
    public function detectPageByUrl($path, $url)
    {
        // Отдельная фотография не имеет своего URL, поэтому если мы сюда попали, то это ошибка
        $this->path = $path;
        $this->is404 = true;
        return $this;
    }

    public function getList($page)
    {
        $list = parent::getList($page);
        $photos = array();
        foreach ($list as $v) {
            $title = array();
            if (strlen($v['cat']) < 1) {
                $v['cat'] = 'non-cat';
            }
            $v['dir_img'] = trim($v['dir_img'], ' /\\');
            $tmp = preg_split('/(\r\n|\r|\n)/su', $v['info']);
            foreach ($tmp as $val) {
                $tmp2 = explode(':', $val);
                $tmp2[1] = isset($tmp2[1]) ? $tmp2[1] : '';
                $title[trim($tmp2[0])] = trim($tmp2[1]);
            }
            $v['images'] = glob($v['dir_img'] . '/*.{jpg,png,gif}', GLOB_BRACE);
            natsort($v['images']);
            foreach ($v['images'] as $key => $val) {
                unset($v['images'][$key]);
                $v['images'][$key]['src'] = '/' . $val;
                $pos = strrpos($val, '/');
                $pos = substr($val, $pos + 1);
                $v['images'][$key]['title'] = (isset($title[$pos])) ? $title[$pos] : $v['name'];
            }
            $v['amount'] = count($v['images']);
            $photos[$v['cat']][] = $v;
        }
        return $photos;
    }

    public function getStructureElements()
    {
        return array();
    }
}
