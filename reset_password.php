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

// See if the input parameters are OK
if (array_key_exists("email",$parms) && is_string($parms["email"]) &&
    array_key_exists("password",$parms) && is_string($parms["password"]) &&
    array_key_exists("reset_code",$parms) && is_string($parms["reset_code"]))
{
   $email      = $parms["email"];
   $password   = $parms["password"];
   $reset_uuid = $parms["reset_code"];
   $query      = sprintf("SELECT name                AS name,
                                 admin_user          AS admin_user,
                                 password_reset_uuid AS reset_uuid
                          FROM %s
                          WHERE email = ?", CONFIG_USER_TABLE);
   $res        = query($query,"s",$email);
   if (sizeof($res) > 0)
   {
      if (strcmp($res[0]['reset_uuid'],$reset_uuid) == 0) {
         $_SESSION['logged_on']  = "TRUE";
         $_SESSION['userid']     = $res[0]['name'];
         $_SESSION['admin_user'] = $res[0]["admin_user"] == 1 ? "TRUE" : "FALSE";
         $query                  = sprintf("UPDATE %s
                                            SET last_logged_in           = NOW(),
                                                account_inactive         = 0,
                                                password_reset_requested = NULL,
                                                password_reset_uuid      = NULL,
                                                password                 = ?
                                             WHERE email = ?", CONFIG_USER_TABLE);
         query($query,"ss",$password,$email);
         $result["result"]     = "success";
         $result["email"]      = $email;
         $result["admin_user"] = $_SESSION['admin_user'];
         $result["name"]       = $_SESSION['userid'];
      }
      else
      {
         reportSecurityProblem("Invalid password reset attempt - bad UUID '".$reset_uuid."' for user '".$email."'");
         $result["result"] = INVALID_RESET_UUID;
      }
   }
   else
   {
      reportSecurityProblem("Invalid password reset attempt - unknown user '".$email."'");
      $result["result"] = UNKNOWN_USER;
   }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
