<?php
namespace Awz\Europost;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Application;

class Checker {

    public static function runJob($points){
        if(!is_array($points)) return;
        foreach($points as $point){
            PvzTable::updatePvz($point);
        }
    }

    public static function agentGetPickpoints(){

		$isUtf8 = Application::getInstance()->isUtfMode();

        $api = Helper::getApi();
        if (!$isUtf8){
            $api->setStandartJson(true);
        }
        $pointRes = $api->getPvz();
        if (!$isUtf8){
            $api->setStandartJson(false);
        }
        if($pointRes->isSuccess()){
            $pointsData = $pointRes->getData();
            if(isset($pointsData['result']['data']['points'])){
                foreach($pointsData['result']['data']['points'] as $point){
                    if (!$isUtf8){
                        $point = Json::decode(json_encode($point));
                    }
                    PvzTable::updatePvz($point);
                }
            }
        }

        return "\\Awz\\Europost\\Checker::agentGetPickpoints();";

    }

}