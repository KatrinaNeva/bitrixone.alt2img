<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main;
use Bitrix\Iblock;
$mess = "";
$arIblockID = array();
$arID = array();
$result = 0;
// 1. все жесть как тормозит - нужна отладка
// 2. доделать проверку на обход всех инфоблоков - строки 14-16
// 3. В js-скрипте также доделать проверку на обход всех инфоблоков - строки 39-47
// 4. Возможно, продумать функционал, чтобы не было цикла foreach для инфоблоков
if(!empty($_POST["sendData"]["id"]) && CModule::IncludeModule('iblock')) {
	$iblockIds = explode(',', $_POST["sendData"]["id"]);
	if(!empty($_POST["sendData"]["oldIblockIds"]) && is_array($_POST["sendData"]["oldIblockIds"]) && is_array($iblockIds) && (count($iblockIds) == count($_POST["sendData"]["oldIblockIds"]))) {
		$result == 0;
		echo json_encode(['result' => $result]);
		exit();
	}
	foreach($iblockIds as $iblockId) {
		$entityClassNameForAlts = Iblock\Iblock::wakeUp((int)$iblockId)->getEntityDataClass();
		$rsElementForAlts = $entityClassNameForAlts::getList(['select' => ['ID', 'NAME', 'DETAIL_TEXT', 'ATT_TEXT_ALT'], 'filter' => ['ACTIVE' => 'Y', 'ATT_AUTO_ALT.ITEM.VALUE' => 'Да', '!ID'=>$_POST["sendData"]["oldIds"]], 'order' => ['ID' => 'ASC'], 'limit' => 1]);

		$elTxt = "";
		while ($obElement = $rsElementForAlts->fetchObject()) {
			$elTxt = ($obElement->getAttTextAlt() && !empty($obElement->getAttTextAlt()->getValue())) ? $obElement->getAttTextAlt()->getValue() : "";

			$count = substr_count(htmlspecialchars($obElement->getDetailText()), "img");
			if ($count > 0) {
	//                $imgsArr = array();
	//                preg_match_all('/<img[^>]+>/i',$arFields["DETAIL_TEXT"], $result); //отобрать все теги img в строке $html
	//                foreach($result as $img_tag)
	//                {
	//                    preg_match_all('/(alt|title|src)=("[^"]*")/i',$img_tag, $imgsArr[$img_tag]); //получение через регулярные выражения всех img атрибутов
	//                }
				$my_image_alt = !empty($elTxt) ? $elTxt : $obElement->getName();
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

				$doc = new DOMDocument();
				$doc->loadHTML(mb_convert_encoding($obElement->getDetailText(), 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	//            $xml=simplexml_import_dom($doc); // just to make xpath more simple
				$images = $doc->getElementsByTagName('img');
				$i = 1;
				foreach ($images as $img) {
					$img->setAttribute('alt', $my_image_alt." - изображение ".$i );
					$i++;
				}

				$obElement->setDetailText($doc->saveHTML($doc->documentElement));
				$obElement->save();
			}
			$result = 1;
			$arID[] = $obElement->getId();
			$exedElements = (isset($_POST["sendData"]["oldIds"])) ? $_POST["sendData"]["oldIds"] : $obElement->getId();
		
//            $obElement->getDetailText() = $doc->saveHTML($doc->documentElement);
//            $arFields["DETAIL_TEXT"] = $arFields["DETAIL_TEXT"]."<br>".$textPropValue;
			
		}
		if($result == 0) {
			$arIblockID[] = $iblockId;
		}
	}
	
	$mess = 'Alts в процессе обновления';
	echo json_encode(['result' => $result, 'ids' => $arID, 'iblockIds' => $arIblockID, 'message' => $mess, 'exedElements' => $exedElements]);
}