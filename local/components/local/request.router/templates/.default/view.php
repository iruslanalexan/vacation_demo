<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION;

/** @var array $arResult */
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_VACATION_REQUEST") . $arResult['VARIABLES']['REQUEST_ID']);

/** @var array $arResult */
$APPLICATION->IncludeComponent(
    "local:request.view",
    ".default",
    [
        'APPLICATION_ID' => $arResult['VARIABLES']['REQUEST_ID'],
        'SEF_FOLDER' => $arResult['SEF_FOLDER']
    ]
);
