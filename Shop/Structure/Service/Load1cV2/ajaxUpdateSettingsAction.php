<?php
/**
 * Created by PhpStorm.
 * User: Help4
 * Date: 29.07.2015
 * Time: 18:37
 */
$conf = array_merge($item, array('info' => $_POST));
$str = "<?php\n\nreturn ";
file_put_contents('Mods/Shop/Structure/Service/Load1cV2/config.php', $str . var_export($conf, true) . ';');
