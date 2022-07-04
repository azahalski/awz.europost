<?php
namespace Awz\Europost\Api\Controller;

use Awz\Europost\Handler;
use Awz\Europost\PvzTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Awz\Europost\Api\Filters\Sign;
use Awz\Europost\Helper;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class pickPoints extends Controller
{

    public function configureActions()
    {
        return array(
            'list' => array(
                'prefilters' => array(
                    new Scope(Scope::AJAX),
                    new Sign(array('address','profile_id','page','order','user','s_id'))
                )
            ),
            'baloon' => array(
                'prefilters' => array(
                    new Scope(Scope::AJAX),
                    new Sign(array('address','profile_id','page','order','user','s_id'))
                )
            ),
            'setorder' => array(
                'prefilters' => array(
                    new Scope(Scope::AJAX),
                    new Sign(array('address','profile_id','page','order','user','s_id'))
                )
            )
        );
    }

    public function setorderAction($address = '', $geo_id = '', $profile_id = '', $page = '', $user = '', $order='', $point=''){

        \Bitrix\Main\Loader::includeModule('sale');

        if(!$user || !$order || !$point || !$page){
            $this->addError(
                new Error(
                    Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_ERR_REQ'),
                    100
                )
            );
            return null;
        }

        $orderOb = \Bitrix\Sale\Order::load($order);
        $propertyCollection = $orderOb->getPropertyCollection();
        $res = null;

        if(!$profile_id){
            $profile_id = Helper::getProfileId($orderOb, Helper::DOST_TYPE_PVZ);
        }

        $pointData = PvzTable::getPvz($point);
        if(!$pointData){
            $this->addError(
                new Error(
                    Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_ERR_POINT_DATA'),
                    100
                )
            );
            return null;
        }
        $addressPvz = Helper::formatPvzAddress($profile_id, $pointData);

        $isSet = false;
        $addMessAddress = '';
        foreach($propertyCollection as $prop){
            if($prop->getField('CODE') == Helper::getPropPvzCode($profile_id)){
                $prop->setValue($point);
                $isSet = true;
            }elseif($addressPvz && ($prop->getField('CODE') == Helper::getPropAddress($profile_id))){
                $prop->setValue($addressPvz);
                $isSet = true;
                $addMessAddress .= ', '.Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_OK_ADDR_ADD', array('#PROP#'=>$prop->getField('CODE')));
            }
        }
        if($isSet){
            $res = $orderOb->save();
        }
        if(!$res){
            $this->addError(
                new Error(
                    Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_ERR_PROP'), 100
                )
            );
            return null;
        }else{
            if($res->isSuccess()){
                return Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_OK_ADDR',
                    array("#POINT#"=>$point, "#PROP#"=>Helper::getPropPvzCode($profile_id))
                ).$addMessAddress;
            }else{
                $this->addErrors($res->getErrors());
                return null;
            }

        }

    }
    public function baloonAction($s_id='', $address = '', $profile_id = '', $page = '', $id = '')
    {
        if(!$id){
            $this->addError(
                new Error(Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_ID_ERR'), 100)
            );
            return null;
        }
        if(bitrix_sessid() != $s_id){
            $this->addError(
                new Error(Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_SESS_ERR'), 100)
            );
            return null;
        }

        $hideBtn = ($page === 'pvz-edit') ? true : false;

        $bResult = Helper::getBaloonHtml($id, $hideBtn);
        if(!$bResult->isSuccess()){
            $this->addErrors($bResult->getErrors());
            return null;
        }
        $resultData = $bResult->getData();

        return $resultData['html'];
    }

    public function listAction($address = '', $profile_id = '', $page = '')
    {

        if(!$profile_id){
            $this->addError(
                new Error(Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_PROFILE_ERR'), 100)
            );
            return null;
        }
        if(!$address){
            $this->addError(
                new Error(Loc::getMessage('AWZ_EUROPOST_API_CONTROL_PICKPOINTS_ADDRGEO_ERR'), 100)
            );
            return null;
        }

        $items = array();

        $resPvz = PvzTable::getList(array(
            'select'=>array('*'),
            'filter'=>array('=TOWN'=>$address)
        ));

        while($point = $resPvz->fetch()){
            $items[] = array(
                'id'=>$point['PVZ_ID'],
                'position'=>array(
                    'longitude'=>$point['PRM']['longitude'],
                    'latitude'=>$point['PRM']['latitude']
                ),
            );
        }


        return array(
            'page'=>$page,
            'address' => $address,
            'profile_id' => $profile_id,
            'items' => $items
        );
    }
}