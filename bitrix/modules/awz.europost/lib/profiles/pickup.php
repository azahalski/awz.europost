<?php
namespace Awz\Europost\Profiles;

use Awz\Europost\Handler;
use Awz\Europost\Helper;
use Awz\Europost\PvzTable;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security;

Loc::loadMessages(__FILE__);

class Pickup extends \Bitrix\Sale\Delivery\Services\Base
{
    protected static $isProfile = true;
    protected static $parent = null;

    public function __construct(array $initParams)
    {
        if(empty($initParams["PARENT_ID"]))
            throw new \Bitrix\Main\ArgumentNullException('initParams[PARENT_ID]');
        parent::__construct($initParams);
        $this->parent = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($this->parentId);
        if(!($this->parent instanceof Handler))
            throw new ArgumentNullException('parent is not instance of \Awz\Europost\Handler');
        if(isset($initParams['PROFILE_ID']) && intval($initParams['PROFILE_ID']) > 0)
            $this->serviceType = intval($initParams['PROFILE_ID']);
    }

    public static function getClassTitle()
    {
        return Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_NAME');
    }

    public static function getClassDescription()
    {
        return Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_DESC');
    }

    public function getParentService()
    {
        return $this->parent;
    }

    public function isCalculatePriceImmediately()
    {
        return $this->getParentService()->isCalculatePriceImmediately();
    }

    public static function isProfile()
    {
        return self::$isProfile;
    }

    public function isCompatible(\Bitrix\Sale\Shipment $shipment)
    {
        $calcResult = self::calculateConcrete($shipment);
        return $calcResult->isSuccess();
    }

    protected function getConfigStructure()
    {
        $result = array(
            "MAIN" => array(
                'TITLE' => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_SETT_INTG'),
                'DESCRIPTION' => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_SETT_INTG_DESC'),
                'ITEMS' => array(
                    'BTN_CLASS' => array(
                        'TYPE' => 'STRING',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_SETT_BTN_CLASS'),
                        "DEFAULT" => 'btn btn-primary'
                    ),
                    'WEIGHT_DEFAULT' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_SETT_WEIGHT_DEF'),
                        "DEFAULT" => '3000'
                    ),
                )
            ),
            "TARIFS"=>array(
                'TITLE' => 'Тарифы',
                'DESCRIPTION' => 'Тарифы на доставку',
                'ITEMS' => array(
                    'TARIF_1' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 0,01 до 2 кг.',
                        "DEFAULT" => '2.99'
                    ),
                    'TARIF_2' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 2,01 до 10 кг.',
                        "DEFAULT" => '3.99'
                    ),
                    'TARIF_3' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 10,01 до 20 кг.',
                        "DEFAULT" => '5.99'
                    ),
                    'TARIF_4' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 20,01 до 30',
                        "DEFAULT" => '8.49'
                    ),
                    'TARIF_5' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 30,01 до 35',
                        "DEFAULT" => '23.99'
                    ),
                    'TARIF_6' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 35,01 до 40',
                        "DEFAULT" => '24.99'
                    ),
                    'TARIF_7' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 40,01 до 45',
                        "DEFAULT" => '28.99'
                    ),
                    'TARIF_8' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => 'от 45,01 до 50',
                        "DEFAULT" => '30.99'
                    ),
                )
            )
        );
        return $result;
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment = null)
    {

        $config = $this->getConfigValues();

        $result = new \Bitrix\Sale\Delivery\CalculationResult();

        $weight = $shipment->getWeight();
        if(!$weight) $weight = $config['MAIN']['WEIGHT_DEFAULT'];

        $order = $shipment->getCollection()->getOrder();
        $props = $order->getPropertyCollection();
        $locationCode = $props->getDeliveryLocation()->getValue();
        if(strlen($locationCode) == strlen(intval($locationCode))){
            if ($loc = \Bitrix\Sale\Location\LocationTable::getRowById($locationCode)) {
                $locationCode = $loc['CODE'];
            }
        }
        $locationName = '';

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

        //$api = Helper::getApi();
        //$resPvz = $api->getPvz();
        //echo '<pre>';print_r($resPvz);echo'</pre>';

        if(!$locationName){
            $result->addError(new \Bitrix\Main\Error(Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_ERR_REGION')));
            return $result;
        }

        $rCheck = PvzTable::checkPvzFromTown($locationName);
        if($rCheck->isSuccess()){

            $price = 500;

            $result->setDeliveryPrice(
                roundEx(
                    $price,
                    SALE_VALUE_PRECISION
                )
            );

            $pointId = false;
            $pointHtml = '';
            $request = Context::getCurrent()->getRequest();
            if($request->get('AWZ_EP_POINT_ID')){
                $pointId = preg_replace('/([^0-9A-z\-])/is', '', $request->get('AWZ_EP_POINT_ID'));
            }
            if($pointId){
                $blnRes = Helper::getBaloonHtml($pointId, true);
                if($blnRes->isSuccess()){
                    $blnData = $blnRes->getData();
                    $pointHtml = $blnData['html'];
                }
            }

            $signer = new Security\Sign\Signer();

            $signedParameters = $signer->sign(base64_encode(serialize(array(
                'address'=>$locationName,
                'profile_id'=>$this->getId(),
                's_id'=>bitrix_sessid()
            ))));

            $buttonHtml = '<a id="AWZ_EP_POINT_LINK" class="'.$config['MAIN']['BTN_CLASS'].'" href="#" onclick="window.awz_ep_modal.show(\''.Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_BTN_OPEN').'\',\''.$signedParameters.'\');return false;">'.Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_BTN_OPEN').'</a><div id="AWZ_EP_POINT_INFO">'.$pointHtml.'</div>';
            $result->setDescription($result->getDescription().
                '<!--btn-awz-ed-start-->'.
                $buttonHtml
                .'<!--btn-awz-ed-end-->'
            );

        }else{
            foreach ($rCheck->getErrors() as $error) {
                $result->addError($error);
            }
        }


        return $result;

    }

    public static function onBeforeAdd(array &$fields = array()): \Bitrix\Main\Result
    {
        if(!$fields['LOGOTIP']){
            $fields['LOGOTIP'] = Handler::getLogo();
        }
        return new \Bitrix\Main\Result();
    }

    public static function onAfterAdd($serviceId, array $fields = array())
    {
        Application::getInstance()->addBackgroundJob(
            array("\\Awz\\Europost\\Checker", "agentGetPickpoints"),
            array(),
            \Bitrix\Main\Application::JOB_PRIORITY_NORMAL
        );
        return true;
    }
}