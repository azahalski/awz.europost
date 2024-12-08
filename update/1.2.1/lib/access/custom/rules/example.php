<?php
namespace Awz\Europost\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Europost\Access\Custom\PermissionDictionary;
use Awz\Europost\Access\Custom\Helper;

class Example extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin() && !Helper::ADMIN_DECLINE)
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::MODULE_SETT_VIEW))
        {
            return true;
        }
        return false;
    }

}