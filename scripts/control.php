<?php
    require 'datainit.php';

    function init() {
        loadAttrs(true);
        loadObjs(true);
        $GLOBALS['questionCount'] = 0;
        storeData();
        $tempIndex = fopen('data/qIndex.temp','w');
        fwrite($tempIndex,$GLOBALS['questionCount']);
        fclose($tempIndex);
    }

    function loadTemp($testFile) {
        fclose($testFile);
        loadAttrs();
        loadObjs();
        $GLOBALS['questionCount'] = file_get_contents("data/qIndex.temp");
    }

    function storeTempAttr($tempAttr) {
        $tempAttrFile = fopen('data/a.temp','w');
        $tempAttr = json_encode($tempAttr);
        fwrite($tempAttrFile,$tempAttr);
        fclose($tempAttrFile);
    }

    function loadTempAttr() {
        $tempAttr = file_get_contents('data/a.temp');
        $tempAttr = json_decode($tempAttr);
        return $tempAttr;
    }

    function storeTempMod() {
        $tempModFile = fopen('data/m.temp','w');
        $tempMod = json_encode($GLOBALS['modObj']);
        fwrite($tempModFile,$tempMod);
        fclose($tempModFile);
    }

    function loadTempMod() {
        $tempMod = file_get_contents('data/m.temp');
        $GLOBALS['modObj'] = json_decode($tempMod);
    }

    function clearTemp() {
        unlink('data/a.temp');
        unlink('data/m.temp');
        unlink('data/attrIndex.temp');
        unlink('data/qIndex.temp');
        unlink('oInfo.temp');
    }

    if ($idFile = fopen("data/attrIndex.temp","r")) loadTemp($idFile);
    else init();


    $type = $_GET["type"];

    if ($type == "start") {
        $thisAttr = pickAttr();
        $resJSON = sendAttr($thisAttr);
        storeTempAttr($thisAttr);
        dropAttr($thisAttr);
        storeData();
        echo $resJSON;
    }

    if ($type == "answer") {
        $ans = $_GET["ans"];
        $lastAttr = loadTempAttr();
        loadTempMod();
        $backupObj = array_rand($GLOBALS['objMap'],1); // prepare a random-picked result
        filterObj($lastAttr,$ans);
        recordObj($lastAttr,$ans);
        storeTempMod();
        if (count($GLOBALS['objMap']) == 1) {
            reset($GLOBALS['objMap']);
            $resJSON = verifyObj();
        }
        elseif ( (count($GLOBALS['objMap']) == 0) or ($GLOBALS['questionCount'] == 19) ) {
            $resJSON = verifyObj($backupObj);
        }
        else {
            $thisAttr = pickAttr();
            $resJSON = sendAttr($thisAttr);
            storeTempAttr($thisAttr);
            dropAttr($thisAttr);
        }
        storeData();
        echo $resJSON;
    }

    if ($type == "check") {
        $ans = $_GET["ans"];
        if ($ans) {
            clearTemp();
            verifyObj('success!!!');
        }
        else {
            clearTemp();
            verifyObj('Tried best');
        }

    }

