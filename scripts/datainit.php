<?php

    $attrMap = null;
    $objMap = null;
    $attrCount = array();
    $questionCount = 0;
    $modObj = array();

    // load attributes .JSON file to memory
    function loadAttrs() {
        $jsonStr = file_get_contents("data/attrIndex");
        $GLOBALS['attrMap'] = json_decode($jsonStr,true);
    }

    // load objects .JSON file to memory
    function loadObjs() {
        $jsonStr = file_get_contents("data/oInfo");
        $GLOBALS['objMap'] = json_decode($jsonStr,true);
    }

    // pick the most Identifying attributes
    function pickAttr() {
        $targetAttr = null;
        $targetIndex = null;
        $attrRateBest = 0;
        foreach ($GLOBALS['attrMap'] as $attrIndex => $currentAttr) {
            $attrY = $currentAttr[1];
            $attrN = $currentAttr[2];
            $attrRateCurrent = min($attrY,$attrN);
            if ( $attrRateCurrent > $attrRateBest ) {
                $targetAttr = $currentAttr;
                $attrRateBest = $attrRateCurrent;
                $targetIndex = $attrIndex;
            }
        }
        return array($targetIndex,$targetAttr);
    }

    // construct JSON string for next question
    function sendAttr($sentAttr) {
        $constructJSON = array();
        $GLOBALS['questionCount']++;
        $constructJSON['type'] = 'filter';
        $constructJSON['name'] = $sentAttr[1][0];
        $constructJSON['qnumber'] = $GLOBALS['questionCount'];
        $constructJSON = json_encode($constructJSON);
        // send JSON to client
    }

    // delete asked attributes
    function dropAttr($theAttr) {
        unset($GLOBALS['attrMap'][$theAttr[0]]);
    }

    // filter objects remaining after the previous question
    function filterObj($attr,$flag) {
        $backupObj = array_rand($GLOBALS['objMap'],1); // prepare a random-picked result
        if ($flag == true) $flag = 'without';
        else $flag = 'with';
        $attrIndex = $attr[0];
        foreach ($GLOBALS['objMap'] as $tag => $currentObj) {
            if ( in_array($attrIndex,$currentObj[$flag]) ) unset($GLOBALS['objMap'][$tag]);
        }
        if (count($GLOBALS['objMap']) == 1) {
            reset($GLOBALS['objMap']);
            verifyObj();
        }
        if ( (count($GLOBALS['objMap']) == 0) or ($GLOBALS['questionCount'] == 19) ) {
            verifyObj($backupObj);
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

    // decode JSON from question answer to pass to filterObj()
    function decodeAns($ans) {
        $ansJSON = json_decode($ans,true);
        return ($ansJSON['ans'] == "n");
    }

    // when only 1 last object left, check if it's the answer
    function verifyObj($opt = null) {
        $constructJSON = array();
        $GLOBALS['questionCount']++;
        $constructJSON['type'] = 'verify';
        if ($opt == null) $constructJSON['name'] = key($GLOBALS['objMap']);
        else $constructJSON['name'] = $opt;
        $constructJSON['qnumber'] = $GLOBALS['questionCount'];
        $constructJSON = json_encode($constructJSON);
        // send JSON to client
    }

    // record what the object in this life cycle is like
    function recordObj($attr,$state) {
        if ($state) $state = 1;
        else $state = 0;
        $GLOBALS['modObj'][$attr[0]] = $state;
    }

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

    function storeData() {
        $tempAttr = fopen('data/attrIndex','w');
        $contentAttr = json_encode($GLOBALS['attrMap']);
        fwrite($tempAttr,$contentAttr);
        fclose($tempAttr);

        $tempObj = fopen('data/oInfo','w');
        $contentObj = json_encode($GLOBALS['objMap']);
        fwrite($tempObj,$contentObj);
        fclose($tempObj);
    }

    // simulate the action of client for offline-logic
    function simulateClient() {

        // generate questions to form page
        $thisAttr = pickAttr();
        sendAttr($thisAttr);
        dropAttr($thisAttr);

        // server would receive yes/no
        $fakeJSON = '{"ans":"y"}';
        filterObj($thisAttr,decodeAns($fakeJSON));
        recordObj($thisAttr,decodeAns($fakeJSON));

        // ...loop till verifyObj() executed
        // server would receive name of the correct answer

    }

    // load JSON data to memory
    loadAttrs();
    loadObjs();

    //$modObj = array(2=>0,3=>0,4=>1,5=>1);
    //updateData('bluewater');
    // simulateClient();
    echo "\n";
    print_r($attrMap);
    print_r($objMap);
    //storeData();
