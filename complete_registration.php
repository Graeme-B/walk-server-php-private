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
if (array_key_exists("email",$parms) && is_string($parms["email"]) && strlen($parms["email"]) > 0 &&
    array_key_exists("registration_code",$parms) && is_string($parms["registration_code"]) && strlen($parms["registration_code"]) > 0)
{
   $email             = $parms["email"];
   $registration_code = $parms["registration_code"];
   $query             = sprintf("SELECT name
                                 FROM %s
                                 WHERE email               = ?
                                 AND   to_be_activated     = TRUE
                                 AND   password_reset_uuid = ?", CONFIG_USER_TABLE);
   $res               = query($query, "ss",$email,$registration_code);
   if (sizeof($res) > 0)
   {
      $query = sprintf("UPDATE %s
                        SET to_be_activated     = FALSE,
                            password_reset_uuid = NULL
                        WHERE email = ?", CONFIG_USER_TABLE);
      query($query,"s",$email);
      $result["admin_user"] = FALSE;
      $result["name"]       = $res[0]['name'];
      $result["email"]      = $email;
      $result["result"]     = "success";
   }
   else
   {
      reportSecurityProblem("Attempt to activate registration for user '".$email."' with code '".$registration_code."'");
      $result["result"] = REGISTERED_USER;
   }
}
else
{
   reportSecurityProblem("Attempt to activate registration with blank email and/or code");
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
