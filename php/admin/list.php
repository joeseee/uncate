<?php

header("content-type: text/html; charset=utf-8");

#$_SERVER['HTTP_X_REQUESTED_WITH'] = '';
date_default_timezone_set("Asia/Shanghai");

function my_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    print "$errno , $errstr";
    print "<br />\n";
    //die();
}
set_error_handler("my_error_handler");

if (get_magic_quotes_gpc()) {
    function undoMagicQuotes($array, $topLevel=true) {
        $newArray = array();
        foreach($array as $key => $value) {
            if (!$topLevel) {
                $key = stripslashes($key);
            }
            if (is_array($value)) {
                $newArray[$key] = undoMagicQuotes($value, false);
            }
            else {
                $newArray[$key] = stripslashes($value);
            }
        }
        return $newArray;
    }
    $_GET = undoMagicQuotes($_GET);
    $_POST = undoMagicQuotes($_POST);
    $_COOKIE = undoMagicQuotes($_COOKIE);
    $_REQUEST = undoMagicQuotes($_REQUEST);
}

#if(file_exists('dUnzip2.inc.php')) {
#    require_once('dUnzip2.inc.php');
#
#    function unzipAll($f) {
#        $zip = new dUnzip2($f);
#        $entries = $zip->getList();
#        foreach($entries as $fileName=>$trash) {
#            if(substr($fileName, -1 ) == '/' && !file_exists(dirname($f) . '/' . $fileName)) {
#                mkdir(dirname($f) . '/' . $fileName, 0777, true);
#                continue;
#            }
#            $zip->unzip($fileName, dirname($f) . '/' . $fileName);
#        }
#        $zip->close();
#    }
#} else {
    function unzipAll($f) {
        $zip = new ZipArchive;
        if ($zip->open($f)) {
            $zip->extractTo(dirname($f));
        }
        $zip->close();
    }
#}

#if(file_exists('zip.inc.php')) {
#    require_once('zip.inc.php');
#
#    function addTree($zip, $dir) {
#        foreach(scandir($dir) as $file) {
#            if ($file == '.' || $file == '..') {
#                continue;
#            }
#            if ($dir == '.') {
#                $path = $file;
#            } else {
#                $path = $dir . '/' . $file;
#            }
#            if (is_dir($path)) { 
#                $zip->addDir($path . '/');
#                addTree($zip, $path);
#            } else {
#                $data = implode('',file($path)); 
#                $zip->addFile($data,$path); 
#            }
#        }
#    }
#
#    function zipDir($dir, $outputFile) {
#        $currdir = getcwd();
#        chdir($dir);
#        $dir = '.';
#        $zip = new zipfile();
#        addTree($zip, $dir);
#        $zip->output($outputFile);
#        chdir($currdir);
#    }
#} else {
    function addTree($zip, $dir) {
        foreach(scandir($dir) as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if ($dir == '.') {
                $path = $file;
            } else {
                $path = $dir . '/' . $file;
            }
            if (is_dir($path)) { 
                addTree($zip, $path);
            } else {
                $zip->addFile($path); 
            }
        }
    }
    function zipDir($dir, $outputfile) {
        $currdir = getcwd();
        chdir($dir);
        $dir = '.';

        $zip = new ZipArchive;
        if (file_exists($outputfile)) {
            trigger_error("Failed, '{$outputfile}' already exists.");
        }
        if ($zip->open($outputfile, ZipArchive::OVERWRITE)) {
            addTree($zip, $dir);
        }
        $zip->close();

        chdir($currdir);
    }
#}



// functions
function fix_size($size) {
    if ($size < 1024)
        return $size . " B";
    else if ($size < 1048576)
        return number_format($size / 1024, 2, '.', '') . " KB";
    else
        return number_format($size / 1048576, 2, '.', '') . " MB";
}

function rrmdir($dir) { 
  if (is_dir($dir)) { 
    $objects = scandir($dir); 
    foreach ($objects as $object) { 
      if ($object != "." && $object != "..") { 
        if (filetype($dir."/".$object) == "dir") {
          rrmdir($dir."/".$object); 
        } else {
          unlink($dir."/".$object); 
        }
      } 
    } 
    reset($objects); 
    rmdir($dir); 
  } 
} 


