<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\UI\Extension;
use Awz\Europost\Access\AccessController;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.europost";
if(!Loader::includeModule($module_id)) return;
Extension::load('ui.sidepanel-content');
$request = Application::getInstance()->getContext()->getRequest();
$APPLICATION->SetTitle(Loc::getMessage('AWZ_EUROPOST_OPT_TITLE'));

if($request->get('IFRAME_TYPE')==='SIDE_SLIDER'){
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
    require_once('lib/access/include/moduleright.php');
    CMain::finalActions();
    die();
}

if(!AccessController::isViewSettings())
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$startProfile = intval($_REQUEST['profile']);
$startCode = preg_replace('/([^0-9A-Za-z_])/is','',$_REQUEST['code']);

$deliveryProfileList = \Awz\Europost\Helper::getActiveProfileIds();

$minMode = ($startProfile && $startCode);
if ($request->getRequestMethod()==='POST' && AccessController::isEditSettings() && $request->get('Update'))
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
    Option::set($module_id, "yandex_map_api_key", trim($_REQUEST["yandex_map_api_key"]), "");
    Option::set($module_id, "yandex_map_suggest_api_key", trim($_REQUEST["yandex_map_suggest_api_key"]), "");

    $townsPrep = [];
    try{
        $townsPrep = unserialize(Option::get('awz.europost', "REPL_TOWNS", "",""), ['allowed_classes' => false]);
    }catch (\Exception $e){
        $townsPrep = [];
    }
    if(!is_array($townsPrep)) $townsPrep = [];
    if(isset($_REQUEST['REPL_TOWNS']) && is_array($_REQUEST['REPL_TOWNS'])){
        foreach($_REQUEST['REPL_TOWNS'] as $key=>$v){
            if(is_string($v) && $v){
                $townsPrep[$key] = trim($v);
            }else{
                unset($townsPrep[$key]);
            }
        }
    }
    Option::set($module_id, "REPL_TOWNS", serialize($townsPrep), "");

    foreach($deliveryProfileList as $profileId=>$profileName){
        Option::set($module_id, "PVZ_CODE_".$profileId, trim($_REQUEST["PVZ_CODE_".$profileId]), "");
        Option::set($module_id, "PVZ_ADDRESS_".$profileId, trim($_REQUEST["PVZ_ADDRESS_".$profileId]), "");
        Option::set($module_id, "PVZ_ADDRESS_TMPL_".$profileId, trim($_REQUEST["PVZ_ADDRESS_TMPL_".$profileId]), "");
        Option::set($module_id, "SHOW_ALL_PVZ_".$profileId, $_REQUEST["SHOW_ALL_PVZ_".$profileId]=='Y' ? "Y" : "N", "");
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
$saveUrl = $APPLICATION->GetCurPage(false).'?mid='.htmlspecialcharsbx($module_id).'&lang='.LANGUAGE_ID.'&mid_menu=1';
$tabControl = new \CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>
<style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
<form method="POST" action="<?=$saveUrl?>" id="FORMACTION">

<?
$tabControl->BeginNextTab();
?>

<tr>
    <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_MAP_ADRESS')?><br>
        <a href="https://developer.tech.yandex.ru/services" target="_blank">
            <?=Loc::getMessage('AWZ_EUROPOST_OPT_MAP_ADRESS_DESC')?>
        </a>
    </td>
    <td>
        <?$val = Option::get($module_id, "MAP_ADDRESS", "N","");?>
        <input type="checkbox" value="Y" name="MAP_ADDRESS" <?if ($val=="Y") echo "checked";?>>
    </td>
</tr>
<tr>
    <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_MAP_ADRESS_KEY1')?></td>
    <td>
        <?$val = Option::get($module_id, "yandex_map_api_key", "","");?>
        <input type="text" value="<?=$val?>" name="yandex_map_api_key">
    </td>
</tr>
    <tr>
    <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_MAP_ADRESS_KEY2')?></td>
    <td>
        <?$val = Option::get($module_id, "yandex_map_suggest_api_key", "","");?>
        <input type="text" value="<?=$val?>" name="yandex_map_suggest_api_key">
    </td>
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
    <tr class="heading">
        <td colspan="2">
            <?=Loc::getMessage('AWZ_EUROPOST_OPT_L_SOOT')?></td>
    </tr>
<tr>
    <td colspan="2" width="100%">
        <?
        $towns = [];
        try{
            $towns = unserialize(Option::get($module_id, "REPL_TOWNS", "",""), ['allowed_classes' => false]);
        }catch (\Exception $e){
            $towns = [];
        }
        if(!is_array($towns)) $towns = [];
        $allTowns = \Awz\Europost\PvzTable::getList([
            'select'=>['TOWN'],
            'group'=>['TOWN']
        ]);
        ?>
        <table style="margin:auto;">
            <tr>
                <th style="text-align: left;"><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_SOOT_LBL1')?></th>
                <th style="text-align: left;"><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_SOOT_LBL2')?></th>
            </tr>
        <?
        $keyIssets = [];
        while($townName = $allTowns->fetch()){
            $key = md5(trim($townName['TOWN']));
            $keyIssets[] = $key;
            ?>
            <tr>
                <td><?=$townName['TOWN']?></td>
                <td><input type="text" name="REPL_TOWNS[<?=$key?>]" value="<?=$towns[$key]?>"></td>
            </tr>
            <?
        }
        foreach($towns as $k=>$v){
            if(!in_array($k, $keyIssets)){
                ?>
                <tr>
                    <td><?=$k?></td>
                    <td><input type="text" name="REPL_TOWNS[<?=$k?>]" value="<?=$v?>"></td>
                </tr>
                <?
            }
        }
        ?>
        </table>
   </td>
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
        <tr>
            <td width="50%"><?=Loc::getMessage('AWZ_EUROPOST_OPT_L_PROPPVZ_SHOW_ALL')?>
            </td>
            <td>
                <?$val = Option::get($module_id, "SHOW_ALL_PVZ_".$profileId, "Y","");?>
                <input type="checkbox" value="Y" name="SHOW_ALL_PVZ_<?=$profileId?>" <?if ($val=="Y") echo "checked";?>>
            </td>
        </tr>
        <?}?>


<?}?>


<?
$tabControl->Buttons();
?>
<input <?if (!AccessController::isEditSettings()) echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_EUROPOST_OPT_L_BTN_SAVE')?>" />
<input type="hidden" name="Update" value="Y" />
<input type="hidden" name="profile" value="<?=htmlspecialcharsEx($startProfile)?>">
<input type="hidden" name="code" value="<?=htmlspecialcharsEx($startCode)?>">
    <?if(AccessController::isViewRight()){?>
        <button class="adm-header-btn adm-security-btn" onclick="BX.SidePanel.Instance.open('<?=$saveUrl?>');return false;">
            <?=Loc::getMessage('AWZ_EUROPOST_OPT_SECT3')?>
        </button>
    <?}?>
<?$tabControl->End();?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");