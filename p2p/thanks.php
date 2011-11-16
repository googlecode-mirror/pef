<?php

// Check for a degraded file upload, this means SWFUpload did not load and the user used the standard HTML upload
$used_degraded = false;
$resume_id = "";
if (isset($_FILES["resume_degraded"]) && is_uploaded_file($_FILES["resume_degraded"]["tmp_name"]) && $_FILES["resume_degraded"]["error"] == 0) {
    $resume_id = $_FILES["resume_degraded"]["name"];
    $used_degraded = true;
}

// Check for the file id we should have gotten from SWFUpload
if (isset($_POST["hidFileID"]) && $_POST["hidFileID"] != "" ) {
	$resume_id = $_POST["hidFileID"];
}

if($resume_id != ''){
	// file upload success
	// print_r($_POST);
	$node_list = $_POST['node'];
	$node = base64_encode(serialize($node_list));
	//echo $node;
	$cmd = "/usr/bin/create_torrent.sh " . $node . ' ' . $resume_id;
	//echo $cmd;
	exec($cmd);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">


<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
<title>P2P Upload Demos</title>
<link href="css/default.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="header">
	<h1 id="logo"><a href="index.php">P2p Upload</a></h1>
	<div id="version">v2.2.0</div>
</div>

<div id="content">

	<h2><a href="index.php">P2P Upload Demo</a></h2>
	<br>
	<?php if ($resume_id == "") { ?>
		<p>Your upload file was not received.</p>
	<?php } else { ?>
		<table width="100%">
			<tr>
				<td>Result: </td>
				<td><?php echo htmlspecialchars('successful'); ?> </td>
			</tr>
			<tr>
				<td>Node: </td>
				<td><?php print_r($_POST["node"]); ?> </td>
			</tr>
			<tr>
				<td>Download link: </td>
				<td> <?php
foreach ($_POST['node'] as $node){
	$filename = $resume_id;
	echo "<a href=\"http://$node/download/$filename\"> http://$node/download/$filename </a> <br>";
}

 ?> </td>
			</tr>
		</table>
		<?php if ($used_degraded) { ?>
<p>You used the standard HTML form.</p>
		<?php } ?>
		<hr width="90%" />
		<p> Thank you for your submission. </p>
	<?php } ?>
	<p>&nbsp;</p>
</div>
</body>
</html>
