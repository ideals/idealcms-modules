<?php
namespace Shop\Structure\Service\Load1c_v2;

/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 02.07.2015
 * Time: 16:34
 */

class NewCategory
{
    protected $result = array();
    protected $answer = array();

    /**
     * @param DbCategory $dbCategory
     * @param XmlCategory $xmlCategory
     */
    public function parse($dbCategory, $xmlCategory)
    {
        $this->result['dbResult'] = $dbCategory->parse();
        $this->result['xmlResult'] = $xmlCategory->parse();
        $this->diff();
        $result = array();
        $this->result = $result;
    }

    protected function diff()
    {
//        $this->result['xmlResult']['keys'] = array_keys($this->result['xmlResult']);
//        $this->result['dbResult']['keys'] = array_keys($this->result['dbResult']);

        $arr['add'] = array_diff($this->result['xmlResult'], $this->result['dbResult']);
        $arr['delete'] = array_diff($this->result['dbResult']['keys'], $this->result['xmlResult']['keys']);

    }

    protected function findParent($groups)
    {

    }

    public function answer()
    {
        return $this->answer;
    }
}
