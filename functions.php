<?php
function doDir($path){
	//echo "dir:$path<br>";
	if ($handle = opendir($path)) {
		myecho("<ul>");
		while (false !== ($file = readdir($handle))) {
			myecho("<li>$path/$file</li>");
			if ('.' === $file) continue;
			if ('..' === $file) continue;
			if(is_file("$path/$file")){
				if(substr($file,-3) == "php"){
					//its a PHP file
					doFile("$path/$file");
				}
			}
			if(is_dir("$path/$file")){
				doDir("$path/$file");
			}
        // do something with the file
		}
		closedir($handle);
		myecho("</ul>");
	} else {
		myecho("failed to open $path");
	}
}

function doFile($path){
	//echo "file:$path<br>";
	global $filecounter;
	if(!doneFile($path)){
		//get file lines and log progress;
		$log = logFile($path);
		$log = getLine($log) OR die("Error getting line {$log["line"]} in file {$log["path"]}.");
		if($log["lineText"] == "ENDOFFILE"){
			echo_message("Finished file {$log["path"]} ({$log["line"]} lines)","success");
			doneFile($path,true);
			return true;
		}
		//print_r($log);
		myecho("<br>line: {$log["lineText"]}<br>");
		echo_form($log);
	}
	$filecounter++;
}

function myecho($str){
	global $echoHTML;
	if($echoHTML === true) echo $str;
}

function doneFile($path,$finished = NULL){
	$dir = "logging";
	$file = hash("md2",$path) . ".done";
	if($finished === true){
		$handle = fopen("$dir/$file",'w') or die('Cannot open file:  '.$file);
		fclose($handle);
		return true;
	}
	if(file_exists("$dir/$file")) return true;
	else return false;
}
function logFile($path,$hash=FALSE){
	$dir = "logging";
	if($hash === TRUE) $file = $path;
	else $file = hash("md2",$path) .".log";
	if(file_exists("$dir/$file")){
		// load jason content and return;
		$handle = fopen("$dir/$file", 'r');
		$json = fread($handle,filesize("$dir/$file"));
		$arr = json_decode($json, true);
	} else {
		//create logging file.
		$arr = array("path"=>$path,"line"=>1);
		$json = json_encode($arr);
		$handle = fopen("$dir/$file",'w') or die('Cannot open file:  '.$file); //implicitly creates fil
		fwrite($handle, $json);
	}
	fclose($handle);
	return $arr;
}
function updateLog($data){
	$dir = "logging";
	if(isset($data["hash"])) $file = $data["hash"];
	else $file = hash("md2",$data["path"]) .".log";
	if(!file_exists("$dir/$file")) die("Error: Trying to update log that does not exist: ".(isset($data["path"])?$data["path"]:(isset($data["hash"])?$data["hash"]:"no data to show")));
	$logFile = logFile($file,TRUE);
	$arr = array();
	$arr["path"] = isset($data["path"])?$data["path"]:$logFile["path"];
	$arr["line"] = isset($data["line"])?$data["line"]:$logfile["line"];
	//$arr = array("path"=>$data["path"],"line"=>$data["line"]);
	$json = json_encode($arr);
	$handle = fopen("$dir/$file",'w') or die('Cannot open file:  '.$file); //implicitly creates fil
	fwrite($handle, $json);
	fclose($handle);
	return true;
}
function getLine($log, $abs = FALSE){
	$handle = fopen($log["path"], "r");
	$i = 1;
	if ($handle) {
		while (($line = fgets($handle)) !== false) {
			//print_r($line);
			myecho("i = $i<br>");
			// process the line read.
			if($i == $log["line"]){
				if($abs === FALSE && (empty($line) || substr($line,0,3) != '$_[')) $log["line"]++;
				else{
					$log["lineText"] = $line;
					return $log;
				} //return $line;
			}
			$i++;
		}
		fclose($handle);
		$log["lineText"] = "ENDOFFILE";
		return $log;
	} else {
		return false;
	} 
}
function echo_form($log){
	global $base_url;
	$tmp = explode(" = ",$log["lineText"]);
	//$len = strlen($tmp[1])-2;
	$value = substr($tmp[1], 1, -4);
	echo "Current line {$log["lineText"]} in file {$log["path"]}<br>";
	echo "<p id='p1' style='font-size:  50px;'>{$value}</p><button onclick=\"copyToClipboard('#p1')\">one click copy</button><br><br>";
	echo "<form action='$base_url/index.php' method='post'>";
	echo "Translation: <input type='text' name='new' style='width: 500px;' autofocus required><br>";
	echo "<input type='hidden' name='line' value='{$log["line"]}'>";
	echo "<input type='hidden' name='path' value='{$log["path"]}'>";
	echo '<input type="submit"></form>';
	$script=<<<HRD
	<script type="text/javascript">
	function copyToClipboard(element) {
	  var \$temp = $("<input>");
	  $("body").append(\$temp);
	  \$temp.val($(element).text()).select();
	  document.execCommand("copy");
	  \$temp.remove();
	}
	</script>
HRD;
	//echo $script;
	die();
}
function checkSubmit(){
	global $base_url;
	myecho("Checking submit<br>");
	if(isset($_POST['new']) && !empty($_POST['new'])) $new = $_POST['new'];
	if(isset($_POST['line']) && !empty($_POST['line'])) $line = $_POST['line'];
	if(isset($_POST['path']) && !empty($_POST['path'])) $path = $_POST['path'];
	if(isset($new) and isset($path) and isset($path)){
		$message = "";
		$message .= "Submit ditected<br>found New $new<br>";
		$log = getLine(array("line"=>$line,"path"=>$path),true);
		$tmp = explode(" = ",$log["lineText"]);
		if(count($tmp)!= 2) die("error expoding {$log["lineText"]}");
		$newLine = "{$tmp[0]} = '{$new}';";
		$message .= "new line: $newLine<br>";
		$message .= "<a href='$base_url/index.php?file=".hash("md2",$path).".log&line={$log["line"]}'>Click here to redo line {$log["line"]}</a><br>";
		if(replaceLine($log,$newLine)) $message .= "Line {$log["line"]} replaced in file {$log["path"]} from {$log["lineText"]} <br>";
		myecho("Submitted: $new for {$log["lineText"]}<br>");
		myecho("New line : $newLine<br>");
		echo_message($message,"info");
	} else myecho("no submit..<br>");
}

function replaceLine($log,$newLine){
	//return true;
	$reading = fopen($log["path"], 'r');
	$writing = fopen($log["path"].".tmp", 'w');

	$replaced = false;
	$i = 1;
	while (!feof($reading)) {
		$line = fgets($reading);
		if ($i == $log["line"]) {
			$line = $newLine."\n";
			$replaced = true;
		}
		fputs($writing, $line);
		$i++;
	}
	fclose($reading); fclose($writing);
	// might as well not overwrite the file if we didn't replace anything
	if ($replaced) {
		rename("{$log["path"]}.tmp", $log["path"]);
	} else {
	unlink('myfile.tmp');
	}
	$log["line"]++;
	return updateLog($log);
}
function echo_message($str,$level = "info"){
	$color = "#FFEB3B";
	if($level == "info") $color = "#FFEB3B";
	if($level == "success") $color = "#8bc34a";
	if($level == "danger") $color = "#f44336";
	echo "<div style='background-color: $color;'> <p><br>$str<br></p></div>";
}
?>