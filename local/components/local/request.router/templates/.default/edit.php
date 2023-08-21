<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION;

/** @var array $arResult */
$APPLICATION->IncludeComponent(
    "local:request.edit",
    ".default",
    [
        'APPLICATION_ID' => $arResult['VARIABLES']['APPLICATION_ID'],
        'SEF_FOLDER' => $arResult['SEF_FOLDER']
    ]
);
