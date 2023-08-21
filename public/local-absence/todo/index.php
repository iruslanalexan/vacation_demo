<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_MOI_DELA"));
?>
<?
if (!$USER->CanDoOperation('local.absence.use')):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    $APPLICATION->IncludeComponent(
        "absence.local.ru:todo",
        ".default",
        array()
    );
endif;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
