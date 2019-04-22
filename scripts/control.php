<?php
    require 'datainit.php';

    function init() {
        loadAttrs(true);
        loadObjs(true);
        $GLOBALS['questionCount'] = 0;
        storeData();
    }

    function loadTemp($testFile) {
        fclose($testFile);
        loadAttrs();
        loadObjs();
    }

    function storeTempAttr($tempAttr) {
        $tempAttrFile = fopen('data/a.temp','w');
        $tempJSON = json_encode($tempAttr);
        fwrite($tempAttrFile,$tempJSON);
        fclose($tempAttrFile);
    }

    function loadTempAttr() {
        $tempAttr = file_get_contents('data/a.temp');
        $tempJSON = json_decode($tempAttr);
        return $tempJSON;
    }

    function storeTempIndex() {
        $tempIndexFile = fopen('data/qIndex.temp','w');
        fwrite($tempIndexFile,$GLOBALS['questionCount']);
        fclose($tempIndexFile);
    }

    function loadTempIndex() {
        $GLOBALS['questionCount'] = file_get_contents('data/qIndex.temp');
}

    function storeTempMod() {
        $tempModFile = fopen('data/m.temp','w');
        $tempJSON = json_encode($GLOBALS['modObj']);
        fwrite($tempModFile,$tempJSON);
        fclose($tempModFile);
    }

    function loadTempMod() {
        $tempMod = file_get_contents('data/m.temp');
        $GLOBALS['modObj'] = json_decode($tempMod,true);
    }

    function clearTemp() {
        unlink('data/a.temp');
        unlink('data/m.temp');
        unlink('data/attrIndex.temp');
        unlink('data/qIndex.temp');
        unlink('oInfo.temp');
    }


    session_start();
    if (isset($_SESSION['open'])) {
        //clearTemp();
     }

    $_SESSION['open'] = 1;

    if ($idFile = fopen("data/attrIndex.temp","r")) loadTemp($idFile);
    else init();


    $type = $_GET["type"];

    if ($type == "start") {
        $thisAttr = pickAttr();
        $resJSON = sendAttr($thisAttr);
        storeTempAttr($thisAttr);
        storeTempIndex();
        dropAttr($thisAttr);
        storeData();
        echo $resJSON;
    }

    if ($type == "answer") {
        $ans = convertAns($_GET["ans"]);
        $lastAttr = loadTempAttr();
        loadTempIndex();
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
            if ($thisAttr[0] == null) {
                $resJSON = verifyObj($backupObj);
                echo $resJSON;
                exit;
            }
            $resJSON = sendAttr($thisAttr);
            storeTempAttr($thisAttr);
            storeTempIndex();
            dropAttr($thisAttr);
        }
        storeData();
        echo $resJSON;
    }

    if ($type == "check") {


        $ans = convertAns($_GET["ans"]);
        loadTempIndex();
        if ($ans) {
            $resJSON = terminal('Object retrived.');
            clearTemp();
        }
        else {
            $resJSON = terminal('Object not recorded.');
            clearTemp();
        }
        session_destroy();
        echo $resJSON;
    }

    if ($type == "reset") {
        clearTemp();
        echo "anything";
    }

    function convertAns($passIn) {
        if ($passIn == 'true') return true;
        else return false;
    }
