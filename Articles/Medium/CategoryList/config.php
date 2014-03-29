<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

return array(
    'params' => array(
        'has_table' => true
        // ALTER TABLE `i_articles_category_article` RENAME AS `i_articles_medium_categorylist`;
    ),
    'fields' => array(
        'article_id' => array(
            'label' => 'Идентификатор владельца',
            'sql'   => 'int(11)',
        ),
        'category_id' => array(
            'label' => 'Идентификатор элемента',
            'sql'   => 'int(11)',
        )
    )
);
