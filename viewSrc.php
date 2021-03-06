<?php

/*
 * pmt.mcpe.me
 *
 * Copyright (C) 2015 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

use pg\lib\Generator;

define("ACCEPT_SUBPATH", true);
include_once __DIR__ . "/utils.php";
$proj = forceProject();
$generationTime = microtime(true);
$gen = new Generator($proj);
$gen->generate();
$generationTime = microtime(true) - $generationTime;
$files = $gen->files;

//if(!isset($_SERVER["PATH_INFO"])){
//	redirect($_SERVER["SCRIPT_NAME"] . "/");
//}

$path = "/" . ltrim(preg_replace("#[/\\\\]{2,}#", "/", $_REQUEST["path"]), "/");
?>
<html>
<head>
	<title>Source Viewer | PocketMine Plugin Generator</title>
	<?= INCLUDE_JQUERY ?>
	<!--suppress HtmlUnknownTarget -->
	<link rel="stylesheet" type="text/css" href="<?= $_SERVER["SCRIPT_NAME"] ?>/../style/normal.css">
<?php
if($path === "/"){
	goto listPath;
}else{
	$path = rtrim($path, "/");
	if(isset($files[$path])){
		goto dispFile;
	}else{
		foreach(array_keys($files) as $k){
			if(strtolower(substr($k, 0, strlen($path) + 1)) === strtolower("$path/")){
				$path .= "/";
				goto listPath;
			}
		}
	}
}
http_response_code(404);
?>
</head>
<body>
<h1 class="error">Error</h1>
<hr>
<p>That file/directory (<code class="code"><?= $path ?></code>) could not be located. Go <a href="<?= $_SERVER["SCRIPT_NAME"] ?>/">here</a> to view the root directory.</p>
<hr>
<footer>The plugin was generated in <?= $generationTime * 1000 ?>ms.</footer>
</body>
</html>
<?php
return;
listPath:
?>
	<!--suppress HtmlUnknownTarget -->
	<link rel="stylesheet" type="text/css" href="<?= $_SERVER["SCRIPT_NAME"] ?>/../style/dirView.css">
<?= "</head>" ?>
<body>
<h3>Index of
	<?php
	$myName = $_SERVER["SCRIPT_NAME"];
	echo "<a href='$myName'>{$proj->getDesc()->getName()}</a>&nbsp;";
	foreach(explode("/", substr($path, 1)) as $part){
		if($part === ""){
			continue;
		}
		echo '/&nbsp;<a href="';
		$myName .= "/";
		$myName .= urlencode($part);
		echo $myName;
		echo '">';
		echo htmlspecialchars($part);
		echo '</a>&nbsp;';
	}
	?>/
</h3>
<ul>
	<?php
	if($path !== "/"){
		echo "<li><a href='";
		echo rtrim($_SERVER["REQUEST_URI"], "/") . "/..";
		echo "'>..</a>";
	}
	$ok = [];
	foreach(array_keys($files) as $file){
		if(strtolower(substr($file, 0, strlen($path))) === strtolower($path)){
			$ok[] = ltrim(substr($file, strlen($path)), "/");
		}
	}
	sort($ok, SORT_NATURAL);
	$internalDir = [];
	foreach($ok as $f){
		$pointer =& $internalDir;
		$exp = explode("/", $f);
		foreach($exp as $i => $part){
			if(!isset($pointer[$part])){
				if(!isset($exp[$i + 1])){ // is filename
					$pointer[$part] = true;
					break;
				}
				else{ // is dirname
					$pointer[$part] = [];
				}
			}
			$pointer =& $pointer[$part];
		}
	}
	$myName = $_SERVER["SCRIPT_NAME"] . htmlspecialchars($path);
	foreach($internalDir as $name => $dir){
		if($dir === true){
			echo "<li><a href='$myName$name'>$name</a></li>";
		}
		else{
			while(is_array($dir) and count($dir) === 1){
				$n = array_keys($dir)[0];
				if($dir === true){
					break;
				}
				$name .= "/" . $n;
				$dir = $dir[$n];
			}
			?>
			<li><a href='<?= $myName . $name ?>'><?= $name . (isset($gen->files[$path . $name]) ? "" : "/") ?></a></li>
			<?php
		}
	}
	?>
</ul>
<hr>
<p>
	<button onclick="location = '/pg/';" class="button">Back</button>
</p>
<footer>The plugin was generated in <?= $generationTime * 1000 ?>ms.</footer>
</body>
<?php
return;
dispFile:
?>
	<!--suppress HtmlUnknownTarget -->
	<link rel="stylesheet" type="text/css" href="<?= $_SERVER["SCRIPT_NAME"] ?>/../style/sourceView.css">
<?= "</head>" ?>
<body>
<h3>
	<?php
	$myName = $_SERVER["SCRIPT_NAME"];
	echo "<a href='$myName'>{$proj->getDesc()->getName()}</a>&nbsp;";
	foreach(explode("/", substr($path, 1)) as $part){
		echo '/&nbsp;<a href="';
		$myName .= "/";
		$myName .= urlencode($part);
		echo $myName;
		echo '">';
		echo htmlspecialchars($part);
		echo '</a>&nbsp;';
	}
	?>
</h3>
<div class="main-source">
	<?php
	$indent = 4;
	$out = explode("<br>", preg_replace("#([\r\n]|(\r\n))#", "<br>", str_replace(["\t"], [str_repeat("&nbsp;", $indent)], htmlspecialchars(str_replace(["\r\n"], ["\n"], $files[$path])))));
	$max = strlen(count($out));
	for($line = 1; isset($out[$line - 1]); $line++){
		echo str_pad($line, $max, "0", STR_PAD_LEFT);
		echo "| ";
		echo $out[$line - 1];
		echo "<br>";
	}
	?>
</div>
<hr>
<p>
	<button onclick="location = '/pg/';" class="button">Back</button>
</p>
<footer>The plugin was generated in <?= $generationTime * 1000 ?>ms.</footer>
</body>
<?php
echo "</html>";
