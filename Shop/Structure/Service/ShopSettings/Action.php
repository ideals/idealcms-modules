<form action="" method=post enctype="multipart/form-data">

    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\SiteData\ConfigPhp();

    $filePath = stream_resolve_include_path("Shop/Structure/Service/ShopSettings/shop_settings.php");

    $file->loadFile($filePath);

    if (isset($_POST['edit'])) {
        $file->changeAndSave($filePath);
    }

    echo $file->showEdit();
    ?>

    <br/>

    <input type="submit" class="btn btn-info" name="edit" value="Сохранить настройки"/>
</form>