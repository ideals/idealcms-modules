<?php
namespace Gallery\Structure\Fancy\Site;

use Ideal\Structure\News;

class ModelAbstract extends News\Site\Model
{

    public function getFotos($page)
    {
        $list = $this->getList($page);
        $foto = array();
        $tmp = '';
        foreach ($list as $v) {
            $title = array();
            if (strlen($v['cat']) < 1) $v['cat'] = 'non-cat';
            $v['dir_img'] = trim($v['dir_img'], ' /\\');
            $tmp = preg_split('/(\r\n|\r|\n)/su', $v['info']);
            foreach($tmp as $val) {
                $tmp2 = explode(':', $val);
                $title[trim($tmp2[0])] = trim($tmp2[1]);
            }
            $v['images'] = glob($v['dir_img'] . '/*.{jpg,png,gif}', GLOB_BRACE);
            natsort($v['images']);
            foreach($v['images'] as $key => $val) {
                unset($v['images'][$key]);
                $v['images'][$key]['src'] = '/'.$val;
                $pos = strrpos($val,'/');
                $pos = substr($val,$pos+1);
                $v['images'][$key]['title'] = (isset($title[$pos]))?$title[$pos] : $v['name'];
            }
            $v['amount'] = count($v['images']);
            $foto[$v['cat']][] = $v;
        }
        return $foto;
    }

}
