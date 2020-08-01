<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Конструктор");
?>

<?$APPLICATION->IncludeFile(
						"/tpl/include_areas/configurator_admin.php",
						Array(),
						Array("MODE"=>"php")
					);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>