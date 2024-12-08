<?php
namespace Awz\Europost\Access\Custom;

use Awz\Europost\Access\Permission;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PermissionDictionary
    extends Permission\PermissionDictionary
{
    /*awz.gen start - !!!nodelete*/
	public const MODULE_SETT_VIEW = "96";
	public const MODULE_SETT_EDIT = "97";
	public const MODULE_RIGHT_VIEW = "98";
	public const MODULE_RIGHT_EDIT = "99";
	public const ADMIN_PVZVIEW = "2";
	/*awz.gen end - !!!nodelete*/
}