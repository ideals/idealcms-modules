<?php
namespace Cabinet\Structure\Registration\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;

class AjaxController extends \Ideal\Core\Site\AjaxController
{

    public function loginAction(){

    }

    public function registrationAction(){
        session_start();
        $errStr = '';

        // Check e-mail
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errStr .= "Не верный E-mail";
        }

        // Check captcha
        $c = $_POST['captcha'];
        $c = md5($c);
        if ($c != $_SESSION['cryptcode']) {
            $errStr .= "Не верная captcha";
        }

        if($errStr != ''){
            print $errStr;
            return;
        }

        $db = Db::getInstance();
        $config = Config::getInstance();
        $table = $config->db['prefix'] . 'cabinet_structure_registration';
        $tmp = $db->queryArray("SELECT ID FROM {$table} WHERE email='{$_POST['email']}' LIMIT 1");
        if (count($tmp) > 0){
            print 'Такой Email зарегестрирован';
            return;
        }

        $structure_path = $config->getStructureByName('Cabinet_Registration');
        $structure_path = $structure_path['ID'];

        $key = md5(time());
        $db->insert($config->db['prefix'] . 'cabinet_structure_registration', array(
            'email' => $_POST['email'],
            'password' => crypt($_POST['pass']),
            'is_active' => 0,
            'structure_path' => $structure_path,
            'act_key' => $key
        ));

        print '<pre>';print_r($config->getStructureByName('Cabinet_Registration'));
    }

}
