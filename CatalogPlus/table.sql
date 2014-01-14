--
-- Структура таблицы `{prefix}catalogplus_good`
-- Обычно префекс 'i_'
--

CREATE TABLE IF NOT EXISTS `i_catalogplus_good` (
  `category_id` char(37) default NULL,
  `good_id` char(37) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;