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

// See if the user name and password are OK
if (array_key_exists("email",$parms) && is_string($parms["email"]) &&
    array_key_exists("old_password",$parms) && is_string($parms["old_password"]) &&
    array_key_exists("new_password",$parms) && is_string($parms["new_password"]))
{
   $email        = $parms["email"];
   $old_password = $parms["old_password"];
   $new_password = $parms["new_password"];

   $query = sprintf("SELECT password AS password
                     FROM %s
                     WHERE email = ?", CONFIG_USER_TABLE);
   $res = query($query,"s",$email);
   if (sizeof($res) > 0)
   {
      if (strcmp($res[0]['password'],$old_password) == 0)
      {
         $query = sprintf("UPDATE %s
                           SET password = ?
                           WHERE email = ?", CONFIG_USER_TABLE);
         query($query,"ss",$new_password,$email);
         $result["result"] = "success";
      }
      else
      {
         $result["result"] = INVALID_PASSWORD;
      }
   }
   else
   {
      reportSecurityProblem("Invalid change password attempt for unknown user '".$email."'");
      $result["result"] = UNKNOWN_USER;
   }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
