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
if (array_key_exists("email",$parms) && is_string($parms["email"]))
{
   $email = $parms["email"];

   $query = sprintf("SELECT account_inactive AS account_inactive,
                            to_be_activated  AS to_be_activated,
                            email_invalid    AS email_invalid

                     FROM %s
                     WHERE email = ?", CONFIG_USER_TABLE);
   $res = query($query, "s",$email);
   if (sizeof($res) > 0)
   {
      if ($res[0]["email_invalid"])
      {
         reportSecurityProblem("Password reset request for invalid email account '".$email."'");
         $result["result"] = INVALID_EMAIL;
      }
    //   else if ($res[0]["account_inactive"])
    //   {
    //      reportSecurityProblem("Password reset request for inactive account '".$email."'");
    //      $result["result"] = "account_inactive";
    //   }
      else if ($res[0]["to_be_activated"])
      {
         $result["result"] = ACCOUNT_NOT_ACTIVATED;
         reportSecurityProblem("Password reset request for account '".$email."' which isn't activated yet");
      }
      else
      {
         $uuid  = uniqid();
         $query = sprintf("UPDATE %s
                           SET password_reset_requested = NOW(),
                               password_reset_uuid      = ?
                           WHERE email = ?", CONFIG_USER_TABLE);
         query($query, "ss",$uuid,$email);

         $url      = CONFIG_PROTOCOL.'://'.CONFIG_WEB_SERVER.'/'.ROOT_PATH.'?action=password_reset&email='.$email.'&reset_code='.$uuid;
         $subject  = 'Password Reset for '.CONFIG_SITE_NAME;
         $message  = '<p>Please click the following link to reset your password at <a href ="'.$url.'">'.CONFIG_SITE_NAME.'</a></p>';
         $message .= '<p>If you did not request a password reset, please email <a href=mailto:'.CONFIG_ADMIN_EMAIL.'>'.CONFIG_ADMIN_EMAIL.'</a></p>';
         
         if (sendEmail($message,$subject,$email)) {
            $result["result"] = "success";
         }
      }
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
