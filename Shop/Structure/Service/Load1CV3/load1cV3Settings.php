<?php
// @codingStandardsIgnoreFile
return array(
    'directory_for_processing' => "/tmp/1c/processing/", // Папка для временного хранения файлов в процессе обмена информацией с 1С | Ideal_Text
    'images_directory' => "import_files/", // Каталог изображений | Ideal_Text
    'resize' => "", // Значение ресайза изображения | Ideal_Text
    'filesize' => "20", // Максимальный размер файла в Мб | Ideal_Text
    'enable_zip' => "1", // Разрешить архивирование | Ideal_Checkbox
    'keep_log' => "1", // Осуществлять логирование | Ideal_Checkbox
    'keep_files' => "1", // Сохранять файлы выгрузки | Ideal_Checkbox
    'directory_for_keeping' => "/tmp/1c/webdata/000000001/", // Папка хранения выгрузки файлов | Ideal_Text
    'directory_report' => "/tmp/1c/", // Папка хранения отчётов | Ideal_Text
    'supportedExtensionsImage' => "jpeg\njpg\ngif\npng\nbmp", // поддерживаемые системой, расширения изображений (по одному в строку) | Ideal_Area
    'clear_comment' => "", // Данные для и исключения из комментариев приходящих от 1С (по одному на строку, формат "регулярные выражения") | Ideal_RegexpList
    'main_stock_id' => '', // Идентификатор главного склада для получения остатков | Ideal_Text
);
