<?php

$html = file_get_contents("http://zh.tos.wikia.com");

preg_match('/<table class=\"wikitable\".*?>[\s\S]*?<\/table>/', $html, $matches);
$table = $matches[0];
$table = str_replace('href="', 'href="http://zh.tos.wikia.com', $table);
$table = preg_replace('/<img src="data:image.*?>/', '', $table);
$table = preg_replace('/<noscript>/', '', $table);
$table = preg_replace('/<\/noscript>/', '', $table);
?>

<html>
<head>
<title>TOS_WIKIA</title>
<meta charset="UTF-8">
<style>
table { border-spacing: 0; border-collapse: collapse;}
td {border:1px solid #ccc;}
</style>
</head>
<body>

<?php print $table; ?>

</body>
</html>