<?php
namespace Shop\Structure\Service\Load1CV3\Models;

use Ideal\Core\Config;
use Ideal\Field\Cid\Model as CidModel;
use Shop\Structure\Service\Load1CV3\Db\Category\DbCategory;
use Shop\Structure\Service\Load1CV3\ModelAbstract;
use Shop\Structure\Service\Load1CV3\Xml\Category\XmlCategory;
use Shop\Structure\Service\Load1CV3\Xml\Xml;

class GroupsModel extends ModelAbstract
{
    protected XmlCategory $xmlCategory;

    public function init(): void
    {
        $this->setInfoText('Обработка категорий каталога (groups)');
        $this->setSort(60);

        // инициализируем модель категорий в XML - XmlCategory
        $xml = new Xml($this->filename);
        $this->xmlCategory = new XmlCategory($xml);
        $this->isOnlyUpdate = $this->xmlCategory->updateInfo();
    }

    /**
     * Запуск процесса обработки файлов propertiesGoods_*.xml
     *
     * @param int $packageNum Номер пакета
     * @return array Ответ по факту обработки файла
     */
    public function startProcessing($packageNum): array
    {
        $this->packageNum = $packageNum;

        $xmlCategoryXml = $this->xmlCategory->getXml();

        if (!empty($xmlCategoryXml)) {
            // инициализируем модель категорий в БД - DbCategory
            $dbCategory = new DbCategory();

            // Устанавливаем связь БД и XML
            $this->categoryParse($dbCategory, $this->xmlCategory);

            // Создание категории товаров, у которых в выгрузке не присвоена категория
            $dbCategory->createDefaultCategory();
        }

        return $this->answer();
    }

    /**
     * Преобразование XML выгрузки в массив и сравнение с данными из БД
     *
     * @param DbCategory $dbCategory объект категорий БД
     * @param XmlCategory $xmlCategory объект категорий XML
     */
    protected function categoryParse($dbCategory, $xmlCategory)
    {
        $config = Config::getInstance();
        $part = $config->getStructureByName('Ideal_Part');
        $cid = new CidModel($part['params']['levels'], $part['params']['digits']);

        // Собираем данные по категориям из xml
        $xmlResult = $xmlCategory->parse();

        // Собираем данные по категориям из БД
        $dbCategory->setCategoryKeys(array_keys($xmlResult));
        $dbResult = $dbCategory->parse();

        // Проходим по выгрузке бд и вставляем в xml данные из бд с is_active = 0
        foreach ($dbResult as $key => $dbElement) {
            // Если в БД not-1c - вставляем элемент к его предку, данные оставляем из БД
            if ($dbElement['id_1c'] === 'not-1c') {
                $parentCid = $cid->getCidByLevel($dbElement['cid'], $dbElement['lvl'] - 1, false);
                $parentCid = $cid->reconstruct($parentCid);
                $parent = $dbResult[$parentCid]['id_1c'];
                $data = [
                    'ID' => $dbElement['ID'],
                    'parent' => $parent,
                    'is_active' => $dbElement['is_active'],
                    'pos' => $cid->getBlock($dbElement['cid'], $dbElement['lvl']),
                    'Ид' => $dbElement['id_1c'],
                    'Наименование' => $dbElement['name']
                ];
                $xmlCategory->addChild($data);
            } elseif (isset($xmlResult[$key])) {
                // Добавляем информацию из бд в xml
                $data = [
                    'ID' => $dbElement['ID'],
                    'pos' => $cid->getBlock($dbElement['cid'], $dbElement['lvl']),
                    'Ид' => $dbElement['id_1c']
                ];
                $xmlCategory->updateElement($data);
            }
        }

        $keys = [];
        $cidNum = '001';
        // Получаем обновленную сплющенную информацию по категориям из XML
        $xmlCategory->updateConfigs();
        $newXmlResult = $xmlCategory->parse();

        // Проставляем cid категориям, обновляем поля
        foreach ($newXmlResult as $k => $xmlElement) {
            $i = 1;
            if (isset($xmlElement['pos']) && $xmlElement['pos'] !== '') {
                $i = (int)$xmlElement['pos'];
            }
            $fullCid = $cid->setBlock($cidNum, $xmlElement['lvl'], $i, true);
            while (in_array($fullCid, $keys)) {
                $fullCid = $cid->setBlock($cidNum, $xmlElement['lvl'], ++$i, true);
            }
            $cidNum = $fullCid;
            $xmlElement['cid'] = $fullCid;
            $keys[] = $fullCid;
            unset($xmlElement['pos']);

            // Если идентичная запись уже есть в БД, то переходим к рассмотрению следующего элемента
            if (array_key_exists($k, $dbResult)
                && count(array_diff_assoc($xmlElement, $dbCategory->getMainPartCategory($dbResult[$k]))) === 0) {
                continue;
            }

            if (!isset($xmlElement['is_active']) || $xmlElement['is_active'] == '') {
                $xmlElement['is_active'] = '0';
            }

            if (isset($dbResult[$k])) {
                // Если запись уже существует в базе, то обновляем её
                $xmlElement['ID'] = $dbResult[$k]['ID'];
                $this->answer['update']++;
                $dbCategory->update($xmlElement, $dbResult[$k]);
                $this->answer['tmpResult']['category']['update'][$xmlElement['id_1c']] = 1;
            } else {
                // Если это новая запись, то записываем её в БД
                unset($xmlElement['ID']);
                $this->answer['add']++;
                $dbCategory->insert($xmlElement);
                $this->answer['tmpResult']['category']['insert'][$xmlElement['id_1c']] = 1;
            }
        }
    }
}
