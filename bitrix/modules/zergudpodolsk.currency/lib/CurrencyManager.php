<?php
namespace Zergudpodolsk;

use \Bitrix\Main\Config\Option;

class CurrencyManager
{
    public static function getDownloadAgentName()
    {
        return '\\' . __CLASS__ . '::downloadRateAgent();';
    }

    public static function downloadRateAgent()
    {
        if (!\Bitrix\Main\Loader::includeModule('currency')) {
            return self::getDownloadAgentName();
        }

        $days = split(',', Option::get('zergudpodolsk.currency', 'shedule_day_list'));
        if (!in_array(date('w'), $days)) {
            return self::getDownloadAgentName();
        }

        $arCurr = array();
        $rsRate = \CCurrency::GetList($by = 'currency', $order = 'asc');
        while ($arRate = $rsRate->Fetch()) {
            if ($arRate['CURRENCY'] != 'RUB' && $arRate['CURRENCY'] != 'RUR') {
                $arCurr[] = $arRate['CURRENCY'];
            }
        }

        if (empty($arCurr)) {
            return self::getDownloadAgentName();
        }

        $queryStr = 'date_req=' . date('d.m.Y');
        $adminDate = date($GLOBALS['DB']->DateFormatToPHP(\CLang::GetDateFormat('SHORT')));

        require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');
        $strQueryText = QueryGetData('www.cbr.ru', 80, '/scripts/XML_daily.asp', $queryStr, $errno, $errstr);

        $objXML = new \CDataXML();
        if ($objXML->LoadString($strQueryText)) {
            $arData = $objXML->GetArray();
            if (!empty($arData) && is_array($arData)) {

                foreach ($arData['ValCurs']['#']['Valute'] as $item) {
                    $currencyCode = $item["#"]["CharCode"][0]["#"];

                    if (in_array($currencyCode, $arCurr)) {
                        $rate = doubleval(str_replace(',', '.', $item['#']['Value'][0]['#']));

                        $arNewRate = array(
                            'CURRENCY' => $currencyCode,
                            'RATE_CNT' => intval($item['#']['Nominal'][0]['#']),
                            'RATE' => $rate,
                            'DATE_RATE' => $adminDate,
                        );

                        if (!\CCurrencyRates::GetList($by = 'id', $order = 'desc', $arNewRate)->Fetch()) {
                            \CCurrencyRates::Add($arNewRate);
                        }
                    }
                }
            }
        }

        return self::getDownloadAgentName();
    }
}