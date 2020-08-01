<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

function change_cases(){
	CModule::IncludeModule("catalog");
	CModule::IncludeModule("sale");
	$el = new CIBlockElement;
	$CO = 0;
	if($_REQUEST["s"]==$_REQUEST["to"]){
			$EXIT["status"]="ok";
			$EXIT["text"]="Нечего делать";
	}else{
		//берем информацию по кейсам, по старому и новому
		$res = CIBlockElement::GetByID($_REQUEST["s"]);
		if($ar_res = $res->GetNext()){
	  		$OLD=$ar_res;
	  		$OLD_PRICE = CPrice::GetBasePrice($_REQUEST["s"]);
	  	}
	  	
	  	$res = CIBlockElement::GetByID($_REQUEST["to"]);
		if($ar_res = $res->GetNext()){
	  		$NEW=$ar_res;
	  		$NEW_PRICE = CPrice::GetBasePrice($_REQUEST["to"]);
	  	}
	  	
	  	
	  	$tar = explode(":", $_REQUEST["razdel"]);
		
		$IB = $tar[0];
		$SEC = $tar[1];
	  	
	  	//теперь выбираем все компы со старым корпусом
	  	$arFilter = Array("IBLOCK_ID"=>$IB,"PROPERTY_".$_REQUEST["complektuha"]=>$_REQUEST["s"]);
	  	if($SEC>0) $arFilter["SECTION_ID"]=$SEC;
	  	
		$res = CIBlockElement::GetList(Array(), $arFilter, false,false, false);
		while($arFields2 = $res->GetNext()){
			
			//обновили цену
			$COMP_OLD_PRICE = CPrice::GetBasePrice($arFields2["ID"]);
			$COMP_NEW_PRICE = $COMP_OLD_PRICE["PRICE"]-$OLD_PRICE["PRICE"]+$NEW_PRICE["PRICE"];
			//var_dump($COMP_OLD_PRICE);
			//var_dump($COMP_NEW_PRICE);
			CPrice::SetBasePrice($arFields2["ID"], $COMP_NEW_PRICE, "RUB");
			
			//обновили имя, описание и картинки корпуса
			$NEW_NAME = str_replace($OLD["NAME"], $NEW["NAME"], $arFields2["NAME"]);
			$NEW_PTEXT = str_replace($OLD["NAME"], $NEW["NAME"], $arFields2["PREVIEW_TEXT"]);
			$arLoadProductArray = Array(
				"NAME"           => $NEW_NAME,
				"PREVIEW_TEXT"   => $NEW_PTEXT
			);
			
			if($_REQUEST["complektuha"]=="korpus"){
				if($NEW["DETAIL_PICTURE"]>0) $arLoadProductArray["DETAIL_PICTURE"]=CFile::MakeFileArray($NEW["DETAIL_PICTURE"]);
				else $arLoadProductArray["DETAIL_PICTURE"]="";
				
				if($NEW["PREVIEW_PICTURE"]>0) $arLoadProductArray["PREVIEW_PICTURE"]=CFile::MakeFileArray($NEW["PREVIEW_PICTURE"]);
				else $arLoadProductArray["PREVIEW_PICTURE"]="";
			}
			
			$el->Update($arFields2["ID"], $arLoadProductArray);
			
			//обновим свойство привязку к корпусу
			CIBlockElement::SetPropertyValuesEx($arFields2["ID"], false, array($_REQUEST["complektuha"] => $NEW["ID"]));
			$CO++;
			
			generate_item_feed($arFields2["ID"]);
		
		}		
				
		
		$EXIT["status"]="ok";
		$EXIT["text"]="Обновлено элементов: ".$CO;
	}
	
	echo json_encode($EXIT);
}


