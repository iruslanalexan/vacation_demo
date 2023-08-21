<?php

use Bitrix\Main\Engine\CurrentUser;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

if ((!CurrentUser::get()->canDoOperation('local.absence.reports'))):
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
else:
    LocalRedirect('planning-state/');
endif;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
