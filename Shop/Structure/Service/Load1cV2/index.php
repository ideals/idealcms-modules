<?php
use Shop\Structure\Service\Load1cV2;

$fc = new Load1cV2\FrontController();
$answer = array(
    'continue' => true,
);

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
        $answer = array_merge($answer, $fc->offer());
        $answer['continue'] = false;
        break;
}

die(json_encode($answer));