function edite_price(){
	CModule::IncludeModule("catalog");
	CModule::IncludeModule("sale");
	
	$client = new GearmanClient();
	$client->addServer('127.0.0.1', '4730');
		
	$res2 = CIBlockElement::GetByID($_REQUEST["ELEMENT_ID"]);
	if($ar_res2 = $res2->GetNext()){
  		if($ar_res2["IBLOCK_TYPE_ID"]=="config"){
			
			//Берем старую цену
			$ar_res_otp = CPrice::GetBasePrice($_REQUEST["ELEMENT_ID"]);
			
			
			
			$arFilter = Array(
				"IBLOCK_ID"=>81, 
				array(
					"LOGIC" => "OR",
   	   		 		"PROPERTY_processor" => $_REQUEST["ELEMENT_ID"],
   	   		 		"PROPERTY_motherboard" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_ram" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_hdd" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_video" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_korpus" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_korpus2" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_bp" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_sound" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_cd" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_cardrider" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_monitor" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_keyboard" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_mouse" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_kolonki" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_office" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_printer" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_antivirus" => $_REQUEST["ELEMENT_ID"],   
   	    			"PROPERTY_os" => $_REQUEST["ELEMENT_ID"],
   	    			"PROPERTY_ssd" => $_REQUEST["ELEMENT_ID"]	
				)
			);
			
			$res = CIBlockElement::GetList(Array(), $arFilter, false,false, array("ID"));
			$i=0;
			$EXIT = array();
			while($arFields2 = $res->GetNext()){
				$i++;
				
				
				
				/*$PRICE = CPrice::GetBasePrice($arFields2["ID"]);
				
				
				$NEW_PRICE = $PRICE["PRICE"] - ($ar_res_otp["PRICE"]  - $_REQUEST["NEW_PRICE"]);
				
				$EXIT[]=array("ID"=>$arFields2["ID"], "NEW_PRICE"=>$NEW_PRICE, "OLD_PRICE"=>$PRICE["PRICE"], "NEW_PRICE_TOVAR"=>$_REQUEST["NEW_PRICE"], "OLD_PRICE_TOVAR"=>$ar_res_otp["PRICE"]);
				
				
				CPrice::SetBasePrice($arFields2["ID"], $NEW_PRICE, "RUB");
			
				generate_item_feed($arFields2["ID"]);
				*/
				
				
				
				
				$client->doBackground('change_price', json_encode(array("ID"=>$arFields2["ID"], "ITEM_OLD_PRICE"=>$ar_res_otp["PRICE"], "ITEM_NEW_PRICE"=>$_REQUEST["NEW_PRICE"],"NAME"=>$ar_res2["NAME"])));	

				
			}	
			
		}
	}
		
	if(CPrice::SetBasePrice($_REQUEST["ELEMENT_ID"], $_REQUEST["NEW_PRICE"], "RUB")) echo "ok";

}

