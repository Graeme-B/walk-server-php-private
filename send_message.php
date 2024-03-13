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
if (array_key_exists("name",$parms) && is_string($parms["name"]) &&
    array_key_exists("email",$parms) && is_string($parms["email"]) &&
    array_key_exists("message",$parms) && is_string($parms["message"]) &&
    array_key_exists("captcha",$parms) && is_string($parms["captcha"]))
{
   $name    = $parms["name"];
   $email   = $parms["email"];
   $message = $parms["message"];
   $captcha = $parms["captcha"];
   if (strcmp($captcha,$_SESSION['captcha']) == 0) {
      $subject  = 'Email message from '.$name;
      $message  = 'Email message from user \''.$name.'\' at email address \''.$email.'\'<p>Message is <p>'.$message;
      sendEmail($message,$subject,'graeme_burton_1@yahoo.co.uk');
      $result["result"] = "success";
   }
   else
   {
      reportSecurityProblem("Invalid captcha sending email to '".$email."'");
      $result["result"] = INVALID_CAPTCHA;
   }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
