<?php
use Local\Options;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_COMPANY_ABSENCE"));

$APPLICATION->IncludeComponent(
    "bitrix:intranet.absence.calendar",
    ".default",
    Array(
        "FILTER_NAME"	=>	"absence",
        "FILTER_SECTION_CURONLY"	=>	"N",
        "NAME_TEMPLATE" => Options::getInstance()->getUserNameFormat(),
    )
);
