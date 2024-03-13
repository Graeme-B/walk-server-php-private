<?php

// Get the input parameters
if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
   $parms = $_GET;
}
else
{
   $parms = $_POST;
}

// Assume we're going to fail
$result           = [];
$result["result"] = "failure";


// Check we're logged on and we're an admin user
if ($_SESSION['logged_on'] == "TRUE" && $_SESSION['admin_user'] == "TRUE") {

// Get the user to unlock and unlock it
   $user  = $parms["user"];
   $query = sprintf("UPDATE %s
                     SET account_inactive         = 0,
                         password_reset_requested = NOW(),
                         invalid_login_attempts   = 0
                     WHERE email = ?", CONFIG_USER_TABLE);
   query($query, "s",$user);
   if (getAffectedRows() > 0)
   {
      $result["result"] = "success";
   }
   else
   {
      $result["result"] = UNKNOWN_USER;
   }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
