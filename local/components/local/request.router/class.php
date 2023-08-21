<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}


class ApplicationComponent extends CBitrixComponent
{
    public const SEF_DEFAULT_TEMPLATES = array(
        'view' => '#REQUEST_ID#/',
        'edit' => '#REQUEST_ID#/edit/',
    );

    public function executeComponent()
    {
        if (empty($this->arParams['SEF_MODE']) || $this->arParams['SEF_MODE'] != 'Y') {
            return;
        }

        if (empty($this->arParams['SEF_FOLDER'])) {
            return;
        }

        if (!is_array($this->arParams['SEF_URL_TEMPLATES'])) {
            $this->arParams['SEF_URL_TEMPLATES'] = [];
        }

        $sefTemplates = array_merge(self::SEF_DEFAULT_TEMPLATES, $this->arParams['SEF_URL_TEMPLATES']);

        $page = CComponentEngine::parseComponentPath(
            $this->arParams['SEF_FOLDER'],
            $sefTemplates,
            $arVariables
        );

        if (empty($page)) {
            $page = 'list';
        }

        $this->arResult = [
            'SEF_FOLDER' => $this->arParams['SEF_FOLDER'],
            'SEF_URL_TEMPLATES' => $sefTemplates,
            'VARIABLES' => $arVariables,
        ];

        $this->includeComponentTemplate($page);
    }
}