function redirect($url) {
    header(sprintf("Location: %s", $url));
}

function ends_with($haystack, $needle){
    return substr($haystack, strlen($haystack) - strlen($needle)) == $needle;
}

function array_insert($array,$pos,$val)
{
    if (count($array) == 0) {
        $array[0] = $val;
        return $array;
    }
    $array2 = array_splice($array,$pos);
    $array[] = $val;
    $array = array_merge($array,$array2);
   
    return $array;
}

class ListPage {

    var $op;
    var $f;
    var $tbody;
    var $doc_root;

    function ListPage() {
        $this->doc_root = $_SERVER["DOCUMENT_ROOT"];
        $this->op = empty($_REQUEST['op']) ? ''  : $_REQUEST['op'];
        $this->f  = empty($_REQUEST['f'])  ? $this->doc_root : $_REQUEST['f'];
        $this->f  = str_replace("//", "/", $this->f);
    }

    // 列出文件
    function do_list() {
        $f = $this->f;
        if (!file_exists($f)) {
            trigger_error('file not exists.');
            return;
        }
        if (!is_dir($f)) {
            trigger_error('not dir.');
            return;
        }
        $tbody = '';
        
        // sort by mtime
        foreach (scandir($f) as $name) {
            if ($name == '.' || $name == '..') {
                continue;
            }
            $path = $f.'/'.$name;
            $tmp[$name] = filemtime($path);
        }
        $files = array();
        if (!empty($tmp)) {
            arsort($tmp);
            $files = array_keys($tmp);
        }
        $files = array_insert($files, 0, '..');
        $count = 0;
        foreach ($files as $name) {
            $path = $f.'/'.$name;
            $dir = dirname($path);
            $parent_path = dirname(dirname($path));
            if ($f == '.' && $name == '..') {
                continue;
            }
            $stat = stat($path);
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            if (!is_dir($path)) { // 文件
                if ($name == '.' || $name == '..') {
                    continue;
                }
                $tbody .= "<tr onmouseover='hover(this)' onmouseout='hout(this)'><td>{$name}</td>";
                $tbody .= "<td align='right'>" . fix_size(filesize($path)) . "</td>";
                $tbody .= "<td align='right'>" . $stat["uid"] . "</td>";
                $tbody .= "<td align='right'>" . $perms . "</td>";
                $tbody .= "<td align='center'>" . date("Y-m-d H:i:s", filemtime($path)) . "</td>";
                $tbody .= "<td align='center'>";
                if (ends_with($name, '.zip')) { // zip文件
                    $tbody .= "<a href='?f={$path}&op=unzip'>unzip</a> | ";
                } else {
                    $tbody .= "&nbsp;<a href='?f={$path}&op=showedit' target='_blank'>edit</a> | ";
                }
                $tbody .= "<a href='?f={$path}&op=delete'>rm</a> | ";
                $tbody .= "<a href='?f={$path}&op=download' target='_blank'>down</a> | ";
                $tbody .= "<a href=\"javascript:submitRenameForm('{$dir}','{$name}')\">mv</a> | ";
                $tbody .= "<a href=\"javascript:submitCopyForm('{$dir}','{$name}')\">cp</a> | ";
                $tbody .= "<a href=\"javascript:submitChmodForm('{$dir}','{$name}', '{$perms}')\">chmod</a>";
                $tbody .= "</td>";
                $tbody .= "</tr>\n";
            } else { // 文件夹
                if ($name == '.') { // 当前目录
                    continue;
                } else if ($name == '..') { // 父目录
                    $tbody .= "<tr onmouseover='hover(this)' onmouseout='hout(this)'>";
                    $tbody .= "<td><a href='?f={$parent_path}'>Parent Directory</a></td>";
                    $tbody .= "<td align='right'>&nbsp;</td>";
                    $tbody .= "<td align='right'>&nbsp;</td>";
                    $tbody .= "<td align='right'>&nbsp;</td>";
                    $tbody .= "<td align='center'>" . date("Y-m-d H:i:s", filemtime($path)) . "</td>";
                    $tbody .= "<td align='center'><a href='?f={$f}&op=zip'>zip</a></td>";
                    $tbody .= "</tr>\n";
                } else { // 子目录
                    $tbody .= "<tr onmouseover='hover(this)' onmouseout='hout(this)'>";
                    $tbody .= "<td><a href='?f={$path}'>{$name}/</a></td>";
                    $tbody .= "<td align='right'>&lt;dir&gt;</td>";
                    $tbody .= "<td align='right'>" . $stat["uid"] . "</td>";
                    $tbody .= "<td align='right'>" . substr(sprintf('%o', fileperms($path)), -4) . "</td>";
                    $tbody .= "<td align='center'>" . date("Y-m-d H:i:s", filemtime($path)) . "</td>";
                    $tbody .= "<td align='center'>";
                    $tbody .= "<a href='?f={$path}&op=delete'>rmdir</a> | ";
                    $tbody .= "<a href=\"javascript:submitRenameForm('{$dir}','{$name}')\">mv</a> | ";
                    $tbody .= "<a href=\"javascript:submitChmodForm('{$dir}','{$name}', '{$perms}')\">chmod</a>";
                    $tbody .= "</td>";
                    $tbody .= "</tr>\n";
                }
            }
            $count += 1;
        }
        $this->tbody = $tbody;
    }

