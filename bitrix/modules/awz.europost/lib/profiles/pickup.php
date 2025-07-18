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
    protected $parent = null;

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
                'TITLE' => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIFS'),
                'DESCRIPTION' => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIFS_DESC'),
                'ITEMS' => array(
                    'TARIF_1' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_1'),
                        "DEFAULT" => '3.16'
                    ),
                    'TARIF_1_2' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_1_2'),
                        "DEFAULT" => '3.33'
                    ),
                    'TARIF_2' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_2'),
                        "DEFAULT" => '4.16'
                    ),
                    'TARIF_2_2' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_2_2'),
                        "DEFAULT" => '5.41'
                    ),
                    'TARIF_3' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_3'),
                        "DEFAULT" => '7.16'
                    ),
                    'TARIF_3_2' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_3_2'),
                        "DEFAULT" => '8.58'
                    ),
                    'TARIF_4' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_4'),
                        "DEFAULT" => '10.25'
                    ),
                    'TARIF_4_2' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_4_2'),
                        "DEFAULT" => '12.08'
                    ),
                    'TARIF_5' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_5'),
                        "DEFAULT" => '16.66'
                    ),
                    'TARIF_6' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_6'),
                        "DEFAULT" => '20.83'
                    ),
                    'TARIF_7' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_7'),
                        "DEFAULT" => '23.33'
                    ),
                    'TARIF_8' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_8'),
                        "DEFAULT" => '24.99'
                    ),
                    'TARIF_NDS1' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_NDS1'),
                        "DEFAULT" => 'N'
                    ),
                    'TARIF_NP' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_NP'),
                        "DEFAULT" => 'N'
                    ),
                    'TARIF_NDS2' => array(
                        'TYPE' => 'Y/N',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_NDS2'),
                        "DEFAULT" => 'N'
                    ),
                    'TARIF_ADD' => array(
                        'TYPE' => 'NUMBER',
                        "NAME" => Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIF_ADD'),
                        "DEFAULT" => '0.00'
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

        /* @var \Bitrix\Sale\Order $order */
        $order = $shipment->getCollection()->getOrder();
        $props = $order->getPropertyCollection();
        $locationProp = $props->getDeliveryLocation();
        $locationName = '';
        if(!$locationProp){
            $locationName = 'BY';
        }else{
            $locationCode = $locationProp->getValue();
            if(strlen($locationCode) == strlen(intval($locationCode))){
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
        if(!$locationName) $locationName = 'BY';


        //$api = Helper::getApi();
        //$resPvz = $api->getPvz();
        //echo '<pre>';print_r($resPvz);echo'</pre>';

        if(!$locationName){
            $result->addError(new \Bitrix\Main\Error(Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_ERR_REGION')));
            return $result;
        }

        $rCheck = PvzTable::checkPvzFromTown($locationName);
        if($rCheck->isSuccess()){

            $price = false;

            if($weight == 0){

            }elseif($weight<1000){
                $price = round((float)$config['TARIFS']['TARIF_1'],2);
            }elseif($weight<2000){
                $price = round((float)$config['TARIFS']['TARIF_1_2'],2);
            }elseif($weight<5000){
                $price = round((float)$config['TARIFS']['TARIF_2'],2);
            }elseif($weight<10000){
                $price = round((float)$config['TARIFS']['TARIF_2_2'],2);
            }elseif($weight<15000){
                $price = round((float)$config['TARIFS']['TARIF_3'],2);
            }elseif($weight<20000){
                $price = round((float)$config['TARIFS']['TARIF_3_2'],2);
            }elseif($weight<25000){
                $price = round((float)$config['TARIFS']['TARIF_4'],2);
            }elseif($weight<30000){
                $price = round((float)$config['TARIFS']['TARIF_4_2'],2);
            }elseif($weight<35000){
                $price = round((float)$config['TARIFS']['TARIF_5'],2);
            }elseif($weight<40000){
                $price = round((float)$config['TARIFS']['TARIF_6'],2);
            }elseif($weight<45000){
                $price = round((float)$config['TARIFS']['TARIF_7'],2);
            }elseif($weight<50000){
                $price = round((float)$config['TARIFS']['TARIF_8'],2);
            }

            if($config['TARIFS']['TARIF_NDS1']==='Y' && $price){
                $price = round($price + $price*0.2, 2);
            }

            if($config['TARIFS']['TARIF_NP']==='Y'){
                $priceOrder = $order->getPrice();
                if($priceOrder <= 300){
                    $price2 = round($priceOrder*1.0/100, 2);
                }else{
                    $price2 = round($priceOrder*1.5/100, 2);
                }
                if($config['TARIFS']['TARIF_NDS2']==='Y'){
                    $price2 = round($price2 + $price2*0.2, 2);
                }
                $price = $price + $price2;
            }

            if($config['TARIFS']['TARIF_ADD'] && $price){
                $price = $price + $config['TARIFS']['TARIF_ADD'];
            }

            if($price === false){
                $result->addError(new \Bitrix\Main\Error(
                    Loc::getMessage('AWZ_EUROPOST_PROFILE_PICKUP_TARIFS_ERR')
                ));
                return $result;
            }

            $result->setDeliveryPrice(
                roundEx(
                    $price,
                    SALE_VALUE_PRECISION
                )
            );

            $pointId = false;
			
			foreach($props as $prop){
				if($prop->getField('CODE') == Helper::getPropPvzCode($this->getId())){
					if($prop->getValue()){
						$pointId = $prop->getValue();
					}
				}
			}
			
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