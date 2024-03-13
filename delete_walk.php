<?php

// Get the walk id and set up the result array
$walk_id = $_GET["id"];
$result  = [];

// Run the query and check we deleted something
query("UPDATE cwc_walk
       SET approved = 'deleted'
       WHERE id = ?","i",$walk_id);
$result["result"] = getAffectedRows() == 1 ? "success" : "failure";

// Return the results
header('Content-type: application/json');
echo json_encode($result);
exit();
?>
