<?
$_SERVER["DOCUMENT_ROOT"]="/home/bitrix/ext_www/ironbook.ru";
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("catalog");
//CModule::IncludeModule("sale");
CModule::IncludeModule("iblock");

$res = CIBlock::GetList(
	Array("SORT"=>"ASC"), 
	Array(
		'TYPE'=>'config', 
		'SITE_ID'=>SITE_ID, 
		'ACTIVE'=>'Y', 
		"CNT_ACTIVE"=>"Y"
	), true
);
while($ar_res = $res->Fetch()){
	$CONFIGURATOR[]=array("ID"=>$ar_res['ID'], "NAME"=>$ar_res['NAME'], "CODE"=>$ar_res['CODE']);
}


foreach($CONFIGURATOR as $iblock){
	$arSelect = array("CATALOG_GROUP_6", "NAME", "ID", "IBLOCK_ID");
	$arFilter = Array("IBLOCK_ID"=>$iblock["ID"], "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
	$res = CIBlockElement::GetList(Array("SORT"=>"ASC", "NAME"=>"ASC"), $arFilter, false, false, $arSelect);
	while($arFields = $res->GetNext()){
		//var_dump($arFields);
		$PRICES[$arFields["ID"]]=array("PRICE"=>$arFields["CATALOG_PRICE_6"], "NAME"=>$arFields["NAME"]);
	}
}
unset($CONFIGURATOR);

//Выбираем компы

$arFilter = Array("IBLOCK_ID"=>81);
$res = CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, false);
while($ob = $res->GetNextElement()){
	$arFields = $ob->GetFields();  
	$arProps = $ob->GetProperties();
 	$PRICE=0;
 	
	//echo $arFields["ID"];
 	
 	$ar_res_otp = CPrice::GetBasePrice($arFields["ID"]);
 	//var_dump($ar_res_otp);
 	
 	foreach($arProps as $pr){
 		if($pr["CODE"]=="name2" || $pr["CODE"]=="garanty") continue;
 		$PRICE+=$PRICES[$pr["VALUE"]]["PRICE"];
 	}
 	
 	CPrice::SetBasePrice($arFields["ID"], $PRICE, "RUB");
}

?>