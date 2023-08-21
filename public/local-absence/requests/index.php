<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_ZAAVKI"));

use Bitrix\Main\UI\Extension;
use \Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

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

if (!$USER->CanDoOperation('local.absence.use')):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    $APPLICATION->IncludeComponent(
        'absence.local.ru:request.router',
        '',
        [
            'SEF_MODE' => 'Y',
            'SEF_FOLDER' => LOCAL_ABSENCE_PUBLIC_URL_REQUESTS,
            'SEF_URL_TEMPLATES' => [
                'details' => '#APPLICATION_ID#/',
                'edit' => '#APPLICATION_ID#/edit/',
            ]
        ],
        false
    );
endif;
?>

<script>
    function onClick(event) {
        BX.SidePanel.Instance.open("<?=LOCAL_ABSENCE_PUBLIC_URL_REQUESTS?>0/edit/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER", {
            label: {
                text: "<?=GetMessageJS("LOCAL_ABSENCE_ZAAVKA_NA_OTPUSK")?>",
            }
        })
    }
</script>
<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
