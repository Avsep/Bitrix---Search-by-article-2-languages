<?php
	class CustomEventHandler
	{
		function BeforeIndexHandler($arFields)
		{
			if ($arFields["MODULE_ID"] == "iblock" && $arFields["PARAM2"] == 20) {
				
				if(!CModule::IncludeModule("iblock")) // подключаем модуль
                return $arFields;
				
				// Запросим свойства индексируемого элемента
				$db_props = CIBlockElement::GetProperty(
                $arFields["PARAM2"],         // BLOCK_ID индексируемого свойства
                $arFields["ITEM_ID"],          // ID индексируемого свойства
                array("sort" => "asc"),       // Сортировка (можно упустить)
                Array("CODE" => "CML2_ARTICLE")); // CODE свойства (в данном случае артикул)
				if($ar_props = $db_props->Fetch())
				{
					$arFields["TITLE"] .= " ".$ar_props["VALUE"];   // Добавим свойство в конец заголовка индексируемого элемента
				}
				
				$arFields["PARAMS"]["iblock_section"] = array();
				if (substr($arFields["ITEM_ID"], 0, 1) != "S") {
					$rsSections = CIBlockElement::GetElementGroups($arFields["ITEM_ID"], true);
					while ($arSection = $rsSections->Fetch()) {
						$nav = CIBlockSection::GetNavChain(20, $arSection["ID"]);
						while ($ar = $nav->Fetch()) {
							$arFields["PARAMS"]["iblock_section"][] = $ar['ID'];
						}
					}
					} else {
					$nav = CIBlockSection::GetNavChain(CATALOG_IBLOCK_ID, substr($arFields["ITEM_ID"], 1, strlen($arFields["ITEM_ID"])));
					while ($ar = $nav->Fetch()) {
						$arFields["PARAMS"]["iblock_section"][] = $ar['ID'];
					}
				}
			}
			return $arFields;
		}
	}
	
	//-- регистрируем обработчик для поиска товаров по свойству CML2_ARTICLE и разделах
	AddEventHandler("search", "BeforeIndex", Array("CustomEventHandler", "BeforeIndexHandler"));
	
	
	AddEventHandler("sale", "OnOrderNewSendEmail", "bxModifySaleMails");
	
	//-- Собственно обработчик события
	
	function bxModifySaleMails($orderID, &$eventName, &$arFields)
	{
		CModule::IncludeModule("iblock");
		CModule::IncludeModule("catalog");
		CModule::IncludeModule("sale");
		global $USER;
		
		
		$strOrderList = "";
		$arBasketList = array();
		$fullBasketList = array();
		$dbBasketItems = CSaleBasket::GetList(
		array("ID" => "ASC"),
		array("ORDER_ID" => $orderID),
		false,
		false,
		array("ID", "PRODUCT_ID", "NAME", "QUANTITY", "PRICE", "CURRENCY", "TYPE", "SET_PARENT_ID")
		);
		while ($arItem = $dbBasketItems->Fetch()) {
			if (CSaleBasketHelper::isSetItem($arItem))
			continue;
			
			$arBasketList[] = $arItem;
		}
		
		$arBasketList = getMeasures($arBasketList);
		
		//-- РУ
		if (!empty($arBasketList) && is_array($arBasketList))  {
			foreach ($arBasketList as $arItem)
			{
				
				$intElementID = $arItem['PRODUCT_ID'];
				$mxResult = CCatalogSku::GetProductInfo($intElementID);
				
				
				if (is_array($mxResult)) {
					$db_props = CIBlockElement::GetProperty(20, $mxResult['ID'], array("sort" => "asc"), Array("CODE"=>"CML2_ARTICLE"));
					if($ar_props = $db_props->Fetch()) {
						$ART_NUMB = $ar_props["VALUE"];
					}
				}
				
				$db_props = CIBlockElement::GetProperty(23, $arItem['PRODUCT_ID'], array("sort" => "asc"), Array("CODE"=>"COLOR_REF"));
				if($ar_props = $db_props->Fetch()) {
					CModule::IncludeModule('highloadblock');
					$hldata = Bitrix\Highloadblock\HighloadBlockTable::getById(5)->fetch(); /// тут id вашей таблицы посмотреть можно в админке в разделе хайлоад инфоблоках
					$hlentity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
					$hlDataClass = $hldata['NAME'].'Table';
					$result = $hlDataClass::getList(array(
					'select' => array('ID', 'UF_NAME', 'UF_XML_ID', 'UF_FILE'),
					'order' => array('UF_NAME' =>'ASC'),
					'filter' => array('UF_XML_ID'=> $ar_props["VALUE"]),
					));
					if($res = $result->fetch()) {
						$COLOR = $res['UF_NAME'];   
					}
				}
				//}
				
				$measureText = (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"])) ? $arItem["MEASURE_TEXT"] : GetMessage("SOA_SHT");
				$strCustomOrderList .= "<tr><td style='padding: 7px; text-align:center;'>".$ART_NUMB."</td><td style='padding: 7px; text-align:center;'>".$arItem['NAME']."</td>
				
				<td style='padding: 7px; text-align:center;'>".$arItem['QUANTITY']." ".$measureText."</td>
				
				<td style='padding: 7px; text-align:center;'>".SaleFormatCurrency($arItem["PRICE"], $arItem["CURRENCY"])."</td><tr>";
			}
		}
		
		//--УКР версия
		if (!empty($arBasketList) && is_array($arBasketList))  {
			foreach ($arBasketList as $arItem)
			{
				
				$intElementID = $arItem['PRODUCT_ID'];
				$mxResult = CCatalogSku::GetProductInfo($intElementID);
				
				
				if (is_array($mxResult)) {
					$db_props = CIBlockElement::GetProperty(43, $mxResult['ID'], array("sort" => "asc"), Array("CODE"=>"CML2_ARTICLE"));
					if($ar_props = $db_props->Fetch()) {
						$ART_NUMB = $ar_props["VALUE"];
					}
				}
				
				$db_props = CIBlockElement::GetProperty(23, $arItem['PRODUCT_ID'], array("sort" => "asc"), Array("CODE"=>"COLOR_REF"));
				if($ar_props = $db_props->Fetch()) {
					CModule::IncludeModule('highloadblock');
					$hldata = Bitrix\Highloadblock\HighloadBlockTable::getById(5)->fetch(); /// тут id вашей таблицы посмотреть можно в админке в разделе хайлоад инфоблоках
					$hlentity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hldata);
					$hlDataClass = $hldata['NAME'].'Table';
					$result = $hlDataClass::getList(array(
					'select' => array('ID', 'UF_NAME', 'UF_XML_ID', 'UF_FILE'),
					'order' => array('UF_NAME' =>'ASC'),
					'filter' => array('UF_XML_ID'=> $ar_props["VALUE"]),
					));
					if($res = $result->fetch()) {
						$COLOR = $res['UF_NAME'];
					}
				}
				
				$measureText = (isset($arItem["MEASURE_TEXT"]) && strlen($arItem["MEASURE_TEXT"])) ? $arItem["MEASURE_TEXT"] : GetMessage("SOA_SHT");
				$strCustomOrderList2 .= "<tr><td style='padding: 7px; text-align:center;'>".$ART_NUMB."</td><td style='padding: 7px; text-align:center;'>".$arItem['NAME']."</td>
				
				<td style='padding: 7px; text-align:center;'>".$arItem['QUANTITY']." ".$measureText."</td>
				
				<td style='padding: 7px; text-align:center;'>".SaleFormatCurrency($arItem["PRICE"], $arItem["CURRENCY"])."</td><tr>";
			}
		}
	?>	
