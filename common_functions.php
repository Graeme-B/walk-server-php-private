<?php

/* -------------------------------------------------------------------------------------------- */
/* Function to report a debug message                                                           */
/*                                                                                              */
/* Input parameters                                                                             */
/*  msg  -  message to write                                                                    */
/* -------------------------------------------------------------------------------------------- */
function debug($msg)
{
   $errorFile = LOGGING_DIRECTORY."debug.log";
   if ($fh = fopen($errorFile, 'a'))
   {
      fwrite($fh, "--------------------------------------------------------------------\n");
      fwrite($fh, "Date: ".date(DATE_W3C)."\n");
      fwrite($fh, "Msg : ".$msg."\n");
      fwrite($fh, "--------------------------------------------------------------------\n");
      fclose($fh);
   }
}

/* -------------------------------------------------------------------------------------------- */
/* Function to report a MySQL error                                                             */
/*                                                                                              */
/* Input parameters                                                                             */
/*  currentUser   -  Logged on user                                                             */
/*  currentFile   -  PHP script which executed the command                                      */
/*  currentQuery  -  Query which failed                                                         */
/*  returnCode    -  MySQL return code                                                          */
/*                                                                                              */
/* Output parameters                                                                            */
/*  errorSring  -  What to display on the glass                                                 */
/* -------------------------------------------------------------------------------------------- */
function reportMySqlError($currentUser,$currentFile,$currentQuery,$errorNumber,$errorText,&$errorMessage)
{
   $errorFile = LOGGING_DIRECTORY."PHPErrors.log";
   if ($fh = fopen($errorFile, 'a'))
   {
      fwrite($fh, "--------------------------------------------------------------------\n");
      fwrite($fh, "Date: ".date(DATE_W3C)."\n");
      fwrite($fh, "User: ".$currentUser."\n");
      fwrite($fh, "File: ".$currentFile."\n");
      fwrite($fh, "RC  : ".$errorNumber."\n");
      fwrite($fh, "Msg : ".$errorText."\n");
      fwrite($fh, "Qry : ".$currentQuery."\n");
      fwrite($fh, "--------------------------------------------------------------------\n");
      fclose($fh);
   }
   $errorMessage = "An internal error has occurred; please try again. If the failure persists, please contact the administrator.";
}

/* -------------------------------------------------------------------------------------------- */
/* Function to report an internal error                                                         */
/*                                                                                              */
/* Input parameters                                                                             */
/*  currentUser   -  Logged on user                                                             */
/*  currentFile   -  PHP script which executed the command                                      */
/* -------------------------------------------------------------------------------------------- */
function reportInternalError($currentUser,$currentFile,$errorText,&$errorMessage)
{
   $errorFile = LOGGING_DIRECTORY."PHPErrors.log";
   if ($fh = fopen($errorFile, 'a'))
   {
      fwrite($fh, "--------------------------------------------------------------------\n");
      fwrite($fh, "Date: ".date(DATE_W3C)."\n");
      fwrite($fh, "User: ".$currentUser."\n");
      fwrite($fh, "File: ".$currentFile."\n");
      fwrite($fh, "Msg : ".$errorText."\n");
      fwrite($fh, "--------------------------------------------------------------------\n");
      fclose($fh);
   }
   $errorMessage = "An internal error has occurred; please try again. If the failure persists, please contact the administrator.";
}

/* -------------------------------------------------------------------------------------------- */
/* Function to report on database tidying                                                       */
/* -------------------------------------------------------------------------------------------- */
function reportTidyDatabase($msg)
{
   $errorFile = LOGGING_DIRECTORY."tidy_database.log";
   if ($fh = fopen($errorFile, 'a'))
   {
      fwrite($fh, "--------------------------------------------------------------------\n");
      fwrite($fh, "Date: ".date(DATE_W3C)."\n");
      fwrite($fh, "Msg : ".$msg."\n");
      fwrite($fh, "--------------------------------------------------------------------\n");
      fclose($fh);
   }
}

/* -------------------------------------------------------------------------------------------- */
/* Function to report a potential security problem                                              */
/* -------------------------------------------------------------------------------------------- */
function reportSecurityProblem($msg)
{
   $errorFile = LOGGING_DIRECTORY."security_problem.log";
   if ($fh = fopen($errorFile, 'a'))
   {
      fwrite($fh, "--------------------------------------------------------------------\n");
      fwrite($fh, "Date: ".date(DATE_W3C)."\n");
      fwrite($fh, "Msg : ".$msg."\n");
      fwrite($fh, "--------------------------------------------------------------------\n");
      fclose($fh);
   }
}

