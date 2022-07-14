<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
global $APPLICATION;
$module_id = "awz.europost";

\Bitrix\Main\Loader::includeModule($module_id);
\Bitrix\Main\Loader::includeModule('sale');

use Awz\Europost\Helper;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Security;

Loc::loadMessages(__FILE__);

$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage("AWZ_EUROPOST_ADMIN_PL_TITLE"));
$APPLICATION->SetAdditionalCSS("/bitrix/css/".$module_id."/style.css");

\CUtil::InitJSCore(array('ajax', 'awz_ep_lib'));

$key = Option::get("fileman", "yandex_map_api_key");
$setSearchAddress = Option::get($module_id, "MAP_ADDRESS", "N", "");
Asset::getInstance()->addString('<script>window._awz_ep_lib_setSearchAddress = "'.$setSearchAddress.'";</script>', true);
Asset::getInstance()->addString('<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU&apikey='.$key.'"></script>', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");



$order = \Bitrix\Sale\Order::load(intval($_REQUEST['order']));

if($_REQUEST['page'] == 'order_edit'){
    $profileId = Helper::getProfileId($order, Helper::DOST_TYPE_PVZ);
}else{
    $profileId = intval($_REQUEST['profile_id']);
}

if($profileId){

    $props = $order->getPropertyCollection();
    $locationProp = $props->getDeliveryLocation();
    $locationName = '';
    if(!$locationProp){
        $locationName = 'BY';
    }else{
        $locationCode = $locationProp->getValue();
        if(strlen($locationCode) == strlen(intval($locationCode))) {
            if ($loc = \Bitrix\Sale\Location\LocationTable::getRowById($locationCode)) {
                $locationCode = $loc['CODE'];
            }
        }
    }

    if($locationCode){
        $res = \Bitrix\Sale\Location\LocationTable::getList(array(
            'filter' => array(
                '=CODE' => $locationCode,
                '=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                '=PARENTS.TYPE.NAME.LANGUAGE_ID' => LANGUAGE_ID,
            ),
            'select' => array(
                'I_ID' => 'PARENTS.ID',
                'I_NAME_LANG' => 'PARENTS.NAME.NAME',
                'I_TYPE_CODE' => 'PARENTS.TYPE.CODE',
                'I_TYPE_NAME_LANG' => 'PARENTS.TYPE.NAME.NAME',
            ),
            'order' => array(
                'PARENTS.DEPTH_LEVEL' => 'asc'
            )
        ));
        while($item = $res->fetch())
        {
            if($item['I_TYPE_CODE'] == 'CITY'){
                $locationName = $item['I_NAME_LANG'];
            }
        }
    }

    if(!$locationName){
        CAdminMessage::ShowMessage(
            array(
                'TYPE'=>'ERROR',
                'MESSAGE'=>Loc::getMessage('AWZ_EUROPOST_ADMIN_PL_ERR_REGION')
            )
        );
    }else{

        global $USER;
        $signer = new Security\Sign\Signer();

        $signedParameters = $signer->sign(base64_encode(serialize(array(
            'address'=>$locationName,
            'profile_id'=>$profileId,
            'user'=>$USER->getId(),
            'page'=>'admin',
            'order'=>$order->getId(),
            's_id'=>bitrix_sessid()
        ))));

    }


    ?>
    <script>
        $(document).ready(function(){
            window.awz_ep_modal.getPickpointsList('<?=$signedParameters?>');
        });
    </script>
    <div style="position:relative;">
        <div id="awz-ep-map" style="width:100%;height:400px;"></div>
    </div>
    <div>
        <form id="awz-europost-send-id-form">
            <input type="hidden" name="sign" value="<?=$signedParameters?>" id="awz-europost-send-id-sign">
            <br><br><?=Loc::getMessage("AWZ_EUROPOST_ID_POSTAMATA")?><input type="text" name="AWZ_EP_POINT_ID" id="AWZ_EP_POINT_ID" value="">
            <?if($_REQUEST['page'] == 'order_edit'){?>
                <p><?=Loc::getMessage('AWZ_EUROPOST_ADMIN_PL_COPY')?></p>

                <a class="awz-europost-send-id adm-btn adm-btn-green adm-btn-add" href="#" onclick="window.awz_ep_modal.setPickpointToOrder();return false;">
                    <?=Loc::getMessage('AWZ_EUROPOST_ADMIN_PL_ORDER_SEND')?> = <?=$order->getId()?></a>
                <p><b><?=Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_OK_ADDR_ATT')?></b></p>

            <?}else{?>
                <a class="awz-europost-send-id adm-btn adm-btn-green adm-btn-add" href="#" onclick="window.awz_ep_modal.setPickpointToOrder();return false;">
                    <?=Loc::getMessage('AWZ_EUROPOST_ADMIN_PL_ORDER_SEND')?> = <?=$order->getId()?></a>
            <?}?>

        </form>
    </div>
    <?php
}else{
    CAdminMessage::ShowMessage(
        array(
            'TYPE'=>'ERROR',
            'MESSAGE'=>Loc::getMessage('AWZ_EUROPOST_ADMIN_PL_ERR_DOST_TYPE')
        )
    );
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");