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
    const GRID_ID = 'APPLICATIONS_LIST';
    const ENTITY = 'LocalAbsenceApplication';
    const FILTER_FIELDS = ['UF_TYPE', 'UF_ABSENCE_TYPE', 'UF_STATE', 'UF_EMPLOYEE', 'UF_AUTHOR', 'UF_CURRENT_USER'];
    const USER_FIELDS = ['UF_EMPLOYEE', 'UF_AUTHOR', 'UF_APPROVER', 'UF_CURRENT_USER'];

    private static $headers;
    private static $filterFields;
    private static $filterPresets;
    private static $usersData;
    private $useMultiOrganizations;

    /**
     * ApplicationListComponent constructor.
     * @param null $component
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function __construct($component = null)
    {
        $this->useMultiOrganizations = Options::getInstance()->isMultiOrganizationEnabled();
        CModule::IncludeModule("local.absence");
        parent::__construct($component);
        self::$headers = array(
            array(
                'id' => 'ID',
                'name' => 'ID',
                'sort' => 'ID',
                'first_order' => 'desc',
                'type' => 'int',
                'default' => true,
            ),
            array(
                'id' => 'UF_DATE_CREATE',
                'name' => GetMessage("LOCAL_ABSENCE_CREATED_AT_DATE"),
                'sort' => 'UF_DATE_CREATE',
                'default' => true,
            ),
            array(
                'id' => 'UF_EMPLOYEE_NAME',
                'name' => GetMessage("LOCAL_ABSENCE_EMPLOYEE"),
                'default' => true,
            ),
            array(
                'id' => 'TYPE',
                'name' => GetMessage("LOCAL_ABSENCE_TYPE"),
                'sort' => 'UF_TYPE',
                'default' => true,
            ),
            array(
                'id' => 'VACATION_TRANSFER',
                'name' => GetMessage("LOCAL_ABSENCE_TRANSFERRED"),
                'default' => true,
            ),
            array(
                'id' => 'PERIODS',
                'name' => GetMessage("LOCAL_ABSENCE_PERIODS"),
                'sort' => 'UF_PERIODS',
                'default' => true,
            ),
            array(
                'id' => 'STATE',
                'name' => GetMessage("LOCAL_ABSENCE_STATE"),
                'sort' => 'UF_STATE',
                'default' => true,
            ),
            array(
                'id' => 'CURRENT_USER',
                'name' => GetMessage("LOCAL_ABSENCE_CURENT_EXECUTOR"),
                'default' => true,
            ),
            array(
                'id' => 'APPROVER',
                'name' => GetMessage("LOCAL_ABSENCE_APPROVER"),
                'default' => true,
            ),
            array(
                'id' => 'UF_DATE_APPROVE',
                'name' => GetMessage("LOCAL_ABSENCE_APPROVED_AT_DATE"),
                'sort' => 'UF_DATE_APPROVE',
                'default' => true,
            ),
            array(
                'id' => 'UF_AUTHOR_NAME',
                'name' => GetMessage("LOCAL_ABSENCE_AUTHOR"),
                'default' => true,
            ),
            array(
                'id' => 'VACATIONS',
                'name' => GetMessage("LOCAL_ABSENCE_VACATIONS"),
                'default' => true
            ),
            array(
                'id' => 'ABSENCES_IDS',
                'name' => GetMessage("LOCAL_ABSENCE_ABSENCES"),
                'default' => true
            ),
        );
        self::$filterFields = array(
            array(
                'id' => 'ID',
                'name' => 'ID'
            ),
            array(
                'id' => 'UF_DATE_CREATE',
                'name' => GetMessage("LOCAL_ABSENCE_CREATED_AT_DATE"),
                'default' => true,
            ),
            array(
                'id' => 'UF_EMPLOYEE',
                'name' => GetMessage("LOCAL_ABSENCE_EMPLOYEE"),
                'default' => true,
            ),
            array(
                'id' => 'UF_TYPE',
                'name' => GetMessage("LOCAL_ABSENCE_TYPE"),
                'default' => true,
            ),
            array(
                'id' => 'UF_STATE',
                'name' => GetMessage("LOCAL_ABSENCE_STATE"),
                'default' => true,
            ),
            array(
                'id' => 'UF_AUTHOR',
                'name' => GetMessage("LOCAL_ABSENCE_AUTHOR"),
                'default' => true,
            ),
            array(
                'id' => 'UF_CURRENT_USER',
                'name' => GetMessage("LOCAL_ABSENCE_CURENT_EXECUTOR"),
                'default' => true,
            ),
            array(
                'id' => 'DATE_BEGIN',
                'name' => GetMessage("LOCAL_ABSENCE_DATE_START"),
                'default' => true,
            ),
            array(
                'id' => 'DATE_END',
                'name' => GetMessage("LOCAL_ABSENCE_DATE_END"),
                'default' => true,
            ),
            array(
                'id' => 'SUBDIVISION',
                'name' => GetMessage("LOCAL_ABSENCE_DEPARTMENT"),
            ),
            array(
                'id' => "HIERARCHY",
                "name" => GetMessage("LOCAL_ABSENCE_ENABLE_DEPARTMENT_HIERARCHY")
            )
        );

        if($this->useMultiOrganizations) {
            self::$filterFields[] = [
                'id' => 'ORGANIZATION',
                'name' => GetMessage("LOCAL_ABSENCE_USER_ORGANIZATION_FILTER"),
                'sort' => 'ORGANIZATION',
                'default' => true,
            ];
        }

        self::$usersData = UserData::getUsersData();
    }


    /**
     * @param $department
     * @param $allDepartments
     * @return array
     */
    private function getDepartmentsChild($department, $allDepartments): array
    {
        $deps = [];
        $deps[] = $department;
        if (isset($allDepartments["TREE"][$department])) {
            foreach ($allDepartments["TREE"][$department] as $subDep) {
                $deps = array_merge($deps, $this->getDepartmentsChild($subDep, $allDepartments));
            }
        }
        return $deps;
    }

    /**
     * @return array|mixed|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException|RestException
     */
    public function executeComponent(): ?array
    {
        global $USER;

        $isAdmin = $USER->CanDoOperation('local.absence.admin', $USER->GetID()) || $USER->IsAdmin();

        $enumFields = Application::getFields([]);
        //TODO ������� � ��������� ��������� ��������� ������ � ���������, �����������, ����������
        $grid = new Grid\Options(self::GRID_ID);

        //region Sort
        $gridSort = $grid->getSorting();
        $sort = $gridSort['sort'];

        if (empty($sort)) {
            $sort = array('ID' => 'desc');
        }
        //endregion

        //region Filter
        $gridFilter = new Filter\Options(self::GRID_ID, self::$filterPresets);
        $gridFilterValues = $gridFilter->getFilter(self::$filterFields);
        $arFilter = [];
        foreach ($gridFilterValues as $key => $filterValue) {
            if (preg_match("/(^.*)_from$/", $key, $arr)) {
                $arFilter['>=' . $arr[1]] = $filterValue;
            }

            if (preg_match("/(^.*)_to$/", $key, $arr)) {
                $arFilter['<=' . $arr[1]] = $filterValue;
            }

            if (in_array($key, self::FILTER_FIELDS)) {
                if (in_array($key, ['UF_AUTHOR', 'UF_EMPLOYEE', 'UF_APPROVER', 'UF_CURRENT_USER'])) {
                    $arFilter[$key] = str_replace('U', '', $filterValue);
                    if (!$isAdmin) {
                        foreach ($arFilter[$key] as $elKey => $item) {
                            if (!in_array($item, self::$usersData['ids'])) {
                                unset($arFilter[$key][$elKey]);
                            }
                        }
                    }
                } else {
                    $arFilter[$key] = $filterValue;
                }

            }


            if ($key === "SUBDIVISION") {
                foreach ($filterValue as $idx=>$item) {
                    $filterValue[$idx] = substr($item, 2);
                }

                if (isset($gridFilterValues["HIERARCHY"]) && $gridFilterValues["HIERARCHY"] === "Y") {
                    $allDeps = \CIntranetUtils::GetStructure();
                    $deps = [];
                    foreach ($filterValue as $item) {
                        $deps = array_merge($deps, $this->getDepartmentsChild($item, $allDeps));
                    }
                } else {
                    $deps = $filterValue;
                }

                $rsUsers = \CIntranetUtils::getDepartmentEmployees($deps);
                $arFilter['UF_EMPLOYEE'] = isset($arFilter['UF_EMPLOYEE']) ? $arFilter['UF_EMPLOYEE'] : [];
                while ($arUser = $rsUsers->fetch()) {
                    if (!in_array((int)$arUser['ID'], $arFilter['UF_EMPLOYEE'], true)) {
                        $arFilter['UF_EMPLOYEE'][] = (int)$arUser['ID'];
                    }
                }
            }

            if ($key === "FIND") {
                if (!empty($filterValue)) {
                    $dbUsers = UserTable::getList(array(
                        'select' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'),
                        "filter" => [
                            'LOGIC' => 'OR',
                            "*%NAME" => $filterValue,
                            "*%LAST_NAME" => $filterValue,
                        ]
                    ));

                    $arFilter["UF_EMPLOYEE"] = [];
                    while ($arUser = $dbUsers->fetch()) {
                        $arFilter["UF_EMPLOYEE"][] = $arUser["ID"];
                    }
                }
            }

            if($key === "ORGANIZATION") {
                $arFilter['ORGANIZATION'] = $filterValue;
            }
        }

        $isAdmin = $USER->CanDoOperation('local.absence.admin', $USER->GetID()) || $USER->IsAdmin();
        if (!$isAdmin && !isset($arFilter['UF_EMPLOYEE'])) {
            $arFilter['UF_EMPLOYEE'] = self::$usersData['ids'];
        }

        //endregion

        if (!empty($arFilter['ORGANIZATION'])) {
            $organizationUsers = Organization::getUsersByOrganizationId($arFilter['ORGANIZATION']);
            if(count($organizationUsers) > 0) {
                if(isset($arFilter["UF_EMPLOYEE"]) && count($arFilter["UF_EMPLOYEE"])) {
                    foreach ($arFilter["UF_EMPLOYEE"] as $key => $employee) {
                        if(!in_array($employee, $organizationUsers)) {
                            unset($arFilter["UF_EMPLOYEE"][$key]);
                        }
                    }
                } else {
                    $arFilter["UF_EMPLOYEE"] = $organizationUsers;
                }
            } else {
                $arFilter["UF_EMPLOYEE"] = -1;
            }
            unset($arFilter['ORGANIZATION']);
        }

        if(array_key_exists('>=DATE_BEGIN', $arFilter) ||
            array_key_exists('<=DATE_BEGIN', $arFilter) ||
            array_key_exists('>=DATE_END', $arFilter) ||
            array_key_exists('<=DATE_END', $arFilter)
        ) {
            $absenceFilter = [];

            if(array_key_exists('>=DATE_BEGIN', $arFilter) &&
                array_key_exists('<=DATE_BEGIN', $arFilter)
            ) {
                $absenceFilter['>=UF_DATE_BEGIN'] = $arFilter['>=DATE_BEGIN'];
                $absenceFilter['<=UF_DATE_BEGIN'] = $arFilter['<=DATE_BEGIN'];
            }
            if(array_key_exists('>=DATE_END', $arFilter) &&
                array_key_exists('<=DATE_END', $arFilter)
            ) {
                $absenceFilter['>=UF_DATE_END'] = $arFilter['>=DATE_END'];
                $absenceFilter['<=UF_DATE_END'] = $arFilter['<=DATE_END'];
            }

            if(!empty($absenceFilter)) {
                $arAbsenceIds = [];
                $absences = Absence::getList(['filter' => $absenceFilter]);
                foreach ($absences as $absence) {
                    if((int)$absence['application'] > 0) {
                        $arAbsenceIds[] = $absence['application'];
                    }
                }

                if(!empty($arAbsenceIds)) {
                    if(!array_key_exists('ID', $arFilter)) {
                        $arFilter['ID'] = $arAbsenceIds;
                    } else {
                        if(!in_array($arFilter['ID'], $arAbsenceIds, true)) {
                            $arFilter['ID'] = -1;
                        }
                    }
                } else {
                    $arFilter['ID'] = -1;
                }
            }

            unset(
                $arFilter['>=DATE_BEGIN'],
                $arFilter['<=DATE_BEGIN'],
                $arFilter['>=DATE_END'],
                $arFilter['<=DATE_END']
            );
        }

        // ���� ������������ �� �����, ���� ������������� �� ����������� + �������� ������������
        if (!$isAdmin) {
            $arFilter[] = [
                'LOGIC' => 'OR',
                // TODO: �������� ��������� ������� "���", ����� ��� ����������� ������ ������ �������� ������������ � ��� �� ����������� / �����������
                'UF_EMPLOYEE' => self::$usersData['ids'],
                'UF_CURRENT_USER' => $USER->GetID(),
                'UF_APPROVER' => $USER->GetID(),
            ];
        }

        //region Pagination
        $count = Application::getCountOfRows($arFilter);
        $nav = new PageNavigation(self::GRID_ID);
        $nav->allowAllRecords(true)
            ->setRecordCount($count)
            ->setPageSize(20)
            ->initFromUri();
        //endregion

        $applications = $this->getRowItems([
            'filter' => $arFilter,
            'limit' => $nav->getLimit(),
            'offset' => $nav->getOffset(),
            'order' => $sort
        ]);
        $rows = self::prepareRows($applications);

        $leaveType = [];
        foreach (Application::getAbsenceTypesReference() as $type) {
            if ($type["xmlId"] == "leave") {
                $leaveType = $type;
                break;
            }
        }

        $this->arResult = array(
            'GRID_ID' => self::GRID_ID,
            'APPLICATIONS' => $applications,
            'DEPARTMENTS' => self::$usersData['departments'],
            'FILTER' => [
                ['id' => 'ID', 'name' => 'ID', 'type' => 'number'],
                ['id' => 'UF_DATE_CREATE', 'name' => GetMessage("LOCAL_ABSENCE_DATE"), 'type' => 'date', 'default' => true],
                ['id' => 'UF_EMPLOYEE', 'name' => GetMessage("LOCAL_ABSENCE_EMPLOYEE"), 'type' => 'dest_selector', 'default' => true,
                    'params' => array(
                        'apiVersion' => '3',
                        'context' => 'SONET_GROUP_LIST_FILTER_OWNER',
                        'multiple' => 'Y',
                        'contextCode' => 'U',
                        'enableAll' => 'N',
                        'enableSonetgroups' => 'N',
                        'allowEmailInvitation' => 'N',
                        'allowSearchEmailUsers' => 'N',
                        'departmentSelectDisable' => 'Y',
                        'siteDepartmentId' => self::$usersData['departmentID'],
                    )],
                ['id' => 'UF_TYPE', 'name' => GetMessage("LOCAL_ABSENCE_TYPE"), 'type' => 'list', 'default' => true, 'params' => array('multiple' => 'Y'),
                    'items' => $enumFields['UF_TYPE']],
                ['id' => 'UF_STATE', 'name' => GetMessage("LOCAL_ABSENCE_STATE"), 'type' => 'list', 'default' => true, 'params' => array('multiple' => 'Y'),
                    'items' => RequestStates::getItemsForSelect('ID', 'UF_TITLE')],
                ['id' => 'UF_AUTHOR', 'name' => GetMessage("LOCAL_ABSENCE_AUTHOR"), 'type' => 'dest_selector', 'default' => true,
                    'params' => array(
                        'apiVersion' => '3',
                        'context' => 'SONET_GROUP_LIST_FILTER_OWNER',
                        'multiple' => 'Y',
                        'contextCode' => 'U',
                        'enableAll' => 'N',
                        'enableSonetgroups' => 'N',
                        'allowEmailInvitation' => 'N',
                        'allowSearchEmailUsers' => 'N',
                        'departmentSelectDisable' => 'Y',
                        'siteDepartmentId' => self::$usersData['departmentID'],
                    )],
                ['id' => 'UF_CURRENT_USER', 'name' => GetMessage("LOCAL_ABSENCE_CURENT_EXECUTOR"), 'type' => 'dest_selector', 'default' => true,
                    'params' => array(
                        'apiVersion' => '3',
                        'context' => 'SONET_GROUP_LIST_FILTER_OWNER',
                        'multiple' => 'Y',
                        'contextCode' => 'U',
                        'enableAll' => 'N',
                        'enableSonetgroups' => 'N',
                        'allowEmailInvitation' => 'N',
                        'allowSearchEmailUsers' => 'N',
                        'departmentSelectDisable' => 'Y',
                        'siteDepartmentId' => self::$usersData['departmentID'],
                    )],
                ['id' => 'DATE_BEGIN', 'name' => GetMessage("LOCAL_ABSENCE_DATE_START"), 'type' => 'date', 'default' => true],
                ['id' => 'DATE_END', 'name' => GetMessage("LOCAL_ABSENCE_DATE_END"), 'type' => 'date', 'default' => true],
                ['id' => 'SUBDIVISION', 'name' => GetMessage("LOCAL_ABSENCE_DEPARTMENT"), 'type' => 'dest_selector', 'default' => true,
                    'params' => array(
                        'apiVersion' => '3',
                        'context' => 'USER_LIST_FILTER_DEPARTMENT',
                        'multiple' => 'Y',
                        'contextCode' => 'D',
                        'enableAll' => 'N',
                        'enableUsers' => 'N',
                        'enableDepartments' => 'Y',
                        'enableSonetgroups' => 'N',
                        'allowEmailInvitation' => 'N',
                        'allowSearchEmailUsers' => 'Y',
                        'departmentSelectDisable' => 'N',
                    )],
                ['id' => 'HIERARCHY', 'name' => GetMessage("LOCAL_ABSENCE_SHOW_SUBORDINATE_DEPARTMENTS"), 'type' => 'checkbox', 'default' => true]
            ],
            'HEADERS' => self::$headers,
            'ROWS' => $rows,
            'NAV' => $nav,
            'SORT' => $sort,
        );

        if($this->useMultiOrganizations) {
            $this->arResult['FILTER'][] = [
                'id' => 'ORGANIZATION',
                'name' => GetMessage("LOCAL_ABSENCE_ORGANIZACIA"),
                'type' => 'list',
                'default' => true,
                'params' => array ('multiple' => 'Y'),
                'items' => Organization::getItemsForSelect('id', 'name', ['filter' => ['ACTIVE' => 'Y']])
            ];
        }

        $this->includeComponentTemplate();
        return $this->arResult;
    }


    /**
     * @param array $params
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws RestException
     */
    private function getRowItems($params = array()): array
    {
        // join ��������
        $hlblock = HighloadBlockTable::getList(['filter' => ['NAME' => 'LocalAbsenceRequestStates']])->fetch();
        $entity = HighloadBlockTable::compileEntity($hlblock);
        $params['runtime'] = [
            new ReferenceField(
                'STATE',
                $entity->getDataClass(),
                Join::on('this.UF_STATE', 'ref.ID')
            )
        ];
        $params['select'] = ['ID', 'UF_*', 'STATE.*'];
        $applications = HLBlock::getList($params, self::ENTITY);

        foreach ($applications as $idx => $application) {
            if(isset($application['UF_PERIODS'])&&count($application['UF_PERIODS'])>1) {
                $applications[$idx]['UF_PERIODS'] = Application::sortPeriod($application['UF_PERIODS']);
            }
        }

        $userIds = array_unique(
            array_merge(
                array_column($applications, 'UF_EMPLOYEE'),
                array_column($applications, 'UF_AUTHOR'),
                array_column($applications, 'UF_APPROVER'),
                array_column($applications, 'UF_CURRENT_USER')
            )
        );
        $userIds = array_filter(
            $userIds,
            function ($userId) {
                return intval($userId) > 0;
            }
        );

        $dbUsers = UserTable::getList(array(
            'filter' => array('=ID' => $userIds)
        ));
        $users = array();
        foreach ($dbUsers as $user) {
            $users[$user['ID']] = $user;
        }

        foreach ($applications as &$application) {
            foreach (self::USER_FIELDS as $field) {
                if ((int)$application[$field] > 0) {
                    $application[$field . '_NAME'] = $users[$application[$field]];
                }
            }
            $application['TYPE'] = Application::getApplicationTypesValues()[$application['UF_TYPE']];
            $application['VACATION_TRANSFERS'] = Absence::loadByIds($application['UF_VACATION_TRANSFER'] ?? []);

            $absences = Absence::loadByApplicationId([$application['ID']]);
            $application['ABSENCES'] = is_array($absences) && array_key_exists($application['ID'], $absences) ?
                $absences[$application['ID']] : [];

            // ���������� �������
            $application['UF_STATE'] = [
                'ID' => $application['LOCAL_ABSENCE_APPLICATION_STATE_ID'],
                'XML_ID' => $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_XML_ID'],
                'TITLE' => $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_TITLE'],
                'BACKGROUND_COLOR' => $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_BACKGROUND_COLOR'],
                'TEXT_COLOR' => $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_TEXT_COLOR'],
            ];
            unset(
                $application['LOCAL_ABSENCE_APPLICATION_STATE_ID'],
                $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_XML_ID'],
                $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_TITLE'],
                $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_BACKGROUND_COLOR'],
                $application['LOCAL_ABSENCE_APPLICATION_STATE_UF_TEXT_COLOR']
            );
        }

        return $applications;
    }

    /**
     * @param $applications
     * @return array
     */
    private function prepareRows($applications): array
    {
        $rows = [];
        $users = UserDataHelper::getSubordinates();
        foreach ($applications as $application) {
            $rows[] = array(
                'id' => $application['ID'],
                'actions' => (false) ? [ // TODO 2.0.x: ������ ������������� ������ ���� ��������, ���� ���� ����� action ��� �������� ������������
                    [
                        'TITLE' => GetMessage("LOCAL_ABSENCE_SHOW"),
                        'TEXT' => GetMessage("LOCAL_ABSENCE_SHOW"),
                        'ONCLICK' => 'openApplication(event, "' . $this->arParams['URL_TEMPLATES']['DETAIL'] . $application['ID'] . '/")',
                        'DEFAULT' => true
                    ],
                    [
                        'TITLE' => GetMessage("LOCAL_ABSENCE_EDIT"),
                        'TEXT' => GetMessage("LOCAL_ABSENCE_EDIT"),
                        'ONCLICK' => 'openApplication(event, "' . $this->arParams['URL_TEMPLATES']['DETAIL'] . $application['ID'] . '/edit/")',
                    ]
                ]
                    : [
                        [
                            'TITLE' => GetMessage("LOCAL_ABSENCE_SHOW"),
                            'TEXT' => GetMessage("LOCAL_ABSENCE_SHOW"),
                            'ONCLICK' => 'openApplication(event, "' . $this->arParams['URL_TEMPLATES']['DETAIL'] . $application['ID'] . '/")',
                            'DEFAULT' => true
                        ]
                    ],
                'data' => $application,
                'columns' => [
                    'ID' => '<a href="' . LOCAL_ABSENCE_PUBLIC_URL_REQUESTS . $application['ID'] . '/" 
                    onclick="openApplication(event, \'' . $this->arParams['URL_TEMPLATES']['DETAIL'] . $application['ID'] . '/\')"
                    >' . $application['ID'] . '</a>',
                    'UF_EMPLOYEE_NAME' => empty($application['UF_EMPLOYEE']) ? '' : UserDataHelper::prepareTaskRowUserBaloonHtml([
                        'PREFIX' => "APPLICATION_{$application['ID']}_RESPONSIBLE",
                        'USER_ID' => $application['UF_EMPLOYEE'],
                        'USER_NAME' => CUser::FormatName(CSite::GetNameFormat(), $application['UF_EMPLOYEE_NAME']),
                        'USER_PROFILE_URL' => "/company/personal/user/{$application['UF_EMPLOYEE']}/"
                    ]),
                    'UF_AUTHOR_NAME' => empty($application['UF_AUTHOR_NAME']) ? '' : UserDataHelper::prepareTaskRowUserBaloonHtml([
                        'PREFIX' => "APPLICATION_{$application['ID']}_RESPONSIBLE",
                        'USER_ID' => $application['UF_AUTHOR'],
                        'USER_NAME' => CUser::FormatName(CSite::GetNameFormat(), $application['UF_AUTHOR_NAME']),
                        'USER_PROFILE_URL' => "/company/personal/user/{$application['UF_AUTHOR']}/"
                    ]),
                    'APPROVER' => empty($application['UF_APPROVER']) ? '' : UserDataHelper::prepareTaskRowUserBaloonHtml([
                        'PREFIX' => "APPLICATION_{$application['ID']}_RESPONSIBLE",
                        'USER_ID' => $application['UF_APPROVER'],
                        'USER_NAME' => CUser::FormatName(CSite::GetNameFormat(), $application['UF_APPROVER_NAME']),
                        'USER_PROFILE_URL' => "/company/personal/user/{$application['UF_APPROVER']}/"
                    ]),

                    'CURRENT_USER' => empty($application['UF_CURRENT_USER']) ? '' : UserDataHelper::prepareTaskRowUserBaloonHtml([
                        'PREFIX' => "APPLICATION_{$application['ID']}_RESPONSIBLE",
                        'USER_ID' => $application['UF_CURRENT_USER'],
                        'USER_NAME' => CUser::FormatName(CSite::GetNameFormat(), $application['UF_CURRENT_USER_NAME']),
                        'USER_PROFILE_URL' => "/company/personal/user/{$application['UF_CURRENT_USER']}/"
                    ]),
                    'PERIODS' => '<a href="' . LOCAL_ABSENCE_PUBLIC_URL_REQUESTS . $application['ID'] . '/" 
                    onclick="openApplication(event, \'' . $this->arParams['URL_TEMPLATES']['DETAIL'] . $application['ID'] . '/\')"
                    >' . implode(', ', $application['UF_PERIODS']) . '</a>',
                    'STATE' => self::getStateFieldHtml($application["UF_STATE"]),
                    'TYPE' => self::getEnumFieldHtml($application['TYPE']),
                    'VACATION_TRANSFER' => self::getVacationTransfersHtml($application['VACATION_TRANSFERS']),
                    'VACATIONS' => self::getVacationsHtml($application['ABSENCES'] ?? []),
                    'ABSENCES_IDS' => self::getAbsencesHtml($application['ABSENCES'] ?? []),
                ]
            );
        }
        return $rows;
    }

    /**
     * @param $value
     * @return string
     */
    private static function getEnumFieldHtml($value)
    {
        return '<div class="enum-container">' .
            '<div class="enum-field enum-field-' . $value['xmlId'] . '">' .
            '<span class="enum-field-text">'
            . $value['name'] .
            '</span>' .
            '</div>' .
            '<div/>';
    }

    /**
     * @param $value
     * @return string
     */
    private static function getStateFieldHtml($value): string
    {
        return '<div class="enum-container">' .
            '<div class="enum-field" style="background-color: ' . $value['BACKGROUND_COLOR'] . '; color: ' . $value['TEXT_COLOR'] . '">' .
            '<span class="enum-field-text">'
            . $value['TITLE'] .
            '</span>' .
            '</div>' .
            '<div/>';
    }

    /**
     * @param array $arVacations
     * @return string
     */
    private static function getVacationTransfersHtml(array $arVacations): string
    {
        $result = '';
        foreach ($arVacations as $vacation) {
            $result .= '<span>' .
                GetMessage("LOCAL_ABSENCE_VACATION_FROM") . $vacation['from'] . " ".GetMessage("LOCAL_ABSENCE_TO") . $vacation['to'] .
                '</span>';
        }
        return $result;
    }

    private static function getVacationsHtml(array $arVacations): string
    {
        $result = '';
        foreach ($arVacations as $vacation) {
            $id = $vacation['vacationApplication'];

            if((int)$id > 0) {
                $result .= '<a href="' . LOCAL_ABSENCE_PUBLIC_URL_APPLICATIONS . $id . '/"
                    onclick="openApplication(event, \'' . LOCAL_ABSENCE_PUBLIC_URL_APPLICATIONS . $id . '/\')"
                    >' . GetMessage("LOCAL_ABSENCE_VACATION") . $id . '</a><br>';
            }
        }
        return $result;
    }

    private static function getAbsencesHtml(array $arAbsences): string
    {
        return implode(', ', array_column($arAbsences, 'id'));
    }
}
