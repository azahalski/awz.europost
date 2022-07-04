<?php
namespace Awz\Europost;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Order;

Loc::loadMessages(__FILE__);

class Helper {

    const DOST_TYPE_PVZ = 'pvz';
    const DOST_TYPE_ALL = 'all';
    const DOST_TYPE_ADR = 'address';

    public static $cacheActiveDelivery = array();

    public static function getApi(){

        return zApi::getInstance();

    }

    /**
     * Получение ид профиля Доставки из заказа
     *
     * @param Order $order
     * @param string $type all|pvz|address - тип профилей доставки
     * @return false|int
     * @throws SystemException
     * @throws ArgumentException
     * @throws ArgumentNullException
     */
    public static function getProfileId(Order $order, $type='all'){

        /* @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
        $shipmentCollection = $order->getShipmentCollection();
        $checkMyDelivery = false;
        /* @var \Bitrix\Sale\Shipment $shipment*/
        foreach ($shipmentCollection as $shipment) {
            if ($shipment->isSystem()) continue;
            /* @var $delivery \Bitrix\Sale\Delivery\Services\Base */
            $delivery = $shipment->getDelivery();
            if($delivery->isInstalled()){
                $classNames = Handler::getChildrenClassNames();
                if($type == self::DOST_TYPE_ALL || $type == self::DOST_TYPE_PVZ){
                    $className = '\\'.$classNames[0];
                    $params = \Bitrix\Sale\Delivery\Services\Manager::getById($delivery->getId());
                    if($params['CLASS_NAME'] == $className){
                        $checkMyDelivery = $delivery->getId();
                    }
                }
                if(isset($classNames[1])){
                    if($type == self::DOST_TYPE_ALL || $type == self::DOST_TYPE_ADR) {
                        $className = '\\' . $classNames[1];
                        $params = \Bitrix\Sale\Delivery\Services\Manager::getById($delivery->getId());
                        if ($params['CLASS_NAME'] == $className) {
                            $checkMyDelivery = $delivery->getId();
                        }
                    }
                }
            }
        }
        return $checkMyDelivery;

    }

    /**
     * Получение параметров профиля доставки из базы
     *
     * @param int $profileId
     * @return array
     * @throws SystemException
     * @throws LoaderException
     */
    public static function deliveryGetByProfileId($profileId){
        if(!Loader::includeModule('sale')){
            throw new SystemException(
                Loc::getMessage('AWZ_EUROPOST_HELPER_NOT_SALE_MODULE')
            );
        }
        return DeliveryManager::getById($profileId);
    }

    /**
     * получение кода свойства для записи ид ПВЗ с настроек модуля
     *
     * @param int $profileId
     * @return string
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public static function getPropPvzCode($profileId){
        return Option::get(Handler::MODULE_ID,
            'PVZ_CODE_'.$profileId,
            'AWZ_EP_POINT_ID', '');
    }

    /**
     * получение кода свойства для записи адреса
     *
     * @param int $profileId
     * @return string
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     */
    public static function getPropAddress($profileId){
        return Option::get(Handler::MODULE_ID,
            'PVZ_ADDRESS_'.$profileId,
            '', '');
    }

    /**
     * Формирование html данных о пвз
     *
     * @param false $id ид пвз в службе доставки
     * @param false $hideBtn скрыть кнопку выбора
     * @param string $template шаблон вывода
     * @return Result
     */
    public static function getBaloonHtml($id=false, $hideBtn=false, $template='.default'){

        global $APPLICATION;

        $result = new Result();

        if(!$id){
            $result->addError(new Error(Loc::getMessage('AWZ_EUROPOST_HELPER_ERR_EMPTY_ID')));
            return $result;
        }

        $resPoint = PvzTable::getPvz($id);
        if(!$resPoint){
            $result->addError(new Error(Loc::getMessage('AWZ_EUROPOST_HELPER_ERR_EMPTY_DATA')));
            return $result;
        }

        $item = $resPoint['PRM'];

        ob_start();
        $APPLICATION->IncludeComponent("awz:europost.baloon",	$template,
            Array(
                "DATA" => $item,
                "HIDE_BTN"=>$hideBtn ? 'Y' : 'N'
            ),
            null, array("HIDE_ICONS"=>"Y")
        );
        $html = ob_get_contents();
        ob_end_clean();

        $result->setData(array('html'=>$html));
        return $result;

    }

    public static function formatPvzAddress($profileId, $pointData){

        if(empty($pointData)) return '';

        $template = Option::get(Handler::MODULE_ID, "PVZ_ADDRESS_TMPL_".$profileId, "#NAME#", "");
        $templateData = array(
            '#ID#'=>$pointData['PRM']['id'],
            '#NAME#'=>$pointData['PRM']['name'],
        );

        return str_replace(array_keys($templateData),array_values($templateData),$template);

    }

    /**
     * Получение активных профилей доставки
     *
     * @return array array(array('ID'=>'NAME'))
     */
    public static function getActiveProfileIds($type='all'){

        if(!empty(self::$cacheActiveDelivery[$type])){
            return self::$cacheActiveDelivery[$type];
        }

        $deliveryProfileList = array();
        $classNames = Handler::getChildrenClassNames();
        foreach($classNames as &$cl){
            $cl = '\\'.$cl;
        }
        unset($cl);
        if($type == Helper::DOST_TYPE_PVZ){
            if(isset($classNames[1]))
                unset($classNames[1]);
        }else if($type == Helper::DOST_TYPE_ADR){
            unset($classNames[0]);
        }
        $r = \Bitrix\Sale\Delivery\Services\Table::getList(array(
            'select'=>array('*'),
            'filter'=>array('=CLASS_NAME'=>$classNames, '=ACTIVE'=>'Y')
        ));
        while($dt = $r->fetch()){
            $deliveryProfileList[$dt['ID']] = $dt['NAME'];
        }
        self::$cacheActiveDelivery[$type] = $deliveryProfileList;

        return $deliveryProfileList;
    }

}