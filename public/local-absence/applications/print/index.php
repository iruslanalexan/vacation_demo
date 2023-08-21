<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Rest\RestException;
use Local\Helper;
use Local\Validation;
use Local\UserData as UserDataController;
use Local\Vacation as VacationController;
use Local\Vacation as VacationEntity;
use Local\Organization;
use Bitrix\Main\Config\Option;
use Local\Options;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
CModule::IncludeModule("local.absence");

global $APPLICATION;

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

if(CModule::IncludeModule("local.absence")) {

    if(isset($_GET['arr'])&&is_array($_GET['arr'])) {
        $arr = [];
        foreach ($_GET['arr'] as $id) {
            $id = (int)$id;
            if( $id>0 && !in_array($id, $arr) ) {
                $arr[] = $id;
            }
        }

        if (count($arr)) {
            $APPLICATION->RestartBuffer();

            foreach ($arr as $ID) {
                $pdf->AddPage();
                try {
                    $vacation = getVacation($ID);

                    $html = getHtml($vacation);
                    $sign = getSign(!$vacation['hideApplicationDate']);
                    $footer = getFooter();

//              output the HTML content
                    $pdf->SetFont('freeserif', 'italic', 12);
                    $pdf->writeHTML($html, true, 0, true, 0);
                    $pdf->SetFont('freeserif', 'italic', 9);
                    $pdf->writeHTML($sign, true, 0, true, 0);
                    $pdf->SetFont('freeserif', 'italic', 14);
                    $pdf->writeHTML($footer, true, 0, true, 0);
                } catch(Exception $exception) {
                    $pdf->SetFont('freeserif', 'italic', 12);
                    $html = getErrorHtml($exception->getMessage());
                    $pdf->writeHTML($html, true, 0, true, 0);
                }
//              reset pointer to the last page
                $pdf->lastPage();
            }
//          Close and output PDF document
            $pdf->Output($vacation["fileName"].'.pdf', 'I');
        }
        die();
    }

    if((int)$_GET['ID']>0) {
        $APPLICATION->RestartBuffer();

        $pdf->AddPage();
        try {
            $vacation = getVacation($_GET['ID']);

            $html = getHtml($vacation);
            $sign = getSign(!$vacation['hideApplicationDate']);
            $footer = getFooter();

    //      output the HTML content
            $pdf->SetFont('freeserif', 'italic', 12);
            $pdf->writeHTML($html, true, 0, true, 0);
            $pdf->SetFont('freeserif', 'italic', 9);
            $pdf->writeHTML($sign, true, 0, true, 0);
            $pdf->SetFont('freeserif', 'italic', 14);
            $pdf->writeHTML($footer, true, 0, true, 0);
        } catch(Exception $exception) {
            $pdf->SetFont('freeserif', 'italic', 12);
            $html = getErrorHtml($exception->getMessage());
            $pdf->writeHTML($html, true, 0, true, 0);
        }

//      reset pointer to the last page
        $pdf->lastPage();

//      Close and output PDF document
        $pdf->Output($vacation["fileName"].'.pdf', 'I');
        die();
    }
} else {
    $APPLICATION->RestartBuffer();
    die();
}

die();

/**
 * @return string
 */
function getStyles(): string
{
    return "<style>
        body {
            font-size: 0.5cm;
            font-style: italic;
        }
        .wrapper {
            width:21cm;
            height: 29cm;
            padding: 1cm;
        }
        .head {
            display: flex;
            flex-direction: column;
        }
        .from-employee {
            text-align: right;
            font-weight: bold;
            margin-bottom: 3cm;
        }
        .message {
            text-indent: 1cm;
            line-height: 1cm;
        }
        .message-head {
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 0.5cm;
        }
        .signature {
            display: flex;
            width: 100%;
            flex-direction: column;
        }
        .signature .row {
            display: flex;
            justify-content: space-between;
            width: auto;
            padding: 2cm 2cm 2cm 0;
    
        }
        .signature .column {
            border-top:0.05cm solid black;
            text-transform: lowercase;
            font-size: 0.3cm;
            width: 5cm;
            text-align: center;
            padding-top: 0.2cm;;
        }
        .negotiation .row {
            padding-bottom: 1cm;
            border-bottom: 0.05cm solid black;
            width: 8cm;
        }
        .footer {
            margin-top: 20cm;
        }
    </style>";
}

/**
 * @param $error
 * @return string
 */
function getErrorHtml($error): string
{
    return getStyles() . "
        <div class=\"body\">
            <div class=\"message-head\">$error</div>
        </div>
        ";
}

/**
 * @param $vacation
 * @return string
 */
