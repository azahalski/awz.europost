<?php

namespace Awz\Europost;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class PvzTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_europost_pvz';
    }

    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => false,
                'title'=>Loc::getMessage('AWZ_EUROPOST_PVZ_FIELDS_ID')
                )
            ),
            new Entity\StringField('PVZ_ID', array(
              'required' => true,
              'title'=>Loc::getMessage('AWZ_EUROPOST_PVZ_FIELDS_PVZ_ID')
                )
            ),
            new Entity\StringField('TOWN', array(
              'required' => true,
              'title'=>Loc::getMessage('AWZ_EUROPOST_PVZ_FIELDS_TOWN')
                )
            ),
            new Entity\StringField('PRM', array(
                'required' => false,
                'serialized'=>true,
                'title'=>Loc::getMessage('AWZ_EUROPOST_PVZ_FIELDS_PRM')
                )
            ),
        );
    }

    public static function checkPvzFromTown($town){
        $result = new Result();

        $res = self::getList(array('select'=>array('ID'),'filter'=>array('=TOWN'=>$town),'limit'=>1));
        if(!$res->fetch()){
            $result->addError(
                new Error(
                    Loc::getMessage('AWZ_EUROPOST_PVZ_ERR_TOWN',
                        array('#TOWN#'=>htmlspecialcharsEx($town))
                    )
                )
            );
        }
        return $result;
    }

    public static function updatePvz($data){
        if($data['id']){
            $dataBd = self::getPvz($data['id']);
            $hash = md5(serialize($data));
            $data['hash'] = $hash;
            if(!$dataBd){
                self::add(array(
                    'PVZ_ID'=>$data['id'],
                    'TOWN'=>$data['town'],
                    'PRM'=>$data
                ));
            }elseif($data['hash'] != $dataBd['PRM']['hash']){
                self::update(array('ID'=>$dataBd['ID']),array(
                      'PVZ_ID'=>$data['id'],
                      'TOWN'=>$data['town'],
                      'PRM'=>$data
                ));
            }
        }
    }

    public static function getPvz($pvzId){

        $result = self::getList(array(
            'select'=>array('*'),
            'filter'=>array('=PVZ_ID'=>$pvzId),
            'limit'=>1
        ))->fetch();

        return $result;
    }

    public static function deleteAll(){

        $connection = \Bitrix\Main\Application::getConnection();
        $sql = "TRUNCATE TABLE ".self::getTableName().";";
        $connection->queryExecute($sql);

    }

}