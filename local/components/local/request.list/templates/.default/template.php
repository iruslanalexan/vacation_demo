<? use Local\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
global $APPLICATION, $USER;
require_once(LOCAL_ABSENCE_MODULE_DIR."/js_lang.php");
$this->SetViewTarget("inside_pagetitle", 11); ?>
<div class="pagetitle-container pagetitle-flexible-space">
    <button type="button" class="ui-btn ui-btn-primary" id="new" onclick="CreateApplication()"><?=GetMessage("LOCAL_ABSENCE_CREATE_REQUEST")?></button>
</div>
<div class="pagetitle-container pagetitle-flexible-space">
    <?
    $APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
        'FILTER_ID' => $arResult['GRID_ID'],
        'GRID_ID' => $arResult['GRID_ID'],
        'FILTER' => $arResult['FILTER'],
        'ENABLE_LIVE_SEARCH' => true,
        'ENABLE_LABEL' => true,
        'FILTER_PRESETS' => [
            'my' => [
                'name' => GetMessage("LOCAL_ABSENCE_MY"),
                //'default' => 'true', // ���� true - ������ �� ���������
                'fields' => [
                    'UF_EMPLOYEE_label' => $USER->GetFormattedName(),
                    'UF_EMPLOYEE_name' => $USER->GetFormattedName(),
                    'UF_EMPLOYEE' => $USER->GetID()
                ]
            ]
        ],
    ],
        $component,
        array("HIDE_ICONS" => true)
    );
    ?>
</div>

<?
$this->EndViewTarget("inside_pagetitle", 10);

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '.default',
    array(
        'AJAX_MODE'           => 'Y',
        //Strongly required
        "AJAX_OPTION_JUMP"    => "N",
        "AJAX_OPTION_STYLE"   => "N",
        "AJAX_OPTION_HISTORY" => "N",

        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['HEADERS'],
        'ROWS' => $arResult['ROWS'],
        'PAGINATION' => $arResult['PAGINATION'],
        'SORT' => $arResult['SORT'],
        'NAV_OBJECT' => $arResult['NAV'],
        'SHOW_TOTAL_COUNTER' => false
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);
?>
<script>
    function CreateApplication() {
        return new Promise(resolve => {
            (BX.rest || window.parent.BX.rest)
                .callMethod('local.application.hasRejectDraft')
                .then((result) => {
                    let data = result.data();
                    let width = window.screen.width-80;
                    let url = data ? "<?=LOCAL_ABSENCE_PUBLIC_URL?>incomplete-requests/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER" : "<?=LOCAL_ABSENCE_PUBLIC_URL_REQUESTS?>0/edit/?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER";
                    let text = data ? "<?=GetMessageJS("LOCAL_ABSENCE_INCOMPLETE_REQUESTS")?>" : "<?=GetMessageJS("LOCAL_ABSENCE_VACATION_REQUEST")?>";
                    const params = {
                        label: {
                            text: text,
                        },
                        cacheable: false
                    }
                    if(width > 0) {
                        params.width = width
                    }
                    BX.SidePanel.Instance.open(url, params)
                    BX.addCustomEvent(
                        "SidePanel.Slider:onMessage",
                        BX.delegate(function(event) {
                            if (event.getEventId() === "Application:reload")
                            {
                                reloadGrid()
                            }

                        }, this)
                    );
                    resolve(data)
                })
        });
    }

    function openApplication(event, path) {

        if(event) {
            event.preventDefault()
            event.stopPropagation()
        }
        let width = window.screen.width-80

        const params = {
            label: {
                text: "<?=GetMessageJS("LOCAL_ABSENCE_VACATION_REQUEST")?>",
            }
        }
        if(width > 0) {
            params.width = width
        }

        BX.SidePanel.Instance.open(
            path,
            params
        )
        BX.addCustomEvent(
            "SidePanel.Slider:onMessage",
            BX.delegate(function(event) {
                if (event.getEventId() === "Application:reload")
                {
                    reloadGrid()
                }

            }, this)
        );
    }

    function reloadGrid() {
        const reloadParams = { apply_filter: 'Y', clear_nav: 'Y' }
        const gridObject = BX.Main.gridManager.getById('<?=$arResult['GRID_ID']?>')

        if (gridObject.hasOwnProperty('instance')){
            gridObject.instance.reloadTable('POST', reloadParams)
        }
    }

</script>
