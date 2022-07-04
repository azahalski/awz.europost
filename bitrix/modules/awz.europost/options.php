<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.europost";
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
$zr = "";
if (! ($MODULE_RIGHT >= "R"))
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage('AWZ_EUROPOST_OPT_TITLE'));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

\Bitrix\Main\Loader::includeModule($module_id);

$startProfile = intval($_REQUEST['profile']);
$startCode = preg_replace('/([^0-9A-z_])/is','',$_REQUEST['code']);

$deliveryProfileList = \Awz\Europost\Helper::getActiveProfileIds();

$minMode = ($startProfile && $startCode);
if ($_SERVER["REQUEST_METHOD"] == "POST" && $MODULE_RIGHT == "W" && strlen($_REQUEST["Update"]) > 0 && (check_bitrix_sessid() || $minMode))
{
    $sendRunAgent = false;
    if($_REQUEST['DELETE_PVZ']=='Y'){
        \Awz\Europost\PvzTable::deleteAll();
        CAdminMessage::ShowMessage(array('TYPE'=>'OK',
            'MESSAGE'=>Loc::getMessage('AWZ_EUROPOST_OPT_MESS1')));
        $sendRunAgent = true;
    }
    if($_REQUEST['UPDATE_PVZ']=='Y'){
        \Awz\Europost\Checker::agentGetPickpoints();
        CAdminMessage::ShowMessage(array('TYPE'=>'OK',
            'MESSAGE'=>Loc::getMessage('AWZ_EUROPOST_OPT_MESS2')));
        $sendRunAgent = true;
    }

    Option::set($module_id, "MAP_ADDRESS", trim($_REQUEST["MAP_ADDRESS"]), "");

    foreach($deliveryProfileList as $profileId=>$profileName){
        Option::set($module_id, "PVZ_CODE_".$profileId, trim($_REQUEST["PVZ_CODE_".$profileId]), "");
        Option::set($module_id, "PVZ_ADDRESS_".$profileId, trim($_REQUEST["PVZ_ADDRESS_".$profileId]), "");
        Option::set($module_id, "PVZ_ADDRESS_TMPL_".$profileId, trim($_REQUEST["PVZ_ADDRESS_TMPL_".$profileId]), "");
    }
}

$aTabs = array();

$aTabs[] = array(
    "DIV" => "edit1",
    "TAB" => Loc::getMessage('AWZ_EUROPOST_OPT_SECT1'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_EUROPOST_OPT_SECT1')
);

$cnt = 1;
foreach($deliveryProfileList as $profileId=>$profileName){
    $cnt++;
    $aTabs[] = array(
        "DIV" => "edit".$cnt,
        "TAB" => Loc::getMessage('AWZ_EUROPOST_OPT_SECT2',array('#PROFILE_NAME#'=>'['.$profileId.'] - '.$profileName)),
        "ICON" => "vote_settings",
        "TITLE" => Loc::getMessage('AWZ_EUROPOST_OPT_SECT2',array('#PROFILE_NAME#'=>'['.$profileId.'] - '.$profileName))
    );
}
$cnt++;
$aTabs[] = array(
    "DIV" => "edit" . $cnt,
    "TAB" => Loc::getMessage('AWZ_EUROPOST_OPT_SECT3'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_EUROPOST_OPT_SECT3')
);
//echo'<pre>';print_r($aTabs);echo'</pre>';
$tabControl = new \CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>&mid_menu=1" id="FORMACTION">

<?
$tabControl->BeginNextTab();
?>

<tr>
    <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_MAP_ADRESS')?></td>
    <td>
        <?$val = Option::get($module_id, "MAP_ADDRESS", "N","");?>
        <input type="checkbox" value="Y" name="MAP_ADDRESS" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<tr class="heading">
    <td colspan="2">
        <?=Loc::getMessage('AWZ_EUROPOST_OPT_L_GROUP1')?>
    </td>
</tr>
<tr>
    <td colspan="2" align="center">
        <div class="adm-info-message-wrap">
            <div class="adm-info-message">
                <div> <?=Loc::getMessage('AWZ_EUROPOST_OPT_L_GROUP1_DESC')?></div>
            </div>
        </div>
    </td>
</tr>
<tr>
    <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_DELPVZ')?></td>
    <td>
        <?$val = "N";?>
        <input type="checkbox" value="Y" name="DELETE_PVZ" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<tr>
    <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_UPPVZ')?></td>
    <td>
        <?$val = "N";?>
        <input type="checkbox" value="Y" name="UPDATE_PVZ" <?if ($val=="Y") echo "checked";?>></td>
</tr>


<?foreach($deliveryProfileList as $profileId=>$profileName){
    $tabControl->BeginNextTab();

    $pvzList = \Awz\Europost\Helper::getActiveProfileIds(\Awz\Europost\Helper::DOST_TYPE_PVZ);
    $isPvz = isset($pvzList[$profileId]) ? true : false;

    ?>

        <tr class="heading">
            <td colspan="2">
                <?=Loc::getMessage('AWZ_EUROPOST_OPT_L_PROFILE_PROP')?>
            </td>
        </tr>

        <?if($isPvz){?>
            <tr>
                <td><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_PROPPVZ')?></td>
                <td>
                    <?$val = Option::get($module_id, "PVZ_CODE_".$profileId, "AWZ_YD_POINT_ID", "");?>
                    <input type="text" size="35" maxlength="255" value="<?=$val?>" name="PVZ_CODE_<?=$profileId?>"/>
                </td>
            </tr>

            <tr>
                <td><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_PROPPVZ_ADR')?></td>
                <td>
                    <?$val = Option::get($module_id, "PVZ_ADDRESS_".$profileId, "PVZ_ADDRESS", "");?>
                    <input type="text" size="35" maxlength="255" value="<?=$val?>" name="PVZ_ADDRESS_<?=$profileId?>"/>
                </td>
            </tr>
            <tr>
                <td><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_PROPPVZ_ADR_TMPL')?></td>
                <td>
                    <?$val = Option::get($module_id, "PVZ_ADDRESS_TMPL_".$profileId, "#NAME#", "");?>
                    <input type="text" size="35" maxlength="255" value="<?=$val?>" name="PVZ_ADDRESS_TMPL_<?=$profileId?>"/>
                    <p><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_PROPPVZ_ADR_TMPL_DESC')?></p>
                </td>
            </tr>
        <?}?>


<?}?>
<?
$tabControl->BeginNextTab();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
?>

<?
$tabControl->Buttons();
?>
<input <?if ($MODULE_RIGHT<"W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_EUROPOST_OPT_L_BTN_SAVE')?>" />
<input type="hidden" name="Update" value="Y" />
<input type="hidden" name="IFRAME_TYPE" value="<?=preg_replace('/([^0-9A-z_])/is','',$_REQUEST['IFRAME_TYPE'])?>">
<input type="hidden" name="IFRAME" value="<?=preg_replace('/([^0-9A-z_])/is','',$_REQUEST['IFRAME'])?>">
<input type="hidden" name="profile" value="<?=$startProfile?>">
<input type="hidden" name="code" value="<?=$startCode?>">
<?$tabControl->End();?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");