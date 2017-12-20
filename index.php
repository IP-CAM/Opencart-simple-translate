<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">
	function copyToClipboard(element) {
	  var $temp = $("<input>");
	  $("body").append($temp);
	  $temp.val($(element).text()).select();
	  document.execCommand("copy");
	  $temp.remove();
	}
</script>
<?php
include("config.php");
include("functions.php");

if(isset($_GET['line']) && !empty($_GET['line'])) $line = $_GET['line'];
if(isset($_GET['file']) && !empty($_GET['file'])) $file = $_GET['file'];
if(isset($file) && isset($line)){
	updateLog(array("hash"=>$file,"line"=>$line));
	unset($file);
	unset($line);
}

checkSubmit();
doDir($dir);
myecho("$filecounter files were found");


?>
