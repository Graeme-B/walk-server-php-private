<?php

// Check that the walk id is valid

// Get the walk id
$walk_id = $_GET["id"];
query("UPDATE cwc_walk
       SET approved = 'unapproved'
       WHERE id = ?","i",$walk_id);

$result           = [];
$result["result"] = "success";
header('Content-type: application/json');
echo json_encode($result);
exit();
?>