/* -------------------------------------------------------------------------------------------- */
/* Function to return a default value if a variable isn't set                                   */
/* -------------------------------------------------------------------------------------------- */
function ifNotSet($var,$default)
{
   if (!isset($var))
   {
      return($default);
   }
   return($var);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to return a default value if an array element isn't set                             */
/* -------------------------------------------------------------------------------------------- */
function ifArrayIndexNotSet($array,$index,$default)
{
   if (!isset($array[$index]))
   {
      return($default);
   }
   return($array[$index]);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to replace parameters in a string                                                   */
/*                                                                                              */
/* The string contains the parameters delimted by {}                                            */
/* The parameters are a list of key/value pairs                                                 */
/* -------------------------------------------------------------------------------------------- */
function replaceParameters($string,$parms)
{
   $replaced = $string;
   foreach($parms as $key => $value)
   {
      $replaced = str_replace('{'.strtoupper($key).'}', $value, $replaced);
   }
   return($replaced);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to build the results set as an array of maps                                        */
/* Used by query()                                                                              */
/* -------------------------------------------------------------------------------------------- */
function build_results($result)
{
   $results = array();
   if (is_object($result) && $result->num_rows > 0)
   {
      while ($row = $result->fetch_assoc())
      {
         $resultRow = array();
         foreach($row as $key => $value)
         {
            $resultRow[$key] = $value;
         }
         $results[] = $resultRow;
      }
   }
   return($results);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to run a parameterised SQL statement which returns a result set                     */
/*                                                                                              */
/* Assumptions:                                                                                 */
/*   first parm is the statement                                                                */
/*   second parm, if present, is the type of arguments (eg "SSS" for three strings)             */
/*   other parms are the arguments themselves                                                   */
/*                                                                                              */
/* eg                                                                                           */
/*   query("CALL get_bookings(STR_TO_DATE(?,?,?,'%d,%m,%Y'),STR_TO_DATE(?,?,?,'%d,%m,%Y'));",   */
/*                            "SS","01,5,2013","01,5,2013");                                    */
/*                                                                                              */
/* The function returns the results set as an array of dictionaries                             */
/* -------------------------------------------------------------------------------------------- */
function query()
{
   global $mysqli;

// Clear the status and check the argument count
   $status = 0;
   $num_args = func_num_args();
   if ($num_args < 1)
   {
      reportInternalError($userid,__FILE__,'Invalid number of arguments',$errorMessage);
      $status = ERR_INTERNAL_ERROR;
   }

// Set up the results array
   $results = array();

// Get the mysql connection and connect to the database if not already done...
   if ($status == 0)
   {
      if (!isset($mysqli))
      {
         $mysqli = new mysqli(CONFIG_HOST, CONFIG_USER, CONFIG_PASS, CONFIG_DBASE);
         if ($mysqli->connect_error)
         {
            reportMySqlError($userid,__FILE__,"",$mysqli->connect_errno,$mysqli->connect_error,$errorMessage);
            $status = ERR_INTERNAL_ERROR;
            unset($mysqli);
         }
      }
   }

// If we connected OK, do our stuff
   if ($status == 0)
   {
      $args  = func_get_args();
      $query = $args[0]; 

// Set up the prepared statement if we have multiple args
      if ($num_args > 1)
      {
         $stmt = $mysqli->prepare($query);

// Bind parameters
         $params      = array();
         $params[]    = $args[1];
         $escapedArgs = array();
         for ($i = 2; $i < $num_args; $i++)
         {
            $escapedArgs[] = $mysqli->real_escape_string($args[$i]);
            $params[]      = &$escapedArgs[$i - 2];
         }
         call_user_func_array(array($stmt, 'bind_param'), $params);

// Execute the query and build an array of the results
         $result = $stmt->execute();
         if ($result)
         {
            $result = $stmt->get_result();
            $results = build_results($result);
         }
         else
         {
            reportMySqlError("",__FILE__,$query,$mysqli->errno,$mysqli->error,$errorMessage);
            $status = ERR_INTERNAL_ERROR;
         }

// Get the number of affecte rows
         $GLOBALS['num_rows'] = mysqli_affected_rows($mysqli);

// Free the results
         $stmt->close();
      }

// If we only have one argument, just execute the query
// We have to free all the results, for some bizarre reason...
      else
      {
         $result = $mysqli->query($query);
         if ($result)
         {
            if (is_object($result))
            {
                $results = build_results($result);
                $result->close();
                while ($mysqli->more_results())
                {
                    $mysqli->next_result();
                    $result = $mysqli->store_result();
                    if (is_object($result))
                    {
                        $result->free_result();
                    }
                }
                if (count($results) == 0)
                {
                    $results['status'] = 'OK';
                }
            }
            $GLOBALS['num_rows'] = mysqli_affected_rows($mysqli);
         }
         else
         {
            reportMySqlError("",__FILE__,$query,$mysqli->errno,$mysqli->error,$errorMessage);
            $status = ERR_INTERNAL_ERROR;
         }
      }
   }

   return($results);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to retrieve the number of affected rows                                             */
/* -------------------------------------------------------------------------------------------- */
function getAffectedRows()
{
   return($GLOBALS['num_rows']);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to accept the privacy policy                                                        */
/* -------------------------------------------------------------------------------------------- */
function sendEmail($message,$subject,$recipient)
{
   $returnAddr = "cwc_bouncecatcher_".str_replace("@","=",$recipient)."@".CONFIG_SERVER;
   $body       = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
  <div>'.$message.'</div>
</body>
</html>';

   $headers  = "From: ".CONFIG_SITE_NAME."<noreply@".CONFIG_SERVER.">\r\n";
   $headers .= "Reply-To: ".$returnAddr."\r\n";
   $headers .= "Return-Path: ".$returnAddr."\r\n";
   $headers .= "X-Mailer: PHP\r\n";
   $headers .= 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

   return (mail($recipient, $subject, $body, $headers) ? TRUE : FALSE);
}

/* -------------------------------------------------------------------------------------------- */
/* Function to accept the privacy policy                                                        */
/* -------------------------------------------------------------------------------------------- */
function privacyAccepted()
{
   $_SESSION['privacy_accepted'] = "TRUE";
   die(); 
}

/* -------------------------------------------------------------------------------------------- */
/* Function to change the terms and conditions acceptance                                       */
/* -------------------------------------------------------------------------------------------- */
function changeConditionsAcceptance($parms)
{
   $_SESSION['accepted'] = strtoupper($parms['accepted']);
   die(); 
}
