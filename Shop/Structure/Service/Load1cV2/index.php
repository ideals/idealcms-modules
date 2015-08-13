<?php
use Shop\Structure\Service\Load1cV2;

$fc = new Load1cV2\FrontController();
$answer = array(
    'continue'  => true,
    'errors'    => array(),
);

$configs = \Ideal\Core\Config::getInstance();
$configs->cms = array_merge($configs->cms, array('errorLog'=>'var'));

switch ($step) {
    case 1:
        $fc->loadFiles($item['info']['directory']);
        $answer = array_merge($answer, $fc->category());
        break;
    case 2:
        $fc->loadFiles($item['info']['directory']);
        $answer = array_merge($answer, $fc->good());
        break;
    case 3:
        $fc->loadFiles($item['info']['directory']);
        $answer = array_merge($answer, $fc->directory());
        break;
    case 4:
        $fc->loadFiles($item['info']['directory']);
        $answer['step'] = 'Предложения';
        $answer = array_merge($answer, $fc->offer());
        $fc->renameTables();
        break;
    case 5:
        $answer['continue'] = false;
        $answer = array_merge($answer, $fc->loadImages($item['info']));
        break;
}

$errors = \Ideal\Core\Util::$errorArray;
if (count($errors) > 0) {
    $answer['continue'] = false;
    $answer['errors'] = $errors;
}

die(json_encode($answer));
