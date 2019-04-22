let questionIndex;
let attrTag;
let questionBlock;
let optionBlock;
let isCheck = false;

window.onload = function () {
    questionIndex = document.getElementById("index-wrapper");
    attrTag = document.getElementById("adj");
    questionBlock = document.querySelector(".q-disc");
    optionBlock = document.querySelector(".q-opt");
    init();
};

function init() {
    let xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) decode(xmlhttp.responseText);
    };
    xmlhttp.open("GET","scripts/control.php?type=start",true)
    xmlhttp.send();
}

function sendAjax(ans) {
    let xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) decode(xmlhttp.responseText);
    };
    if (!isCheck) xmlhttp.open("GET","scripts/control.php?type=answer&ans="+ans,true);
    else xmlhttp.open("GET","scripts/control.php?type=check&ans="+ans,true);
    xmlhttp.send();

}

//fake JSON:{"type":"filter","aname":"red","qnumber":1}

function decode(theJSON) {
    theJSON = theJSON.substring(theJSON.indexOf('{'));
    let obj = JSON.parse(theJSON);
    questionIndex.innerHTML = procNum(obj.qnumber);
    attrTag.innerHTML = procName(obj.aname) + " ?";
    if (obj.type == "verify") isCheck = true;
    if (obj.type == "terminal") terminal(obj.aname);
}

function terminal(content) {
    questionBlock.innerHTML = "<p>" + content + "</p>";
    optionBlock.innerHTML = "";
}

function procName(nameStr) {
    return nameStr.substring(0,1).toUpperCase()+nameStr.substring(1);
}

function procNum(number) {
    let tail = "th";
    switch (number) {
        case 1: tail = "st"; break;
        case 2: tail = "nd"; break;
        case 3: tail = "rd"; break;
        default: break;
    }
    return "" + number + tail;
}

function clearTemp() {
    let xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) window.location.reload();
    };
    xmlhttp.open("GET","scripts/control.php?type=reset",true)
    xmlhttp.send();
}