    function do_mkdir() {
        $f = $this->f;
        $newname = isset($_REQUEST["dirname"]) ? $_REQUEST["dirname"] : "";

        mkdir("$f/$newname");
        redirect('?f=' . $f);
    }

    function do_touch() {
        $f = $this->f;
        $newname = isset($_REQUEST["newname"]) ? $_REQUEST["newname"] : "";

        touch("$f/$newname");
        redirect('?f=' . $f);
    }

    function do_chmod() {
        $f = $this->f;
        $oriname = isset($_REQUEST["oriname"]) ? $_REQUEST["oriname"] : "";
        $mode = isset($_REQUEST["newmode"]) ? $_REQUEST["newmode"] : "";
        echo chmod("$f/$oriname", octdec($mode));
        redirect('?f=' . $f);
    }

    function do_saveedit() {
        $f = $this->f;
        $data = isset($_POST["data"]) ? $_POST["data"] : "";
        file_put_contents($f, $data);
        redirect('?op=showedit&f=' . $f);
    }

    function do_showedit() {
        $f = $this->f;
        $content = file_get_contents($f);
        $content = htmlspecialchars($content);
        $lmt = date("Y-m-d H:i:s", filemtime($f));
        $ext = pathinfo($f, PATHINFO_EXTENSION); 


#        if(!is_dir('./codepress')) {
            print 
"
<title>Edit - $f</title>
<style type='text/css'>
body {font-family:'Courier New'; font-size:12px; min-width:1000px}
form,input {margin:0px; padding:0px}
input { border:1px solid #999; height:28px; line-height:28px; font-weight:bold; font-size:12px; font-family: 'Courier New';}
</style>
<h3>'{$f}' - Last Modified Time: {$lmt} - Type: $ext</h3>
<form name='cpform' action='?op=saveedit' method='POST'>
<input type='submit' value=' save '/>
<input type='button' value=' reload ' onclick=\"window.location.reload(true)\" />
<input type='button' value=' close ' onclick=\"window.close()\"/>
<hr style='border:0'/>
<input type='hidden' name='f' value='{$f}' />
<textarea name='data' style='width:100%;height:600px'>$content</textarea>
</form>

";
#           die();
#       }


#        $extMap = array(
#            'html'=>'html',
#            'htm' =>'html',
#            'xml' =>'html',
#            'php' =>'php',
#            'sql' =>'sql',
#            'js'  =>'javascript',
#            'pl'  =>'perl',
#            'java'=>'java',
#            'css' =>'css',
#            'cs'  =>'csharp',
#            'vb'  =>'vbscript',
#        );
#        $lang = isset($extMap[$ext]) ? $extMap[$ext] : 'generic' /*'text'*/;
#        $opt = "autocomplete-off";
/*
        print 
"
<!-- cp -->
<script src='/codepress/codepress.js' type='text/javascript'></script>

<script type='text/javascript'>
window.onload = function() {

    var cpform = document.forms['cpform'];
    cpform.defaultLang = '$lang';
    cpform.defaultopt = '$opt';

    for(var i = 0; i < cpform.langopt.length; i++) {
        var opt = cpform.langopt[i]
        if (opt.value == cpform.defaultLang) {
            opt.selected = true;
            break;
        }
    }
    cpform._disable_buttons = function() {
        cpform.close.disabled = true;
        cpform.reload.disabled = true;
        cpform.save.disabled = true;
        cpform.langopt.disabled = true;
    }

    cpform.reload.onclick = function() {
        cpform._disable_buttons();
        window.location.reload(true);
    }

    cpform.onsubmit = function() {
        // create textarea for posting value

        var data = document.createElement('textarea');
        this.appendChild(data);
    
        data.name = 'data';
        data.style.display = 'none';
        data.value = myCpWindow.getCode();
        cpform._disable_buttons();
        return true;
    }

    cpform.changeLang = function(lang, textcb) {

        // create buffer textarea to change language on the first time
        var bufta = document.getElementById('bufta');
        if(!bufta) {
            var bufta = document.createElement('textarea');
            bufta.id = 'bufta';
            bufta.style.display = 'none';
            bufta.className = cpform.defaultopt;
            this.appendChild(bufta);
        }
        bufta.value = textcb();

        // change language

        myCpWindow.edit('bufta', lang);
    }
}
</script>

<!-- cp -->

<title>Edit - $f</title>
<h3>'{$f}' - Last Modified Time: {$lmt} - Type: $ext</h3>
<style type='text/css'>
body {font-size:14px; min-width:1100px}
form,input {margin:0px; padding:0px}
input { border:1px solid #999; height:28px; line-height:28px; font-weight:bold; font-size:12px; font-family: Verdana, Arial, sans-serif;}
</style>
<form name='cpform' action='?op=saveedit' method='POST'>
<input type='submit' name='save' value='save' />
<input type='button' name='reload' value='reload' />
<input type='button' name='close'  value='close' onclick=\"window.close()\"/>
<!-- change lang -->
syntax:
<select name='langopt' onchange='this.form.changeLang(this.value, myCpWindow.getCode)'>
    <option value='generic'>generic</option>
    <option value='html'>html</option>
    <option value='php'>php</option>
    <option value='sql'>sql</option>
    <option value='css'>css</option>
    <option value='javascript'>javascript</option>
    <option value='csharp'>c#</option>
    <option value='java'>java</option>
    <option value='perl'>perl</option>
    <option value='vbscript'>vb</option>
</select>
<!-- change lang -->
<hr style='border:0'/>
<input type='hidden' name='f' value='{$f}' />


<!-- cp -->
<textarea id='myCpWindow' class='codepress $lang $opt' style='width:100%;height:600px'>$content</textarea>
<!-- cp -->

</form>
";
*/
    }

    function do_copy() {
        $f = $this->f;
        $oriname = isset($_REQUEST["oriname"]) ? $_REQUEST["oriname"] : "";
        $newname = isset($_REQUEST["newname"]) ? $_REQUEST["newname"] : "";
        copy("$f/$oriname", "$f/$newname");
        redirect('?f=' . $f);
    }

    function do_rename() {
        $f = $this->f;
        $oriname = isset($_REQUEST["oriname"]) ? $_REQUEST["oriname"] : "";
        $newname = isset($_REQUEST["newname"]) ? $_REQUEST["newname"] : "";
        
        
        rename("$f/$oriname", "$f/$newname");
        redirect('?f=' . $f);
    }

    // 解压zip文件内容到当前目录
    function do_unzip() {
        $f = $this->f;

        unzipAll($f);
        
        $f = dirname($f);
        redirect('?f=' . $f);
    }

    // 将当前目录打包到zip
    function do_zip() {
        $f = $this->f;
        
        $outputFile = basename($f) . '.zip';
        zipDir($f, $outputFile);

        redirect('?f=' . $f);
    }

    // 删除该zip文件
    function do_delete() {
        $f = $this->f;
        if (!file_exists($f)) {
            trigger_error('file not exists.');
        }
        if (is_dir($f)) {
            rrmdir($f);
        } else {
            unlink($f);
        }
        $f = dirname($f);
        redirect('?f=' . $f);    
    }

    // 下载文件
    function do_download() {
        $f = $this->f;
        if (!file_exists($f)) {
            trigger_error('file not exists.');
        }
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($f) . "\"");
        header("Content-Length: " . filesize($f));
        readfile($f);
    }

