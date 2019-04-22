<?php

    $attrMap = null;
    $objMap = null;
    $questionCount = 0;
    $modObj = array();

    // load attributes .JSON file to memory
    // if isInit = false, read from temp file
    function loadAttrs($isInit = false) {
        if ($isInit) $fileType = "data/attrIndex";
        else $fileType = "data/attrIndex.temp";
        $jsonStr = file_get_contents($fileType);
        $GLOBALS['attrMap'] = json_decode($jsonStr,true);
    }

    // load objects .JSON file to memory
    // if isInit = false, read from temp file
    function loadObjs($isInit = false) {
        if ($isInit) $fileType = "data/oInfo";
        else $fileType = "data/oInfo.temp";
        $jsonStr = file_get_contents($fileType);
        $GLOBALS['objMap'] = json_decode($jsonStr,true);
    }

    // pick the most Identifying attributes
    // return: ($targetIndex,$targetAttr)
    function pickAttr() {
        $targetAttr = null;
        $targetIndex = null;
        $attrRateBest = 0;
        $attrCoverBest = 0;
        foreach ($GLOBALS['attrMap'] as $attrIndex => $currentAttr) {
            $attrY = $currentAttr[1];
            $attrN = $currentAttr[2];
            if (($attrY == 0 or $attrN ==0) and ($attrY + $attrN == count($GLOBALS['objMap']))) {
                continue;
            }
            $attrRateCurrent = min($attrY,$attrN);
            $attrCoverCurrent = $attrY + $attrN;
            if ( $attrRateCurrent > $attrRateBest ) {
                $targetAttr = $currentAttr;
                $attrRateBest = $attrRateCurrent;
                $targetIndex = $attrIndex;
            } elseif ($attrCoverCurrent > $attrCoverBest) {
                $targetAttr = $currentAttr;
                $attrCoverBest = $attrCoverCurrent;
                $targetIndex = $attrIndex;
            }
        }
        return array($targetIndex,$targetAttr);
    }

    // construct JSON string for next question
    // pass in: ($targetIndex,$targetAttr)
    // return: JSON{"type":"filter","aname":attrName,"qnumber":questionCount}
    function sendAttr($sentAttr) {
        $constructJSON = array();
        $GLOBALS['questionCount']++;
        $constructJSON['type'] = 'filter';
        $constructJSON['aname'] = $sentAttr[1][0];
        $constructJSON['qnumber'] = (int)$GLOBALS['questionCount'];
        $constructJSON = json_encode($constructJSON);
        return $constructJSON;
    }

    // delete asked attributes
    // pass in: ($targetIndex,$targetAttr)
    function dropAttr($theAttr) {
        unset($GLOBALS['attrMap'][$theAttr[0]]);
    }

    // filter objects remaining after the previous question
    // pass in: (($targetIndex,$targetAttr), true for with, false for without)
    function filterObj($attr,$flag) {

        if ($flag == true) $flag = 'without';
        else $flag = 'with';
        $attrIndex = $attr[0];
        foreach ($GLOBALS['objMap'] as $tag => $currentObj) {
            if ( in_array($attrIndex,$currentObj[$flag]) ) unset($GLOBALS['objMap'][$tag]);
        }
        updateAttr();
    }

    // update attributes map after objects been filtered
    // also used to initialize attributes map after added new objects
    function updateAttr() {
        foreach ($GLOBALS['attrMap'] as $attrIndex => $currentAttr) {
            $withCount = 0;
            $withoutCount = 0;
            foreach ($GLOBALS['objMap'] as $currentObj) {
                if ( in_array($attrIndex,$currentObj['with']) ) $withCount++;
                if ( in_array($attrIndex,$currentObj['without']) ) $withoutCount++;
            }
            $GLOBALS['attrMap'][$attrIndex][1] = $withCount;
            $GLOBALS['attrMap'][$attrIndex][2] = $withoutCount;
        }
    }

    // when only 1 last object left, check if it's the answer
    // return: JSON{"type":"verify","aname":objName,"qnumber":questionCount}
    function verifyObj($opt = null) {
        $constructJSON = array();
        $GLOBALS['questionCount']++;
        $constructJSON['type'] = 'verify';
        if ($opt == null) $constructJSON['aname'] = key($GLOBALS['objMap']);
        else $constructJSON['aname'] = $opt;
        $constructJSON['qnumber'] = (int)$GLOBALS['questionCount'];
        $constructJSON = json_encode($constructJSON);
        return $constructJSON;
    }

    function terminal($content) {
        $constructJSON = array();
        $GLOBALS['questionCount']++;
        $constructJSON['type'] = 'terminal';
        $constructJSON['aname'] = $content;
        $constructJSON['qnumber'] = (int)$GLOBALS['questionCount'];
        $constructJSON = json_encode($constructJSON);
        return $constructJSON;
    }

    // record what the object in this life cycle is like
    function recordObj($attr,$state) {
        if ($state) $state = 1;
        else $state = 0;
        $GLOBALS['modObj'][$attr[0]] = $state;
    }

    // update the object described in this run in memory
    function updateData($objName) {
        $objName = strtolower($objName);
        loadAttrs();
        loadObjs();

        if (array_key_exists($objName,$GLOBALS['objMap'])) {
            foreach ($GLOBALS['modObj'] as $attrIndex => $attrWith) {
                if ($attrWith == 1
                    and !in_array($attrIndex,$GLOBALS['objMap'][$objName]['with'])
                    and !in_array($attrIndex,$GLOBALS['objMap'][$objName]['without'])) {
                    array_push($GLOBALS['objMap'][$objName]['with'],$attrIndex);
                }
                if ($attrWith == 0
                    and !in_array($attrIndex,$GLOBALS['objMap'][$objName]['with'])
                    and !in_array($attrIndex,$GLOBALS['objMap'][$objName]['without'])) {
                    array_push($GLOBALS['objMap'][$objName]['without'],$attrIndex);
                }
            }
        }
        else {
            $GLOBALS['objMap'][$objName] = array();
            $GLOBALS['objMap'][$objName]['with'] = array();
            $GLOBALS['objMap'][$objName]['without'] = array();
            foreach ($GLOBALS['modObj'] as $attrIndex => $attrWith) {
                if ($attrWith == 1) array_push($GLOBALS['objMap'][$objName]['with'],$attrIndex);
                else array_push($GLOBALS['objMap'][$objName]['without'],$attrIndex);
            }
        }

        updateAttr();
    }

    // update objects and attributes file
    // if isInit = false, store to temp file
    function storeData($isInit = false) {
        if (!$isInit) $append = ".temp";
        else $append = "";
        $tempAttr = fopen('data/attrIndex'.$append,'w');
        $contentAttr = json_encode($GLOBALS['attrMap']);
        fwrite($tempAttr,$contentAttr);
        fclose($tempAttr);

        $tempObj = fopen('data/oInfo'.$append,'w');
        $contentObj = json_encode($GLOBALS['objMap']);
        fwrite($tempObj,$contentObj);
        fclose($tempObj);
    }
