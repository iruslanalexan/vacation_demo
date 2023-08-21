<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_ZAAVLENIA"));
Bitrix\Main\Loader::includeModule('local.absence');
?>

<?
if (!$USER->CanDoOperation('local.absence.use')):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    $APPLICATION->IncludeComponent(
        "absence.local.ru:application.router",
        ".default",
        array(
            'SEF_MODE' => 'Y',
            'SEF_FOLDER' => LOCAL_ABSENCE_PUBLIC_URL_APPLICATIONS,
            'SEF_URL_TEMPLATES' => array(
                'details' => '#VACATION_ID#/',
                'edit' => '#VACATION_ID#/edit/',
            )
        ),
        false
    );
endif;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