    function do_upload() {
        $f = $this->f;
        if ($_FILES["file"]["error"] > 0)  {
            trigger_error("Error: " . $_FILES["file"]["error"]);
        }
        move_uploaded_file($_FILES["file"]["tmp_name"], $f . '/' . $_FILES["file"]["name"]);
    }
    
    function process() {
        switch ($this->op) {
            case 'zip':
                $this->do_zip();
                break;
            case 'unzip':
                $this->do_unzip();
                break;
            case 'delete':
                $this->do_delete();
                break;
            case 'download':
                $this->do_download();
                die();
                break;
            case 'upload':
                $this->do_upload();
                break;
            case 'rename':
                $this->do_rename();
                break;
            case 'touch':
                $this->do_touch();
                break;
            case 'saveedit':
                $this->do_saveedit();
                break;
            case 'showedit':
                $this->do_showedit();
                die();
                break;
            case 'copy':
                $this->do_copy();
                break;
            case 'mkdir':
                $this->do_mkdir();
                break;
            case 'chmod':
                $this->do_chmod();
                break;
        }
        $this->do_list();
    }
}

// main函数开始

$page = new ListPage();
$page->process();

print "
<!DOCTYPE HTML>
<html>
<head>
<title>Index of '{$page->f}'</title>
<style type='text/css'>
* {padding:0px; margin:0px;}
* {font-family:'Courier New'; font-size:12px }
body {min-width:920px}
input {padding:1px}
form  {padding:5px}
td    {padding:3px}
table {border:1px solid #CCC}
th    {background-color: #333; color:#FFF}

</style>
<script type='text/javascript'>
function hover(elem) {
    elem.style.backgroundColor = '#EFEFEF';
}

function hout(elem) {
    elem.style.backgroundColor = '#FFFFFF';
}

String.prototype.trim=function() {return this.replace(/(^[\\s]*)|([\\s]*$)/g,'');}
function submitCopyForm(f,oriname){
    var newname=prompt('Enter the new filename',oriname);
    newname=newname.trim();
    if(!newname||newname==oriname){return;}
    var form=document.forms['copyform'];
    form.oriname.value=oriname;
    form.f.value=f;
    form.newname.value=newname;
    form.submit();
}
function submitRenameForm(f,oriname){
    var newname=prompt('Enter the new filename',oriname);
    newname=newname.trim();
    if(!newname||newname==oriname){return;}
    var form=document.forms['renameform'];
    form.oriname.value=oriname; form.f.value=f;
    form.newname.value=newname;
    form.submit();
}

function submitChmodForm(f,oriname,orimode){
    var newmode=prompt('Enter filename mode',orimode);
    newmode=newmode.trim();
    if(!newmode||newmode==orimode){return;}
    var form=document.forms['chmodform'];
    form.oriname.value=oriname; form.f.value=f;
    form.newmode.value=newmode;
    form.submit();
}

function fileSelected(input) {
    // IE not support
    if (input.files) {
        var file = input.files[0];
        var fileSize = 0;
        if (file.size > 1024 * 1024)
            fileSize = (file.size / (1024 * 1024)).toFixed(2) + 'MB';
        else
            fileSize = (file.size / 1024).toFixed(2) + 'KB';

        input.form.submit.value = 'Upload ' + fileSize;
        //input.form.submit.disabled = false;
    }
}
function uploadFile(form) {
    // IE not support
    try {
        var fd = new FormData();

        var inputs = form.getElementsByTagName('input');
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            if (input.name) {
                if (input.type == 'file') {
                    fd.append(input.name, input.files[0]);
                } else {
                    fd.append(input.name, input.value);
                }
            }
        }

        var btnSubmit = form.submit;
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(evt) {
            if (evt.lengthComputable) {
                var p = (evt.loaded * 100 / evt.total).toFixed(1);
                if (p < 100) {
                    btnSubmit.value = p.toString() + '%';
                } else {
                    btnSubmit.value = '99.9%';
                }
            } else {
                btnSubmit.value = 'unable to compute';
            }
        }, false);
        xhr.addEventListener('load', function(evt) {
            var text = evt.target.responseText;
            window.location='?f={$page->f}';
            //if (text != 1) {
            //    btnSubmit.value = 'Failed';
            //} else {
            //    btnSubmit.value = 'Finished';
            //    btnSubmit.disabled = true;
            //}
        }, false);
        xhr.addEventListener('error', function(evt) {
            alert('There was an error attempting to upload the file.');
        }, false);
        xhr.addEventListener('abort', function(evt) {
            alert('The upload has been canceled by the user or the browser dropped the connection.');
        }, false);
        xhr.open('POST', form.action);
        xhr.send(fd);
        btnSubmit.disabled = true;
        return false;
    } catch (err) {
        return true;
    }
}

