<?php

use Bitrix\Main\Event;

/** @var array $aMenuLinks */

$event = new Event("local.absence", "OnBuildLocalAbsenceMenu");
$event->send();

foreach ($event->getResults() as $eventResult) {
    // ���� ���������� ������ ������, ������ �� ������
    if($eventResult->getType() === \Bitrix\Main\EventResult::ERROR) {
        continue;
    }

    $arMenuItem = $eventResult->getParameters();
    if(is_array($arMenuItem)
    ) {
        if(array_key_exists('name', $arMenuItem) && array_key_exists('path', $arMenuItem)) {
            $aMenuLinks[] = [
                $arMenuItem['name'],
                "{$arMenuItem['path']}/",
                Array(),
                Array(),
                array_key_exists('rights', $arMenuItem) ?
                    $arMenuItem['rights'] :
                    "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
            ];
        } else {
            foreach ($arMenuItem as $item) {
                if(array_key_exists('name', $item) && array_key_exists('path', $item)) {
                    $aMenuLinks[] = [
                        $arMenuItem['name'],
                        $arMenuItem['path'],
                        Array(),
                        Array(),
                        array_key_exists('rights', $arMenuItem) ?
                            $arMenuItem['rights'] :
                            "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
                    ];
                }
            }
        }


    }
}
