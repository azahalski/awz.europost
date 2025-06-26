<?
$moduleId = "awz.europost";
if(IsModuleInstalled($moduleId)) {
    $updater->CopyFiles(
        "install/js",
        "js/".$moduleId
    );
}