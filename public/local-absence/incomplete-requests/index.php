<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_INCOMPLETE_REQUEST"));

try {
    Loader::includeModule('local.absence');
    $APPLICATION->IncludeComponent(
        "absence.local.ru:dialog.incompleteRequests",
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
} catch (LoaderException $e) {
    ShowError($e->getMessage());
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