function calc(){
	//var_dump($_REQUEST);
	CModule::IncludeModule("iblock");
	CModule::IncludeModule("sale");
	CModule::IncludeModule("catalog");
	
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
	
	$EXIT=array();
	$SUMMA = 0;
	foreach($CONFIGURATOR as $iblock){
		$EXIT[$iblock["CODE"]]='<option value="">-</option>';
		
		$arFilter = Array("IBLOCK_ID"=>$iblock["ID"], "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
		
		
		
		
		if($iblock["CODE"]=="korpus"){
			$db_props = CIBlockElement::GetProperty(72, $_REQUEST["selects"]["video"], array("sort" => "asc"), Array("CODE"=>"POWER"));
			if($ar_props = $db_props->Fetch()){
				if($ar_props["VALUE"]!=""){
					
					$arFilter[">=PROPERTY_POWER"]=$ar_props["VALUE"];
				}
			}
		}
		
		if($iblock["CODE"]=="video"){
			$db_props = CIBlockElement::GetProperty(73, $_REQUEST["selects"]["korpus"], array("sort" => "asc"), Array("CODE"=>"POWER"));
			if($ar_props = $db_props->Fetch()){
				if($ar_props["VALUE"]!=""){
					$arFilter["<=PROPERTY_POWER"]=$ar_props["VALUE"];
				}
			}
		}
		

		
		
		//переключили процессор, нужно подобрать мамки под него
		if($iblock["CODE"]=="motherboard"){
			if($_REQUEST["selects"]["processor"]>0){
				$db_props = CIBlockElement::GetProperty(68, $_REQUEST["selects"]["processor"], array("sort" => "asc"), Array("CODE"=>"socket"));
				if($ar_props = $db_props->Fetch()){
					//var_dump($ar_props);
					if($ar_props["VALUE_ENUM"]!=""){
						$arFilter["PROPERTY_socket_VALUE"]=$ar_props["VALUE_ENUM"];
					}
				}
			}elseif($_REQUEST["selects"]["ram"]>0){
				$db_props = CIBlockElement::GetProperty(70, $_REQUEST["selects"]["ram"], array("sort" => "asc"), Array("CODE"=>"socket"));
				while($ar_props = $db_props->Fetch()){
					if($ar_props["VALUE_ENUM"]!=""){
						$arFilter["PROPERTY_socket_VALUE"][]=$ar_props["VALUE_ENUM"];
					}
				}
			}
		}
		
		//переключили мамку, нужно подобрать проц под нее
		if($iblock["CODE"]=="processor"){
			if($_REQUEST["selects"]["motherboard"]>0){
				$db_props = CIBlockElement::GetProperty(69, $_REQUEST["selects"]["motherboard"], array("sort" => "asc"), Array("CODE"=>"socket"));
				if($ar_props = $db_props->Fetch()){
					//var_dump($ar_props);
					if($ar_props["VALUE_ENUM"]!=""){
						$arFilter["PROPERTY_socket_VALUE"]=$ar_props["VALUE_ENUM"];
					}
				}
			}elseif($_REQUEST["selects"]["ram"]>0){
				$db_props = CIBlockElement::GetProperty(70, $_REQUEST["selects"]["ram"], array("sort" => "asc"), Array("CODE"=>"socket"));
				while($ar_props = $db_props->Fetch()){
					if($ar_props["VALUE_ENUM"]!=""){
						$arFilter["PROPERTY_socket_VALUE"][]=$ar_props["VALUE_ENUM"];
					}
				}
			}
			//var_dump($arFilter);
		}
		
		
		
		if($iblock["CODE"]=="ram"){
			
			if($_REQUEST["selects"]["motherboard"]>0){
				$db_props = CIBlockElement::GetProperty(69, $_REQUEST["selects"]["motherboard"], array("sort" => "asc"), Array("CODE"=>"socket"));
				if($ar_props = $db_props->Fetch()){
					//var_dump($ar_props);
					if($ar_props["VALUE_ENUM"]!=""){
						$arFilter["PROPERTY_socket_VALUE"]=$ar_props["VALUE_ENUM"];
					}
				}
			}elseif($_REQUEST["selects"]["processor"]>0){
				$db_props = CIBlockElement::GetProperty(68, $_REQUEST["selects"]["processor"], array("sort" => "asc"), Array("CODE"=>"socket"));
				if($ar_props = $db_props->Fetch()){
					if($ar_props["VALUE_ENUM"]!=""){
						$arFilter["PROPERTY_socket_VALUE"]=$ar_props["VALUE_ENUM"];
					}
				}
			}
			
		}
		
		
		
		
		$res = CIBlockElement::GetList(Array("SORT"=>"ASC","NAME"=>"ASC"), $arFilter, false, false, false);
		while($arFields = $res->GetNext()){ 
			$EXIT[$iblock["CODE"]].='<option value="'.$arFields["ID"].'" ';
			if($arFields["ID"]==$_REQUEST["selects"][$iblock["CODE"]]) {
				$EXIT[$iblock["CODE"]].=' selected="selected"'; 
				$PRICE = CPrice::GetBasePrice($arFields["ID"]);
				
				//$PRICE["PRICE"] = CCurrencyRates::ConvertCurrency($PRICE["PRICE"], "USD", "RUB");
				
				//var_dump($PRICE);
				$SUMMA+=$PRICE["PRICE"];
				
				if($iblock["CODE"]=="korpus" && $arFields["PREVIEW_PICTURE"]!=""){
					//$SRC=CFile::GetPath($arFields["PREVIEW_PICTURE"]);
					$SRC=CFile::ResizeImageGet($arFields["PREVIEW_PICTURE"], array('width'=>100, 'height'=>100), BX_RESIZE_IMAGE_PROPORTIONAL, true); 
					$EXIT["korpus_img"]='<img src="'.$SRC["src"].'"/>';
				}else{
					$EXIT["korpus_img"]="";
				}
			}
			$EXIT[$iblock["CODE"]].='>'.$arFields["NAME"].'</option>';
		}	
	
	}
	$EXIT["SUMMA"]=CurrencyFormat(CCurrencyRates::ConvertCurrency($SUMMA, "RUB", "RUB"), "RUB");
	echo json_encode($EXIT);
}



function add_new_comp(){
	global $USER;
	CModule::IncludeModule("iblock");
	CModule::IncludeModule("sale");
	CModule::IncludeModule("catalog");
	
	//Сначала смотрим, нет ли такого компа
	$arFilter = Array(
		"IBLOCK_ID"=>81, 
		"ACTIVE"=>"Y",
		"PROPERTY_processor"=>$_REQUEST["processor"],   // процессор	
		"PROPERTY_motherboard"=>$_REQUEST["motherboard"],
		"PROPERTY_ram"=>$_REQUEST["ram"],
		"PROPERTY_HDD"=> $_REQUEST["HDD"],	
		"PROPERTY_video"=> $_REQUEST["video"],
		"PROPERTY_korpus"=> $_REQUEST["korpus"],
		"PROPERTY_sound"=> $_REQUEST["sound"],
		"PROPERTY_cd"=> $_REQUEST["cd"],
		"PROPERTY_cardrider"=> $_REQUEST["cardrider"],
		"PROPERTY_monitor"=> $_REQUEST["monitor"],
		"PROPERTY_keyboard"=> $_REQUEST["keyboard"],
		"PROPERTY_mouse"=> $_REQUEST["mouse"],	
		"PROPERTY_os"=> $_REQUEST["os"],
		"PROPERTY_antivirus"=> $_REQUEST["antivirus"],
		"PROPERTY_printer"=> $_REQUEST["printer"],
		"PROPERTY_office"=> $_REQUEST["office"],
		"PROPERTY_kolonki"=> $_REQUEST["kolonki"],
		"PROPERTY_guaranty"=> $_REQUEST["guaranty"],
		"PROPERTY_cviaz"=> $_REQUEST["cviaz"]
	);
	$res = CIBlockElement::GetList(Array("NAME"=>"ASC"), $arFilter, false, false, false);
	if($arFields = $res->GetNext()){ 
		echo "еrror: Такой компьютер уже существует! ID".$arFields["ID"];
		die();
	}			
	
	
	
	
	$res = CIBlock::GetList(
		Array(), 
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
	
	
	$SUMMA = 0;
	foreach($CONFIGURATOR as $iblock){
		$arFilter = Array("IBLOCK_ID"=>$iblock["ID"], "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
		$res = CIBlockElement::GetList(Array("NAME"=>"ASC"), $arFilter, false, false, false);
		while($arFields = $res->GetNext()){ 
			if($arFields["ID"]==$_REQUEST[$iblock["CODE"]]) {
				$PRICE = CPrice::GetBasePrice($arFields["ID"]);
				//var_dump($PRICE);
				$SUMMA+=$PRICE["PRICE"];
			}
		}	
	
	}
	
	
	$el = new CIBlockElement;

	$PROP = array();
	$PROP[1458] =$_REQUEST["processor"];   // процессор	
	$PROP[1459] = $_REQUEST["motherboard"];
	$PROP[1460] = $_REQUEST["ram"];
	$PROP[1461] = $_REQUEST["HDD"];
	$PROP[1462] = $_REQUEST["video"];
	$PROP[1463] = $_REQUEST["korpus"];
	$PROP[1464] = $_REQUEST["sound"];
	$PROP[1465] = $_REQUEST["cd"];
	$PROP[1466] = $_REQUEST["cardrider"];
	$PROP[1467] = $_REQUEST["monitor"];
	$PROP[1468] = $_REQUEST["keyboard"];
	$PROP[1469] = $_REQUEST["mouse"];
	
	$PROP[1473] = $_REQUEST["os"];
	$PROP[1474] = $_REQUEST["antivirus"];
	$PROP[1475] = $_REQUEST["printer"];
	$PROP[1476] = $_REQUEST["office"];
	$PROP[1477] = $_REQUEST["kolonki"];
	$PROP[1545] = $_REQUEST["guaranty"];
	
	
	$PROP[2304] = $_REQUEST["cviaz"];
	
	//возьмем картинки корпуса и прикрепим к компу
	if($_REQUEST["korpus"]!=""){
		$res = CIBlockElement::GetByID($_REQUEST["korpus"]);
		if($ar_res = $res->GetNext()){
  			$KORPUS_PREVIEW=$ar_res["PREVIEW_PICTURE"];
  			$KORPUS_DETAIL=$ar_res["DETAIL_PICTURE"];
  		}
	
	}
	
	//Формируем анонс 
	//Процессор, оперативная память, жесткий диск, видеокарта, оптический привод, операционная система, корпус.
	$PREVIEW_TEXT="";
	$res = CIBlockElement::GetByID($_REQUEST["processor"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  		
  	}
  	
  	$res = CIBlockElement::GetByID($_REQUEST["ram"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  	}
	
	$res = CIBlockElement::GetByID($_REQUEST["HDD"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  		
  	}
	
	$res = CIBlockElement::GetByID($_REQUEST["video"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  		
  	}
	
	
  	$res = CIBlockElement::GetByID($_REQUEST["cd"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}
  	
  	$res = CIBlockElement::GetByID($_REQUEST["os"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}

    $res = CIBlockElement::GetByID($_REQUEST["cardrider"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}

    $res = CIBlockElement::GetByID($_REQUEST["cviaz"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}

  	$res = CIBlockElement::GetByID($_REQUEST["korpus"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"];
  	}
	//////////////////////////////////
	
	$PROP[1538] = $_REQUEST["name"];
	
	$arLoadProductArray = Array(
  		"MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
  		"IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
		"IBLOCK_ID"      => 81,
		"PROPERTY_VALUES"=> $PROP,
		"NAME"           => $_REQUEST["name"]." (".$PREVIEW_TEXT.")",
  		"ACTIVE"         => "Y",            // активен
  		"DETAIL_PICTURE"=>CFile::MakeFileArray($KORPUS_DETAIL),
  		"PREVIEW_PICTURE" =>CFile::MakeFileArray($KORPUS_PREVIEW),
  		"PREVIEW_TEXT" =>$PREVIEW_TEXT
  	);

	if($PRODUCT_ID = $el->Add($arLoadProductArray)){
		$arFields = array("ID" => $PRODUCT_ID);
		CCatalogProduct::Add($arFields);
		CPrice::SetBasePrice($PRODUCT_ID, $SUMMA, "RUB");

		CCatalogProduct::Add(array("ID"=>$PRODUCT_ID));
		
		//
		CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "CML2_ARTICLE", $PRODUCT_ID);
		generate_item_feed($PRODUCT_ID);
  		echo "Комьютер добавлен! ID".$PRODUCT_ID;
	}else
  		echo "еrror: ".$el->LAST_ERROR;

}











function add_new_comp_user(){
	global $USER;
	CModule::IncludeModule("iblock");
	CModule::IncludeModule("sale");
	CModule::IncludeModule("catalog");
	
	//Сначала смотрим, нет ли такого компа
	$arFilter = Array(
		"IBLOCK_ID"=>81, 
		"ACTIVE"=>"Y",
		"PROPERTY_processor"=>$_REQUEST["processor"],   // процессор	
		"PROPERTY_motherboard"=>$_REQUEST["motherboard"],
		"PROPERTY_ram"=>$_REQUEST["ram"],
		"PROPERTY_HDD"=> $_REQUEST["HDD"],	
		"PROPERTY_video"=> $_REQUEST["video"],
		"PROPERTY_korpus"=> $_REQUEST["korpus"],
		"PROPERTY_sound"=> $_REQUEST["sound"],
		"PROPERTY_cd"=> $_REQUEST["cd"],
		"PROPERTY_cardrider"=> $_REQUEST["cardrider"],
		"PROPERTY_monitor"=> $_REQUEST["monitor"],
		"PROPERTY_keyboard"=> $_REQUEST["keyboard"],
		"PROPERTY_mouse"=> $_REQUEST["mouse"],	
		"PROPERTY_os"=> $_REQUEST["os"],
		"PROPERTY_antivirus"=> $_REQUEST["antivirus"],
		"PROPERTY_printer"=> $_REQUEST["printer"],
		"PROPERTY_office"=> $_REQUEST["office"],
		"PROPERTY_kolonki"=> $_REQUEST["kolonki"],
		"PROPERTY_guaranty"=> $_REQUEST["guaranty"],
		"PROPERTY_cviaz"=> $_REQUEST["cviaz"]
	);
	$res = CIBlockElement::GetList(Array("NAME"=>"ASC"), $arFilter, false, false, false);
	if($arFields = $res->GetNext()){ 
		if(Add2BasketByProductID($arFields["ID"],1)!='false'){
			echo "added";
			die();
		}
	}			
	
	
	
	
	$res = CIBlock::GetList(
		Array(), 
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
	
	
	$SUMMA = 0;
	foreach($CONFIGURATOR as $iblock){
		$arFilter = Array("IBLOCK_ID"=>$iblock["ID"], "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
		$res = CIBlockElement::GetList(Array("NAME"=>"ASC"), $arFilter, false, false, false);
		while($arFields = $res->GetNext()){ 
			if($arFields["ID"]==$_REQUEST[$iblock["CODE"]]) {
				$PRICE = CPrice::GetBasePrice($arFields["ID"]);
				//var_dump($PRICE);
				$SUMMA+=$PRICE["PRICE"];
			}
		}	
	
	}
	
	
	$el = new CIBlockElement;

	$PROP = array();
	$PROP[1458] =$_REQUEST["processor"];   // процессор	
	$PROP[1459] = $_REQUEST["motherboard"];
	$PROP[1460] = $_REQUEST["ram"];
	$PROP[1461] = $_REQUEST["HDD"];
	$PROP[1462] = $_REQUEST["video"];
	$PROP[1463] = $_REQUEST["korpus"];
	$PROP[1464] = $_REQUEST["sound"];
	$PROP[1465] = $_REQUEST["cd"];
	$PROP[1466] = $_REQUEST["cardrider"];
	$PROP[1467] = $_REQUEST["monitor"];
	$PROP[1468] = $_REQUEST["keyboard"];
	$PROP[1469] = $_REQUEST["mouse"];
	
	$PROP[1473] = $_REQUEST["os"];
	$PROP[1474] = $_REQUEST["antivirus"];
	$PROP[1475] = $_REQUEST["printer"];
	$PROP[1476] = $_REQUEST["office"];
	$PROP[1477] = $_REQUEST["kolonki"];
	$PROP[1545] = $_REQUEST["guaranty"];
	
	$PROP[2304] = $_REQUEST["cviaz"];
	
	//возьмем картинки корпуса и прикрепим к компу
	if($_REQUEST["korpus"]!=""){
		$res = CIBlockElement::GetByID($_REQUEST["korpus"]);
		if($ar_res = $res->GetNext()){
  			$KORPUS_PREVIEW=$ar_res["PREVIEW_PICTURE"];
  			$KORPUS_DETAIL=$ar_res["DETAIL_PICTURE"];
  		}
	
	}
	
	//Формируем анонс 
	//Процессор, оперативная память, жесткий диск, видеокарта, оптический привод, операционная система, корпус.
	$PREVIEW_TEXT="";
	$res = CIBlockElement::GetByID($_REQUEST["processor"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  		
  	}
  	
  	$res = CIBlockElement::GetByID($_REQUEST["motherboard"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  	}
  	
  	
  	
  	$res = CIBlockElement::GetByID($_REQUEST["ram"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  	}
	
	$res = CIBlockElement::GetByID($_REQUEST["HDD"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  		
  	}
	
	$res = CIBlockElement::GetByID($_REQUEST["video"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].", ";
  		
  	}
	
	
  	$res = CIBlockElement::GetByID($_REQUEST["cd"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}
  	
  	$res = CIBlockElement::GetByID($_REQUEST["os"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}
  	
    $res = CIBlockElement::GetByID($_REQUEST["cardrider"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}

    $res = CIBlockElement::GetByID($_REQUEST["cviaz"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"].', ';
  	}

  	$res = CIBlockElement::GetByID($_REQUEST["korpus"]);
	if($ar_res = $res->GetNext()){
  		$PREVIEW_TEXT.=$ar_res["NAME"];
  	}
	//////////////////////////////////
	
	
	//////////////////////////////////
	$C = CIBlockSection::GetSectionElementsCount(763, array("CNT_ACTIVE"=>"N"));
	$NAME="Компьютер RiWer ".$C;
	
	/////////////////////////////////
	
	
	$PROP[1538] = $NAME;
	
	$arLoadProductArray = Array(
  		"MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
  		"IBLOCK_SECTION_ID" => 763,          // элемент лежит в корне раздела
		"IBLOCK_ID"      => 81,
		"PROPERTY_VALUES"=> $PROP,
		"NAME"           => $NAME." (".$PREVIEW_TEXT.")",
  		"ACTIVE"         => "Y",            // активен
  		"DETAIL_PICTURE"=>CFile::MakeFileArray($KORPUS_DETAIL),
  		"PREVIEW_PICTURE" =>CFile::MakeFileArray($KORPUS_PREVIEW),
  		"PREVIEW_TEXT" =>$PREVIEW_TEXT
  	);

	if($PRODUCT_ID = $el->Add($arLoadProductArray)){
		$arFields = array("ID" => $PRODUCT_ID);
		CCatalogProduct::Add($arFields);
		CPrice::SetBasePrice($PRODUCT_ID, $SUMMA, "RUB");
		
		
		
		
		CCatalogProduct::Add(array("ID"=>$PRODUCT_ID));
		
  		if(Add2BasketByProductID($PRODUCT_ID,1)!='false'){
			echo "added";
		}
		
		
		
		CIBlockElement::SetPropertyValueCode($PRODUCT_ID, "CML2_ARTICLE", $PRODUCT_ID);
		
		generate_item_feed($PRODUCT_ID);
	}else
  		echo "еrror: ".$el->LAST_ERROR;

}








if($_REQUEST["act"]=="edite_price") edite_price();
if($_REQUEST["act"]=="calc") calc();
if($_REQUEST["act"]=="add_new_comp") add_new_comp();
if($_REQUEST["act"]=="add_new_comp_user") add_new_comp_user();

if($_REQUEST["act"]=="change_cases") change_cases();

?>