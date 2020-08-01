<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Прайс");
?>

<?$APPLICATION->IncludeFile(
						"/tpl/include_areas/configurator_price.php",
						Array(),
						Array("MODE"=>"php")
					);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>