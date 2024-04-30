<?php
namespace Awz\Europost;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;
use Bitrix\Sale\Order;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class handlersBx {

    public static function registerHandler()
    {

        $result = new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            array(
                'Awz\Europost\Handler' => '/bitrix/modules/awz.europost/lib/handler.php',
                'Awz\Europost\Profiles\Pickup' => '/bitrix/modules/awz.europost/lib/profiles/pickup.php',
                //'Awz\Europost\Profiles\Standart' => '/bitrix/modules/awz.europost/lib/profiles/standart.php',
            )
        );

        return $result;

    }

    public static function OnAdminSaleOrderEditDraggable($args){
        $res = array(
            'getScripts'=>array('\Awz\Europost\handlersBx','editDraggableAddScript')
        );
        return $res;
    }

    public static function editDraggableAddScript($args){
        if(isset($args['ORDER']) && $args['ORDER'] instanceof Order){

            $order = $args['ORDER'];
            $propertyCollection = $order->getPropertyCollection();
            /* @var \Bitrix\Sale\EntityPropertyValue $prop*/
            $profileId = Helper::getProfileId($order, Helper::DOST_TYPE_PVZ);
            $prop = $propertyCollection->getItemByOrderPropertyCode(Helper::getPropPvzCode($profileId));

            if(!$prop) return '';

            $content = 'BX.addCustomEvent("onAfterSaleOrderTailsLoaded", function(){';
            $content .= "BX.insertAfter(BX.create('a', {
                      attrs: {
                         className: 'adm-btn adm-btn-green adm-btn-add',
                         href: '/',
                         onclick: 'BX.SidePanel.Instance.open(\"/bitrix/admin/awz_europost_picpoint_list.php?LANG=ru&page=order_edit&order=".$order->getId()."\",{cacheable: false});return false;'
                      },
                      text: '".Loc::getMessage('AWZ_EUROPOST_HANDLERBX_CHOISE')."'
                   }), BX.findChild(BX('tab_order_edit_table'), {tag: 'input', attribute: {name: 'PROPERTIES[".$prop->getPropertyId()."]'}}, true));";

            $content .= '});';
            return '<script>'.$content.'</script>';
        }
    }

    public static function OnEndBufferContent(&$content){
        global $APPLICATION;
        if($APPLICATION->getCurPage(false) == '/bitrix/admin/sale_order_ajax.php'){
            if($_REQUEST['action'] == 'changeDeliveryService' && $_REQUEST['formData']['order_id']){
                if(!\Bitrix\Main\Loader::includeModule('awz.europost')) return;

                $profileId = $_REQUEST['formData']['SHIPMENT'][1]['PROFILE'] ? $_REQUEST['formData']['SHIPMENT'][1]['PROFILE'] : $_REQUEST['formData']['SHIPMENT'][1]['DELIVERY_ID'];

                if($profileId){
                    $delivery = \Awz\Europost\Helper::deliveryGetByProfileId($profileId);
                    //print_r($delivery);die();
                    if(in_array($delivery['CLASS_NAME'],array('\Awz\Europost\Profiles\Pickup', '\Awz\Europost\Handler'))){
                        $json = \Bitrix\Main\Web\Json::decode($content);
                        if($delivery['CLASS_NAME'] == '\Awz\Europost\Handler'){
                            preg_match('/value="([0-9]+)"/is',$json['SHIPMENT_DATA']['PROFILES'], $mc);
                            $profileId = $mc[1];
                        }
                        $json['SHIPMENT_DATA']['PROFILES'] .= '<br><a href="#" class="adm-btn adm-btn-green adm-btn-add" onclick="BX.SidePanel.Instance.open(\'/bitrix/admin/awz_europost_picpoint_list.php?LANG=ru&profile_id='.intval($profileId).'&order='.intval($_REQUEST['formData']['order_id']).'&from=changeDeliveryService\',{cacheable: false});return false;">'.Loc::getMessage('AWZ_EUROPOST_HANDLERBX_CHOISE_PVZ').'</a>';
                        $content = \Bitrix\Main\Web\Json::encode($json);
                    }
                }


            }
        }
    }

    public static function OnSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
    {
        $request = Context::getCurrent()->getRequest();
        /* @var Order $order*/
        $order = $event->getParameter("ENTITY");
        $propertyCollection = $order->getPropertyCollection();

        $checkMyDeliveryPvz = Helper::getProfileId($order, Helper::DOST_TYPE_PVZ);

        if(!$checkMyDeliveryPvz) {
            $event->addResult(
                new \Bitrix\Main\EventResult(
                    \Bitrix\Main\EventResult::SUCCESS, $order
                )
            );
        }else{
			$pointId = false;
            $errorText = '';
            $setPoints = false;
            if($request->get('AWZ_EP_POINT_ID')){
                $pointId = preg_replace('/([^0-9A-z\-])/is', '', $request->get('AWZ_EP_POINT_ID'));
            }

            /* @var \Bitrix\Sale\EntityPropertyValue $prop*/
            $checkIsProp = false;
            $propAddress = false;
            foreach($propertyCollection as $prop){
                if($prop->getField('CODE') == Helper::getPropPvzCode($checkMyDeliveryPvz)){
                    $checkIsProp = true;
                    if($pointId){
                        $prop->setValue($pointId);
                    }
                    if($prop->getValue()){
                        $setPoints = true;
						$pointId = $prop->getValue();
                    }
                }elseif($prop->getField('CODE') == Helper::getPropAddress($checkMyDeliveryPvz)){
                    $propAddress = $prop;
                }
            }

            if($pointId){
                $pointData = PvzTable::getPvz($pointId);
                if($pointData){

                    if($propAddress){
                        $propAddress->setValue(Helper::formatPvzAddress($checkMyDeliveryPvz, $pointData));
                    }

                }else{
                    //$setPoints = false;
                    $errorText = Loc::getMessage('AWZ_EUROPOST_HANDLERBX_ERR_PVZDATA');
                }
            }

            if(!$setPoints || $errorText){
                if(!$errorText) $errorText = Loc::getMessage('AWZ_EUROPOST_HANDLERBX_ERR_PVZ');
                if(!$checkIsProp){
                    $errorText = Loc::getMessage('AWZ_EUROPOST_HANDLERBX_ERR_PVZ_PROP');
                }
                $event->addResult(
                    new \Bitrix\Main\EventResult(
                        \Bitrix\Main\EventResult::ERROR,
                        \Bitrix\Sale\ResultError::create(
                            new \Bitrix\Main\Error($errorText, "DELIVERY")
                        )
                    )
                );
            }else{
                $event->addResult(
                    new \Bitrix\Main\EventResult(
                        \Bitrix\Main\EventResult::SUCCESS, $order
                    )
                );
            }
        }

    }

    public static function OrderDeliveryBuildList(&$arResult, &$arUserResult, $arParams)
    {
        \CJSCore::Init(['ajax', 'awz_ep_lib']);

        $key = Option::get("fileman", "yandex_map_api_key");
        $key1 = Option::get(Handler::MODULE_ID, "yandex_map_api_key", "", "");
        if($key1) $key = $key1;
        $key2 = Option::get(Handler::MODULE_ID, "yandex_map_suggest_api_key", "", "");
        $host = 'api-maps.yandex.ru';
        if($key){
            $host = 'enterprise.api-maps.yandex.ru';
        }
        $setSearchAddress = "N";
        if($key && $key2){
            $setSearchAddress = Option::get(Handler::MODULE_ID, "MAP_ADDRESS", "N", "");
        }

        Asset::getInstance()->addString('<script>window._awz_ep_lib_setSearchAddress = "'.$setSearchAddress.'";</script>', true);
        Asset::getInstance()->addJs(
            '//'.$host.'/2.1/?lang=ru_RU&apikey='.$key.'&suggest_apikey='.$key2,
            true
        );

    }

    public static function OnAdminContextMenuShow(&$items)
    {

    }

    public static function OnEpilog()
    {

    }

    public static function OnSaleComponentOrderCreated($order, $arUserResult, $request, $arParams, $arResult, &$arDeliveryServiceAll, &$arPaySystemServiceAll)
    {

    }

}