<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\DateTime;

$module_id = 'zergudpodolsk.currency';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule($module_id);

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$agentFilter = array('MODULE_ID' => $module_id,
    '=NAME' => '\Zergudpodolsk\CurrencyManager::downloadRateAgent();'
);

$aTabs = array(
    array(
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS'),
        'OPTIONS' => array(
            array('shedule_day_list', Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_SCHEDULE'),
                '',
                array('multiselectbox', array(
                    '1' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY1'),
                    '2' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY2'),
                    '3' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY3'),
                    '4' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY4'),
                    '5' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY5'),
                    '6' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY6'),
                    '7' => Loc::getMessage('ZGP_CUR_MODULE_TAB_SETTINGS_DAY7'),
                    )
                )
            ),
        ),
    ),
);

if ($request->isPost() && $request['Update'] && check_bitrix_sessid()){
    foreach ($aTabs as $aTab) {
        //Или можно использовать __AdmSettingsSaveOptions($MODULE_ID, $arOptions);
        foreach ($aTab['OPTIONS'] as $arOption)
        {
            if (!is_array($arOption)) //Строка с подсветкой. Используется для разделения настроек в одной вкладке
                continue;

            if ($arOption['note']) //Уведомление с подсветкой
                continue;

            //Или __AdmSettingsSaveOption($MODULE_ID, $arOption);
            $optionName = $arOption[0];

            $optionValue = $request->getPost($optionName);

            Option::set($module_id, $optionName, is_array($optionValue) ? implode(",", $optionValue):$optionValue);
        }
    }
} elseif ($request->isPost()  && check_bitrix_sessid() && $request['agents'] == 'Y' && !empty('action')) {
    switch ($action) {
        case 'activate':
        case 'deactivate':
            $agentIterator = CAgent::GetList(array(), $agentFilter);
            if ($currencyAgent = $agentIterator->Fetch()) {
                $active = ($action == 'activate' ? 'Y' : 'N');
                CAgent::Update(
                    $currencyAgent['ID'],
                    array('ACTIVE' => $active)
                );
            }
            break;
        case 'create':
            $checkDate = DateTime::createFromTimestamp(strtotime('tomorrow 06:00:00'));
            CAgent::AddAgent(
                '\Zergudpodolsk\CurrencyManager::downloadRateAgent();',
                $module_id,
                'Y',
                86400,
                '',
                'Y',
                $checkDate->toString(),
                100,
                false,
                true
            );
            break;
    }
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

?>
<? $tabControl->Begin(); ?>
<form method='post'
      action='<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&amp;lang=<?=$request['lang']?>'
      name='zergudpodolsk_currency_settings'>

    <? foreach ($aTabs as $aTab):
        if($aTab['OPTIONS']):?>
            <? $tabControl->BeginNextTab(); ?>
            <? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>

        <?      endif;
    endforeach; ?>

    <?
    $tabControl->BeginNextTab();



    $tabControl->Buttons(); ?>

    <input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
    <input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
    <?=bitrix_sessid_post();?>
</form>
<? $tabControl->End(); ?>

<?php
$agentTabs = array(
    array(
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('ZGP_CUR_MODULE_TAB_AGENTS'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('ZGP_CUR_MODULE_TAB_AGENTS_TITLE')
    ),
);

$tabAgent = new CAdminTabControl('tabAgent', $agentTabs);

?><h2><?= Loc::getMessage('ZGP_CUR_MODULE_PROC'); ?></h2><?

$tabAgent->Begin();
$tabAgent->BeginNextTab();

?><form method="POST" action="<?echo $APPLICATION->GetCurPage();?>?lang=<?echo LANGUAGE_ID?>&mid=<?=$module_id?>" name="zergudpodolsk_currency_agents"><?
echo bitrix_sessid_post();
?><h4><? echo Loc::getMessage('ZGP_CUR_MODULE_PROC'); ?></h4><?

$currencyAgent = false;
$agentIterator = CAgent::GetList(array(), $agentFilter);
if ($agentIterator) {
    $currencyAgent = $agentIterator->Fetch();
}

if (!empty($currencyAgent)) {
    $currencyAgent['LAST_EXEC'] = (string)$currencyAgent['LAST_EXEC'];
    $currencyAgent['NEXT_EXEC'] = (string)$currencyAgent['NEXT_EXEC'];
    ?><b><? echo Loc::getMessage('ZGP_CUR_AGENT_ACTIVE'); ?>:</b>&nbsp;<?
    echo ($currencyAgent['ACTIVE'] == 'Y'
        ? Loc::getMessage('ZGP_CUR_AGENT_ACTIVE_YES')
        : Loc::getMessage('ZGP_CUR_AGENT_ACTIVE_NO'));
    ?><br><?
    if ($currencyAgent['LAST_EXEC'])
    {
        ?><b><? echo Loc::getMessage('ZGP_CUR_AGENT_LAST_EXEC'); ?>:</b>&nbsp;<? echo $currencyAgent['LAST_EXEC']; ?><br>
        <? if ($currencyAgent['ACTIVE'] == 'Y')
    {
        ?><b><? echo Loc::getMessage('ZGP_CUR_AGENT_NEXT_EXEC');?>:</b>&nbsp;<? echo $currencyAgent['NEXT_EXEC']; ?><br>
        <?
    }
    }
    elseif ($currencyAgent['ACTIVE'] == 'Y')
    {
        ?><b><? echo Loc::getMessage('ZGP_CUR_AGENT_PLANNED_NEXT_EXEC') ?>:</b>&nbsp;<? echo $currencyAgent['NEXT_EXEC']; ?><br>
        <?
    }
    if ($currencyAgent['ACTIVE'] != 'Y')
    {
        ?><br><input type="hidden" name="action" value="activate">
        <input type="submit" name="activate" value="<? echo Loc::getMessage('ZGP_CUR_AGENT_ACTIVATE'); ?>"><?
    }
    else
    {
        ?><br><input type="hidden" name="action" value="deactivate">
        <input type="submit" name="deactivate" value="<? echo Loc::getMessage('ZGP_CUR_AGENT_DEACTIVATE'); ?>"><?
    }
}
else
{
    ?><b><? echo Loc::getMessage('ZGP_CUR_AGENT_ABSENT'); ?></b><br><br>
    <input type="hidden" name="action" value="create">
    <input type="submit" name="startagent" value="<? echo Loc::getMessage('ZGP_CUR_AGENT_CREATE_AGENT'); ?>">
    <?
}

?><input type="hidden" name="agents" value="Y">
    </form><?
$tabAgent->End();
