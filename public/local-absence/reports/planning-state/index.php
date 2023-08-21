<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_PLANNING_STATES"));

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Loader;

if ((!CurrentUser::get()->CanDoOperation('local.absence.reports'))):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    $APPLICATION->IncludeComponent(
        'absence.local.ru:reports.planning-states',
        ".default",
        []
    );
endif;

try {
    // TODO 2.0.x: ����� ��� ��� ����������?
    Loader::includeModule('ui');
    Loader::includeModule('local.absence');
    Extension::load(
        [
            "ui.forms",
            'ui.buttons',
            'ui.icons',
            'ui.notification',
            'ui.accessrights',
            'ui.dialogs.messagebox'
        ]
    );
} catch (LoaderException $e) {
    ShowError(GetMessage($e->getMessage()));
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
