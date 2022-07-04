<?php
namespace Awz\Europost;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class zApi {

    /**
     * Точка входа api Сервиса доставки (боевой режим)
     */
    const URL = 'https://dostavka.zahalski.dev/api/epost';

    /**
     * Папка для кеша в /bitrix/cache/
     */
    const CACHE_DIR = '/awz/europost/';

    private $token = null;
    private static $_instance = null;

    /**
     * Сохраняется последний ответ api с метода send
     * Может быть пустым в случае ответа с кеша
     * @var null|HttpClient
     */
    private $lastResponse = null;

    private $cacheParams = array();

    private $standartJson = false;

    private function __construct($params=array())
    {
        if($params['token']) $this->token = $params['token'];
    }

    public static function getInstance($params=array())
    {
        if(is_null(self::$_instance)){
            self::$_instance = new self($params);
        }
        return self::$_instance;
    }

    /**
     * очистка параметров для кеша
     * должна вызываться после любого запроса через кеш
     */
    public function clearCacheParams(){
        $this->cacheParams = array();
    }

    /**
     * параметры для кеша результата запроса
     *
     * @param $cacheId ид кеша
     * @param $ttl время действия в секундах
     */
    public function setCacheParams($cacheId, $ttl){
        $this->cacheParams = array(
            'id'=>$cacheId,
            'ttl'=>$ttl
        );
    }

    /**
     * Получает текущий токен
     *
     * @return string|null
     */
    public function getToken(){
        return $this->token;
    }

    /**
     * Установка токена
     *
     * @param $token
     */
    public function setToken(string $token){
        $this->token = $token;
    }


    public function getPvz($data = array()){
        return $this->send('pickpoints?type=all', $data, 'get');
    }

    /**
     * Запросы к апи логистической платформы
     *
     * @param $method метод апи
     * @param array $data параметры запроса
     * @param string $type post или get
     * @return Result
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function send($method, $data = array(), $type='post'){

        $url = self::URL;
        $url .= '/'.$method;

        $res = null;
        $obCache = null;

        if(!empty($this->cacheParams)){
            $obCache = Cache::createInstance();
            if( $obCache->initCache($this->cacheParams['ttl'],$this->cacheParams['id'],self::CACHE_DIR) ){
                $res = $obCache->getVars();
            }
            $this->clearCacheParams();
        }
        $httpClient = null;
        if(!$res){
            $httpClient = new HttpClient();
            $httpClient->disableSslVerification();
            if($this->getToken()){
                $httpClient->setHeaders(array(
                    'Authorization'=>'Bearer '.$this->getToken(),
                ));
            }
            if($type == 'get'){
                $res = $httpClient->get($url);
            }else{
                $res = $httpClient->post($url, $data);
            }
            $this->setLastResponse($httpClient);
        }else{
            $this->setLastResponse($httpClient, 'cache');
        }

        $result = new Result();
        if(!$res){
            $result->addError(
                new Error(Loc::getMessage('AWZ_EUROPOST_YDAPI_RESPERROR'))
            );
        }else{
            try {
                if($this->standartJson){
                    $json = json_decode($res, true);
                }else{
                    $json = Json::decode($res);
                }
                /*
                 * error -> array('code'=>'str', 'message'=>'str')
                 * */
                if(isset($json['status']) && $json['status']=='error'){
                    foreach($json['errors'] as $error){
                        $result->addError(
                            new Error($error['message'], $error['code'])
                        );
                    }
                }elseif(!isset($json['status']) || ($json['status'] != 'success')){
                    $result->addError(
                        new Error('Error params')
                    );
                }
                $result->setData(array('result'=>$json));

            }catch (\Exception  $ex){
                $result->addError(
                    new Error($ex->getMessage(), $ex->getCode())
                );
            }
        }

        if($result->isSuccess() && $this->lastResponse){
            if($obCache){
                if($obCache->startDataCache()){
                    $obCache->endDataCache($res);
                }
            }
        }

        return $result;

    }

    /**
     * Получение последнего запроса
     *
     * @return null|HttpClient
     */
    public function getLastResponse(){
        return $this->lastResponse;
    }

    /**
     * Запись последнего запроса
     *
     * @param null $resp
     * @param string $type
     * @return HttpClient|null
     */
    private function setLastResponse($resp = null, $type=''){
        if($resp && !($resp instanceof HttpClient)){
            $resp = null;
        }
        $this->lastResponse = $resp;
        return $this->lastResponse;
    }

    public function setStandartJson($val){
        $this->standartJson = $val;
    }
}