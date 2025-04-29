<?php
namespace Bitrixone\Alt2Img;
use \Bitrix\Main\Config\Option;
use \Bitrix\Iblock\PropertyTable;
//use \CIBlockPropertyEnum;

use Bitrix\Iblock;
class Main{

    public function createPropsAtIb(){

        if(defined("ADMIN_SECTION") && ADMIN_SECTION == true){
		
            $module_id = pathinfo(dirname(__DIR__))["basename"];
            $iblockIds = (string)Option::get($module_id, 'source_iblocks');

            if ($iblockIds !== '')
            {
                $iblockIds = explode(',', $iblockIds);

                foreach ($iblockIds as $id) {
                    if (\Bitrix\Main\Loader::includeModule('iblock')) {

                        $arPropsCodes = self::checkProps($id);

                        if(!in_array("ATT_AUTO_ALT", $arPropsCodes)) {
                        //if(false) {
                            PropertyTable::add(
                                [
                                    'IBLOCK_ID' => $id,
                                    'NAME' => 'Автозаполнение alt у изображений',
                                    'CODE' => 'ATT_AUTO_ALT',
                                    'PROPERTY_TYPE' => 'L'
                                ]
                            );
                            $property = PropertyTable::getList(array(
                                'select' => array('*'),
                                'filter' => array('IBLOCK_ID' => $id, 'CODE' => 'ATT_AUTO_ALT')
                            ))->fetch();




                            //---------->>> Переписать метод на основе D7 <<<----------
                            $ibpenum = new \CIBlockPropertyEnum();
                            $valueId = $ibpenum->Add([
                                'PROPERTY_ID' => $property['ID'],
                                'VALUE' => 'Да',
                                'XML_ID' => 'Y',
                                'DEF' => 'Y'
                            ]);
                            if ((int) $valueId < 0) {
                                throw new \Exception('Unable to add a value');
                            }
                            //---------->>> Переписать метод на основе D7 <<<----------



                            //---------->>> Написать код, который обойдет все элементы текущего ИБ и проставит им значение "Да" у свойства "Автозаполнение alt у изображений"  <<<----------
                            // переменные
                            $IBLOCK_ID = $id;
                            $PROP_CODE = 'ATT_AUTO_ALT';
                            $PROP_VALUE = 'Да';

// найдем код значения св-ва типа "Список"
                            $dbPropVals = \CIBlockProperty::GetPropertyEnum($PROP_CODE, [], ["IBLOCK_ID"=>$IBLOCK_ID, "VALUE"=>$PROP_VALUE]);
                            $arPropVal = $dbPropVals->GetNext();

                            \Bitrix\Main\Diag\Debug::dump($arPropVal);
// установим всем элементам с неустановленным свойством значение найденное выше

                            $arFilter = [
                                'IBLOCK_ID' => $IBLOCK_ID,
                                //'ID' => 66
                            ];

                            $arSelect = [
                                'ID',
                                'NAME',
                                'PROPERTY_'.$PROP_CODE,
                            ];

                            $dbEls = \CIBlockElement::GetList(['ID' => 'ASC'], $arFilter, false, false, $arSelect);

                            while ($arEl = $dbEls->GetNext()) {
                                if(!$arEl['PROPERTY_'.$PROP_CODE.'_VALUE'] && $arPropVal['ID']) {
                                    \CIBlockElement::SetPropertyValues($arEl['ID'], $IBLOCK_ID, $arPropVal['ID'], $PROP_CODE);
                                }
                            }
                            //---------->>> Написать код, который обойдет все элементы текущего ИБ и проставит им значение "Да" у свойства "Автозаполнение alt у изображений" <<<----------
                        }
                        if(!in_array("ATT_TEXT_ALT", $arPropsCodes)) {
                        //if(false) {
                            PropertyTable::add(
                                [
                                    'IBLOCK_ID' => $id,
                                    'NAME' => 'Текст для alt-ов',
                                    'CODE' => 'ATT_TEXT_ALT',
                                    'PROPERTY_TYPE' => 'S',
                                ]
                            );
                        }
                    }
                }
            }
        }
        return false;
    }


    // public function deletePropsAtIb(){
        // if(defined("ADMIN_SECTION") && ADMIN_SECTION == true){
			
            // $module_id = pathinfo(dirname(__DIR__))["basename"];
            // $iblockIds = (string)Option::get($module_id, 'source_iblocks');

            // if ($iblockIds !== '')
            // {
                // $iblockIds = explode(',', $iblockIds);

                // foreach ($iblockIds as $id) {
                    // if (\Bitrix\Main\Loader::includeModule('iblock')) {

