<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
    "local:request.list",
    ".default",
    []
);
