<?

use Bitrix\Main\Event;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("LOCAL_ABSENCE_MOI_OTPUSKA"));
?>
<?php
if (!$USER->CanDoOperation('local.absence.use')) {
    ShowError(GetMessage("LOCAL_ABSENCE_DOSTUP_ZAPRESEN"));
} else {
    $request = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
    $request = str_replace(rtrim(LOCAL_ABSENCE_PUBLIC_URL, '/'), '', $request);
    if ($request === '') {
        $APPLICATION->IncludeComponent(
            "absence.local.ru:calendar",
            ".default",
            array(),
            false
        );
    } else {
        $request = trim($request, '/');
        $event = new Event("local.absence", "OnProcessRequest");
        $event->send();

        $processed = false;
        foreach ($event->getResults() as $eventResult) {
            // ���� ���������� ������ ������, ������ �� ������
            if ($eventResult->getType() === \Bitrix\Main\EventResult::ERROR) {
                continue;
            }

            $param = $eventResult->getParameters();
            if (is_callable($param[$request])) {
                $result = $param[$request]();
                if ($result !== false) {
                    $processed = true;
                };
            }
        }
        if (!$processed) {
            Bitrix\Iblock\Component\Tools::process404(
                GetMessage("LOCAL_ABSENCE_STRANICA_NE_NAYDENA"), //���������
                true, // ����� �� ���������� 404-� ���������
                true, // ������������� �� ������
                true, // ���������� �� 404-� ��������
                false // ������ �� �������� �� ����������� 404-�
            );
        }
    }
}
?>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
