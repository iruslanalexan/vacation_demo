<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Grid;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Rest\RestException;


class ApplicationListComponent extends CBitrixComponent
{

}