                        // $arPropsCodes = self::checkProps($id, true);

                        // if(in_array("ATT_AUTO_ALT", $arPropsCodes)) {
                            // PropertyTable::delete(
                                // array_search('ATT_AUTO_ALT', $arPropsCodes)
                            // );
                        // }
                        // if(in_array("ATT_TEXT_ALT", $arPropsCodes)) {
                            // PropertyTable::delete(
                                // array_search('ATT_TEXT_ALT', $arPropsCodes)
                            // );
                        // }
                    // }
                // }
            // }
        // }
        // return false;
    // }



    private function checkProps($iblock_id, $is_id = false):array
    {
		$arPropsCodesById = array();
            $arProps = PropertyTable::getList(array(
                'select' => array('*'),
                'filter' => array('IBLOCK_ID' => $iblock_id)
            ))->fetchAll();
			

            foreach ($arProps as $prop) {
                if($is_id) {
                    $arPropsCodesById[$prop["ID"]] = $prop["CODE"];
                } else {
                    $arPropsCodesById[] = $prop["CODE"];
                }
            }
           
            return $arPropsCodesById;

    }

    
    public function OnBeforeIBlockElementAddOrUpdateHandler(&$arFields)
    {
        # подключаем модули
        \Bitrix\Main\Loader::includeModule('iblock'); // подключаем инфоблоки
        $module_id = pathinfo(dirname(__DIR__))["basename"];
        $iblockIds = (string)Option::get($module_id, 'source_iblocks');
        $iblockIds = explode(',', $iblockIds);


            if (in_array($arFields["IBLOCK_ID"], $iblockIds)) {

                $propValues = $arFields["PROPERTY_VALUES"];
                $arProps = PropertyTable::getList(array(
                    'select' => array('*'),
                    'filter' => array('IBLOCK_ID' => $arFields["IBLOCK_ID"])
                ))->fetchAll();

                foreach($arProps as $prop) {
                    $propMap[$prop["ID"]] = $prop["CODE"];// Массив полей, за которым следим в виде ID св-ва => тайтл
                }

                $autoPropValue = "";
                $textPropValue = "";

                foreach ($propMap as $propId => $propTitle) {
                    if($propTitle == 'ATT_AUTO_ALT') {
                        $autoPropValueEnum = \Bitrix\Iblock\PropertyEnumerationTable::getList(array(
                            'filter' => array('PROPERTY_ID'=>$propId),
                        ));
                        $autoPropValue = $propValues[$propId][array_key_first($propValues[$propId])]["VALUE"];
                    }
                    if($propTitle == 'ATT_TEXT_ALT') {
                        $textPropValue = trim($propValues[$propId][array_key_first($propValues[$propId])]["VALUE"]);
                    }
                }

                while($arEnum=$autoPropValueEnum->fetch()) {
                    $enumWeNeed = ($arEnum["VALUE"] == "Да") ? $arEnum["ID"] : '';
                }
 

                //id значения свойства ATT_AUTO_ALT типа Список
                if ($autoPropValue == $enumWeNeed) {
                    $count = substr_count(htmlspecialchars($arFields["DETAIL_TEXT"]), "img");
                    if ($count > 0) {
                        $my_image_alt = !empty($textPropValue) ? $textPropValue : $arFields["NAME"];
                        // Sanitize the title: remove hyphens, underscores & extra spaces:
                        $my_image_alt = preg_replace('%\s*[-_\s]+\s*%', ' ', $my_image_alt);
                        // Sanitize the title: capitalize first letter of every word (other letters lower case):
                        $my_image_alt = str_replace('"', '', $my_image_alt);
                        $my_image_alt = str_replace('«', '', $my_image_alt);
                        $my_image_alt = str_replace('»', '', $my_image_alt);
                        $my_image_alt = str_replace('—', '', $my_image_alt);
                        $my_image_alt = str_replace(':', '', $my_image_alt);
                        $my_image_alt = str_replace('  ', ' ', $my_image_alt);
                        $my_image_alt = str_replace('   ', ' ', $my_image_alt);
                        $my_image_alt = mb_strtolower($my_image_alt);

                        $doc = new \DOMDocument();
                        $doc->loadHTML(mb_convert_encoding($arFields["DETAIL_TEXT"], 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                        $images = $doc->getElementsByTagName('img');
                        $i = 1;
                        foreach ($images as $img) {
                            $img->setAttribute('alt', $my_image_alt . " - изображение " . $i);
                            $i++;
                        }
                       // $arFields["DETAIL_TEXT"] = $doc->saveHTML($doc->documentElement);

                    }
                }
            }
    }
}