function getHtml($vacation)
{
    $html = getStyles() . "
    <div class=\"wrapper\">
        <div class=\"head\" style=\"\">
            <div class=\"from-employee\">
                <div class=\"row\">{$vacation['organizationHeadPositionTo']}</div>
                <div class=\"row\">{$vacation['organizationName']}</div>
                <div class=\"row\" style=\"margin-bottom: 0.5cm\">{$vacation['organizationHeadFioTo']}</div>
                <div class=\"row\">" . GetMessage("LOCAL_ABSENCE_OT_SOTRUDNIKA") . ":</div>
                <div class=\"row\">{$vacation['employee']}</div>";

    if (!$vacation["hideProfession"]) {
        $html .= "<div class=\"row\">{$vacation['profession']}</div>";
    }

    $html.="</div>
        </div>
        <div class=\"body\">
            <div class=\"message-head\">" . GetMessage("LOCAL_ABSENCE_APPLICATION") . "</div>
            <div class=\"message\">"
        . GetMessage("LOCAL_ABSENCE_APPLICATION_BODY",
            [
                "#TEXT#" => $vacation['text'],
                "#FROM#" => $vacation['from'],
                "#TO#" => $vacation['to'],
                "#ON#" => $vacation['countName'],
            ]
        )
        . "
            </div>
        </div>
        ";
    return $html;
}

/**
 * @return string
 */
function getSign($showAppDate) {
    $appDate = $showAppDate ? (new \Bitrix\Main\Type\DateTime())->format('d.m.Y') : '';
    $sign = "<div class=\"footer\" >
            <div style=\"height: 1cm;\"></div>
            <table style=\"width: 100%;\">
                <tr>
                    <td style=\"width:30%;text-align: center;\">" . $appDate . "</td>
                    <td style=\"width:30%;\">&nbsp;</td>
                    <td style=\"width:20%;text-align: center\">&nbsp;</td>
                    <td style=\"width:20%;\">&nbsp;</td>
                </tr>
                <tr>
                    <td style=\"width:30%;border-top:1px solid black;text-align: center;\">".GetMessage("LOCAL_ABSENCE_DATA")."</td>
                    <td style=\"width:20%;\"></td>

                </tr>
            </table>
        </div>
    ";
    return $sign;
}

/**
 * @return string
 */
function getFooter()
{
    $footer = "
            <div>
                <br/><br/><br/>
                <table>
                    <tr>
                        <td style=\"width: 30%;\">" . GetMessage("LOCAL_ABSENCE_SOGLASOVANO") . "</td>
                        <td style=\"width: 70%\"></td>
                    </tr>
                    <tr>
                        <td style=\"margin-bottom: 1cm;border-bottom: 1px solid black; width: 30%;\"></td>
                        <td style=\"width: 70%\"></td>
                    </tr>
                </table>
            </div>
        </div>";
    return $footer;
}

/**
 * @param $ID
 * @return array|void
 * @throws ArgumentException
 * @throws ObjectPropertyException
 * @throws SystemException
 * @throws RestException
 * @throws Exception
 */
function getVacation($ID) {
    $vacationEntity = VacationEntity::load($ID);
    $vacation = VacationController::init(['vacationId' => $ID, 'userId' => $vacationEntity['employee']]);
    $absence = is_array($vacation['vacation']['data']['absences'][(int)$ID])
        ? array_shift($vacation['vacation']['data']['absences'][(int)$ID]) : [];
    $userData = UserDataController::getUsersData($vacation['employee']['id'] ?? null);
    $user = UserTable::getList(array(
        'filter' => array('=ID' => $userData["id"])
    ))->fetch();

    $obOrganization = new Organization();
    $arOrganizations = $obOrganization->getOrganizations([$userData["id"]]);

    $organization = [];
    if (!empty($arOrganizations)
        && array_key_exists($userData["id"], $arOrganizations)
        && !empty($arOrganizations[$userData["id"]])
    ) {
        $organization = $arOrganizations[$userData["id"]];
    } else {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        throw new Exception(GetMessage("LOCAL_ABSENCE_MODULE_SETTINGS_ERROR"));
    }

    $vacation = [
        'hideProfession' => $organization['hideProfession'],
        'hideApplicationDate' => $organization['hideApplicationDate'],
        'organizationName' => $organization['name'],
        'organizationHeadPositionTo' => $organization['headPositionTo'],
        'organizationHeadFioTo' => $organization['headFioTo'],
        'from' => $absence['from'],
        'to' => $absence['to'],
        'count' => $absence['vacationDays'],
        'countName' => Validation::declOfNum($absence['vacationDays'], array(GetMessage("LOCAL_ABSENCE_KALENDARNYY_DENQ"), GetMessage("LOCAL_ABSENCE_KALENDARNYH_DNA"), ' '.GetMessage("LOCAL_ABSENCE_KALENDARNYH_DNEY"))),
        'employee' => $userData['fullUserName'],
        'department' =>  $userData['departmentName'],
        'profession' => $user["WORK_POSITION"],
        'fileName' => "Vacation_request_" . $vacation["vacation"]["data"]["id"] . "_" . str_replace(".", "_",mb_substr($vacation["vacation"]["data"]["dateCreate"], 0, 10)),
        'text' => $absence["type"]["xmlId"] === 'unpaidLeaveChart' ?
            GetMessage("LOCAL_ABSENCE_PROSU_PREDOSTAVITQ_M") :
            GetMessage("LOCAL_ABSENCE_PROSU_PREDOSTAVITQ_M1"),
    ];
    return $vacation;
}