function validateForm(form) {
    if (form.file.value == '') {
        return false;
    }
    return uploadFile(form);
}


</script>
</head>
<body>
<div style='margin:5px auto;width:920px'>
<div style='float:left'>
<form name='copyform' method='POST' action='?op=copy' style='display:none'>
<input type='hidden' name='f'/>
<input type='hidden' name='oriname'/>
<input type='hidden' name='newname'/>
</form>
<form name='renameform' method='POST' action='?op=rename' style='display:none'>
<input type='hidden' name='f'/>
<input type='hidden' name='oriname'/>
<input type='hidden' name='newname'/>
</form>
<form name='chmodform' method='POST' action='?op=chmod' style='display:none'>
<input type='hidden' name='f'/>
<input type='hidden' name='oriname'/>
<input type='hidden' name='newmode'/>
</form>

<form style='float:left' >
<b>Index of '{$page->f}'</b>
<input type='button' value='refresh' onclick=\"window.location='?f={$page->f}'\" />
<input type='button' value='home' onclick=\"window.location='?f={$page->doc_root}'\" />
</form>
<div style='clear:both'></div>
<form style='float:left' name='touch' method='POST' action='?op=touch' onsubmit=\"return this.newname.value != ''\">
<input type='hidden' name='f' value='{$page->f}'/>
<input type='text' size='12' name='newname'/>
<input type='submit' value='touch'/>
</form>
<form style='float:left' name='mkdir' method='POST' action='?op=mkdir' onsubmit=\"return this.dirname.value != ''\">
<input type='hidden' name='f' value='{$page->f}'/>
<input type='text' size='12' name='dirname'/>
<input type='submit' value='mkdir'/>
</form>
<form style='float:left' enctype='multipart/form-data' method='POST' onsubmit=\"return validateForm(this);\">
<input type='hidden' name='f' value='{$page->f}' />
<input type='hidden' name='op' value='upload' />
<input type='file' size='12' name='file' onchange=\"fileSelected(this)\"/>
<input type='submit' name='submit' value='upload'/>
</form>
</div>
<div style='clear:both'></div>

<table cellspacing='0'>
<tr>
<th width='260px' align='center'>Filename</th>
<th width='80px' align='right' >Size</th>
<th width='80px' align='center'>Owner</th>
<th width='80px' align='center'>Permission</th>
<th width='150px' align='center'>Modify Time</th>
<th width='270px' align='center'>Operation</th>
</tr>
{$page->tbody}
</table>
</div>
</body>
</html>";
?>
