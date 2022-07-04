<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
$item = $arResult['ITEM'];
?>
<div class="awz-ep-bln-wrap">

<?if($item['name']){?>
    <div class="awz-ep-bln-name"><b><?=$item['name']?></b></div>
<?}?>
<?if($arParams['HIDE_BTN']!='Y'){?>
<div>
    <a href="#" class="awz-ep-select-pvz" data-id="<?=$item['id']?>">
        <?=Loc::getMessage('AWZ_EUROPOST_BALOON_CHOISE')?>
    </a>
</div>
<?}?>

<?if($item['full_address']){?>
    <div><b><?=Loc::getMessage('AWZ_EUROPOST_BALOON_ADR')?></b>: <?=$item['full_address']?></div>
<?}?>

<?if($item['phone']){?>
    <div><b><?=Loc::getMessage('AWZ_EUROPOST_BALOON_PHONE')?></b>: <?=$item['phone']?></div>
<?}?>

<?if($item['info']){?>
    <div><?=$item['info']?></div>
<?}?>

</div>