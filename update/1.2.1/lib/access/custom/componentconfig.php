<?php
namespace Awz\Europost\Access\Custom;

use Bitrix\Main\Localization\Loc;
use Awz\Europost\Access\Component\ConfigPermissions;
use ReflectionClass;
Loc::loadMessages(__FILE__);

class ComponentConfig extends ConfigPermissions
{
    /*awz.gen start - !!!nodelete*/
	protected const SECTION_MODULE = "MODULE";
	protected const SECTION_ADMIN = "ADMIN";
    /*awz.gen end - !!!nodelete*/

    public const COMPONENT_NAME = 'awz:europost.config.permissions';

    protected function getSections(): array
    {
        return $this->getSectionsFromConst();
    }

    public function getSectionsFromConst(){
        $allItems = [];
        $sectionsRefl = new ReflectionClass($this);
        $permsRefl = new ReflectionClass(PermissionDictionary::class);

        foreach($sectionsRefl->getConstants() as $constName=>$constValue){
            if(substr($constName,0,8)==='SECTION_'){
                $allItems[$constValue] = [];
                foreach($permsRefl->getConstants() as $permName=>$permValue){
                    if(substr($permName,0,strlen($constValue)+1) === $constValue.'_'){
                        $allItems[$constValue][] = $permValue;
                    }
                }
                if(empty($allItems[$constValue]))
                    unset($allItems[$constValue]);
            }
        }
        return $allItems;
    }

}