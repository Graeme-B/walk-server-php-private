<?php
include_once 'constants.php';
include_once 'common_functions.php';

function process_bounced_emails($emails)
{
   $count = 0;
   foreach($emails as $email) {
      reportSecurityProblem("bc recording bounced email ".$email);
      $query = sprintf("UPDATE %s
                        SET email_invalid = TRUE
                        WHERE email = ?", CONFIG_USER_TABLE);
      $res = query($query, "s",$email);
      if (getAffectedRows() > 0) {
         $count += 1;
      }
   }
   reportSecurityProblem("bc checked ".count($emails)." and updated $count users");
}
?>
