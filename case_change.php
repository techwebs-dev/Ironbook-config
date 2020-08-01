<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Замена корпуса");
?>

<?$APPLICATION->IncludeFile(
						"/tpl/include_areas/configurator_case_change.php",
						Array(),
						Array("MODE"=>"php")
					);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>