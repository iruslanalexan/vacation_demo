<?php

use Bitrix\Main\Config\Option;


$aMenuLinks = array(
    array(
        GetMessage("LOCAL_ABSENCE_MOI_OTPUSKA"),
        "./",
        array(),
        array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
    ),
    array(
        GetMessage("LOCAL_ABSENCE_MOI_DELA"),
        "todo/",
        array(),
        array("counter_num" => LOCAL_ABSENCE_TODO_COUNTER),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
    ),
    array(
        GetMessage("LOCAL_ABSENCE_ZAAVKI"),
        "requests/",
        array(),
        array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
    ),
    array(
        GetMessage("LOCAL_ABSENCE_GRAFIK_OTSUTSTVIY"),
        "chart/",
        array(),
        array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
    ),
    array(
        GetMessage("LOCAL_ABSENCE_OTSUTSTVIA"),
        "absences/",
        array(),
        array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.use') && Local\\Helper::isUserHr()"
    ),
    /*
    Array(
        "���������",
        "settings/",
        Array(),
        Array(),
        ""
    )
    */
);

if (Option::get('local.absence', 'APPLICATION_ENABLED') === 'Y') {
    $aMenuLinks[] = array(
        GetMessage("LOCAL_ABSENCE_ZAAVLENIA"),
        "applications/",
        array(),
        array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.use')"
    );
}

if (Options::getInstance()->isBitrixAbsenceModuleIntegrationEnabled()) {
    $aMenuLinks[] = array(
        GetMessage("LOCAL_ABSENCE_COMPANY_ABSENCE"),
        "company_absences/",
        array(),
        array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.viewIntranetAbsence')"
    );
}

$aMenuLinks[] = [
    GetMessage("LOCAL_ABSENCE_REPORTS"),
    "reports/",
    Array(),
    Array(),
    "\$GLOBALS['USER']->CanDoOperation('local.absence.reports')"
];

include(__DIR__ . '/menu_event.php');
