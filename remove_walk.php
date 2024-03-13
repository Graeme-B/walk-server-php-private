<?php
function deleteDir($dirPath) {
  if (file_exists($dirPath)) {
    if (! is_dir($dirPath)) {
      throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
      $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
      unlink($file);
    }
    rmdir($dirPath);
  }
}

// Delete a walk
function removeWalk($walk_id) {
  query("DELETE FROM cwc_walk_track
         WHERE walk_id = ?","i",$walk_id);
  query("DELETE FROM cwc_walk_waypoint
         WHERE walk_id = ?","i",$walk_id);
  query("DELETE FROM cwc_walk_image
         WHERE walk_id = ?","i",$walk_id);
  query("DELETE FROM cwc_walk
         WHERE id = ?","i",$walk_id);
  deleteDir($walk_id);
}

// Get the walk id and set up the result array
$walk_id = $_GET["id"];
$result  = [];

// Rules:
//  If the logged in user is an admin user, just remove the walk
//  If someone is logged in, check that the walk belongs to them: remove if it does, flag a security breach if it does not
//  If noone is logged on, flag a security breach
if ($_SESSION['logged_on'] == "TRUE") {
  if ($_SESSION['admin_user'] == "TRUE") {
    removeWalk($walk_id);
    $result["result"] = "success";
  } else {
    $res = query("
SELECT COUNT(*) AS num_rows
FROM cwc_walk
WHERE id          = ?
AND LOWER(w.user) = LOWER(?))","is",$walk_id,$_SESSION['userid']);
    if (sizeof($res) > 0 && $res[0]["num_rows"] > 0) {
      removeWalk($walk_id);
      $result["result"] = "success";
    } else {
      reportSecurityProblem("Unauthorised attempt to delete walk - user '".$_SESSION['userid']."' walk id '".$_GET["id"]."'");
      $result["result"] = "failure";
    }
  }
} else {
  reportSecurityProblem("Unauthorised attempt to delete walk id '".$_GET["id"]."' by unknown user");
  $result["result"] = "failure";
}

// Return the results
header('Content-type: application/json');
echo json_encode($result);
exit();
?>

