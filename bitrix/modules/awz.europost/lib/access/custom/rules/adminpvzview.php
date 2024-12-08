<?php
namespace Awz\Europost\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Europost\Access\Custom\PermissionDictionary;
use Awz\Europost\Access\Custom\Helper;

class Adminpvzview extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin())
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::ADMIN_PVZVIEW))
        {
            return true;
        }
        return false;
    }

}