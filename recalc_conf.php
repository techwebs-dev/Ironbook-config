<?
$_SERVER["DOCUMENT_ROOT"]="/home/bitrix/ext_www/ironbook.ru";
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("catalog");
//CModule::IncludeModule("sale");
CModule::IncludeModule("iblock");


$arFilter = Array("IBLOCK_TYPE"=>"config","ACTIVE"=>"Y");
$res = CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, false);
while($arFields = $res->GetNext()){
	
	
 	
 		
 	$ar_res = CPrice::GetBasePrice($arFields["ID"]);
 	
 	$PRICE=CCurrencyRates::ConvertCurrency($ar_res["PRICE"], $ar_res["CURRENCY"], "RUB");
 	
 	if($ar_res["PRICE"]==0) continue;
 	
 	
 	CPrice::SetBasePrice($arFields["ID"], round($PRICE,-1), "RUB");
 			
 	
}

?>