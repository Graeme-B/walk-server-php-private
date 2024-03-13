<?php
$_SESSION['logged_on']  = "FALSE";
$_SESSION['admin_user'] = "FALSE";
$_SESSION['last_regeneration'] = time();
session_regenerate_id(true);

$result           = [];
$result["result"] = "success";

header('Content-type: application/json');
echo json_encode($result);
exit();
?>
