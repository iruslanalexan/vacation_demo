<?php
$aMenuLinks = Array(
    Array(
        GetMessage("LOCAL_ABSENCE_PLANNING_STATES"),
        "planning-state/",
        Array(),
        Array(),
        "\$GLOBALS['USER']->CanDoOperation('local.absence.reports')"
    ),
    Array(
        GetMessage("LOCAL_BACK_TO_MAIN"),
        "../",
        Array(),
        Array(),
        ""
    ),
);
