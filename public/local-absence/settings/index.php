<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_NASTROYKI"));

?><?
$isAdmin = $USER->CanDoOperation('local.absence.admin', $USER->GetID()) || $USER->IsAdmin();
if (!$isAdmin):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    $APPLICATION->IncludeComponent(
        "",
        ".default",
        array()
    );
endif;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
