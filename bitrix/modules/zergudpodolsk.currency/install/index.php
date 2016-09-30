<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Application;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

if (class_exists('zergudpodolsk_currency'))
    return;

class zergudpodolsk_currency extends CModule
{
    private $MODULES_FOLDER = 'bitrix';
    private $MODULE_ROOT_PATH;

    private $errors = array();

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__.'/version.php');

        $this->MODULE_ID = 'zergudpodolsk.currency';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('ZGP_CUR_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('ZGP_CUR_MODULE_DESCRIPTION');

        $this->PARTNER_NAME = Loc::getMessage('ZGP_CUR_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('ZGP_CUR_PARTNER_URI');

        $this->MODULE_ROOT_PATH = $_SERVER['DOCUMENT_ROOT']
            . '/' . $this->MODULES_FOLDER
            . '/modules/' . $this->MODULE_ID;
    }

    public function DoInstall()
    {
        if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES)) {
            foreach ($this->NEED_MODULES as $module) {
                if (!ModuleManager::isModuleInstalled($module)) {
                    $this->ShowForm('ERROR',
                        Loc::getMessage('ZGP_CUR_NEED_MODULES', array('#MODULE#' => $module))
                    );
                }
            }
        }

        if (strlen($this->NEED_MAIN_VERSION) > 0
            && version_compare(SM_VERSION, $this->NEED_MAIN_VERSION) <= 0
        ) {
            $this->ShowForm('ERROR',
                Loc::getMessage('ZGP_CUR_NEED_RIGHT_VER', array('#NEED#' => $this->NEED_MAIN_VERSION))
            );
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        $this->ShowForm('OK', Loc::getMessage('MOD_INST_OK'));
    }

    public function InstallDB()
    {
        \Bitrix\Main\Loader::includeModule($this->MODULE_ID);

        $checkDate = DateTime::createFromTimestamp(strtotime('tomorrow 06:00:00'));
        CAgent::AddAgent(
            \Zergudpodolsk\CurrencyManager::getDownloadAgentName(),
            $this->MODULE_ID,
            'Y',
            86400,
            '',
            'Y',
            $checkDate->toString(),
            100,
            false,
            true
        );

        Option::set($this->MODULE_ID, 'shedule_day_list', '1,2,3,4,5');
    }

    public function InstallEvents()
    {
    }

    public function InstallFiles()
    {

    }

    public function DoUninstall()
    {
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        $this->UnInstallFiles();
        $this->UnInstallEvents();

//        if($request["savedata"] != "Y")
            $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->ShowForm('OK', Loc::getMessage('MOD_UNINST_OK'));
    }

    public function UnInstallFiles()
    {

    }

    public function UnInstallEvents()
    {
    }

    public function UnInstallDB()
    {
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
        CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    private function ShowForm($type, $message, $buttonName='')
    {
        $keys = array_keys($GLOBALS);
        for($i=0; $i<count($keys); $i++)
            if($keys[$i]!='i' && $keys[$i]!='GLOBALS' && $keys[$i]!='strTitle' && $keys[$i]!='filepath')
                global ${$keys[$i]};

        $APPLICATION->SetTitle(Loc::getMessage('ZGP_CUR_MODULE_CUR_NAME'));

        include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

        echo CAdminMessage::ShowMessage(array('MESSAGE' => $message, 'TYPE' => $type));
        ?>
        <form action="<?= $APPLICATION->GetCurPage()?>" method="get">
            <p>
                <input type="hidden" name="lang" value="<?= LANG?>" />
                <input type="submit" value="<?= strlen($buttonName) ? $buttonName : Loc::getMessage('MOD_BACK')?>" />
            </p>
        </form>
        <?
        include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
        die();
    }
}