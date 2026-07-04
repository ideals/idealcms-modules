<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
return [
    'params' => [
        'has_table' => true,
    ],
    'fields' => [
        'good_id' => [
            'label' => 'Идентификатор родителя',
            'sql'   => 'int(11)',
        ],
        'category_id' => [
            'label' => 'Идентификатор потомка',
            'sql'   => 'int(11)',
        ],
    ],
];
