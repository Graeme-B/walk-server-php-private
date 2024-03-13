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
if (array_key_exists("name",$parms) && is_string($parms["name"]) &&
    array_key_exists("password",$parms) && is_string($parms["password"]))
{
   $name     = $parms["name"];
   $password = $parms["password"];

   $query    = sprintf("SELECT password               AS password,
                               admin_user             AS admin_user,
                               invalid_login_attempts AS invalid_login_attempts,
                               email                  AS email,
                               to_be_activated        AS to_be_activated,
                               email_invalid          AS email_invalid
                        FROM %s
                        WHERE name = ?", CONFIG_USER_TABLE);
   $res = query($query, "s",$name);
   if (sizeof($res) > 0)
   {
      if ($res[0]['email_invalid'])
      {
         $result["result"] = INVALID_EMAIL;
      }
      else if ($res[0]['to_be_activated'])
      {
         $result["result"] = ACCOUNT_NOT_ACTIVATED;
      }
      else if (strcmp($res[0]['password'],$password) == 0)
      {
         $_SESSION['logged_on']  = "TRUE";
         $_SESSION['userid']     = $name;
         $_SESSION['admin_user'] = $res[0]["admin_user"] == 1 ? "TRUE" : "FALSE";
         $query                  = sprintf("UPDATE %s
                                            SET last_logged_in           = NOW(),
                                                account_inactive         = 0,
                                                password_reset_requested = NULL
                                            WHERE name = ?", CONFIG_USER_TABLE);
         query($query,"s",$name);
         $result["admin_user"] = $_SESSION['admin_user'];
         $result["name"]       = $name;
         $result["email"]      = $res[0]["email"];
         $result["result"]     = "success";
      }
      else
      {
         if ($res[0]['invalid_login_attempts'] < MAX_INVALID_LOGIN_ATTEMPTS) {
            $query = sprintf("UPDATE %s
                              SET invalid_login_attempts = invalid_login_attempts + 1
                              WHERE name = ?", CONFIG_USER_TABLE);
            query($query,"s",$name);
            $result["result"] = INVALID_PASSWORD;
         }
         else
         {
            reportSecurityProblem("Max login attempts exceeded for user '".$name."'");
            $result["result"] = ACCOUNT_LOCKED;
         }
      }
   }
   else
   {
      reportSecurityProblem("Invalid login attempt for unknown user '".$name."'");
      $result["result"] = UNKNOWN_USER;
   }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
