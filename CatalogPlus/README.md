CatalogPlus v. 1.2
=========

После подключения выполнить запрос из файлы table.sql

В версии 1.2 изменена систем вызова контроллеров.
Теперь для категорий товара всегда вызывается контроллер категорий,
а контроллер товара срабатывает только на карточке товара.

Внутри модели CatalogPlus_Category всегда находится модель
CatalogPlus_Good, а в модели CatalogPlus_Good всегда находится
модель CatalogPlus_Category.

Для **sql** запросов использовать:

1. `'prefix'_catalogplus_structure_good as g`

2. `'prefix'_catalogplus_structure_category as cat`