<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_OTSUTSTVIA"));

use Bitrix\Main\UI\Extension;
use \Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('ui');
Loader::includeModule('local.absence');
Extension::load([
    "ui.forms",
    'ui.buttons',
    'ui.icons',
    'ui.notification',
    'ui.accessrights',
    'ui.dialogs.messagebox'
]);

?>

<?php
if (!$USER->CanDoOperation('local.absence.use')):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    $APPLICATION->IncludeComponent(
            "absence.local.ru:absence.list",
            ".default",
            []
    );
endif;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
