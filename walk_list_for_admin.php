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

// Initialise variables
$result              = [];
$result["result"]    = "success";

if ($_SESSION['logged_on'] != "FALSE" && $_SESSION['admin_user'] != "FALSE") {

  // Build the query
  $qry = "
SELECT w.id              AS id,
       w.status          AS status,
       w.optimum_minutes AS optimum_minutes,
       w.optimum_seconds AS optimum_seconds,
       name              AS name,
       user              AS user,
       email             AS email,
       course            AS course,
       class             AS class,
       create_date       AS create_date,
       approved          AS approved
FROM cwc_walk w
ORDER BY w.id";

  // Run the query and get the results
  // The unknown values (YEAR and COUNTRY) are to keep the column list in line with walk_list
  $res = query($qry);
  $result["walks"] = [];
  if (sizeof($res) > 0)
  {
    foreach($res as $row) {
      $walk                   = [];
      $walk["id"]             = $row["id"];
      $walk["status"]         = $row["status"];
      $walk["optimum_time"]   = sprintf("%d:%02d",$row["optimum_minutes"],$row["optimum_seconds"]);
      $walk["name"]           = $row["name"];
      $walk["user"]           = $row["user"];
      $walk["email"]          = $row["email"];
      $walk["course"]         = $row["course"];
      $walk["class"]          = $row["class"];
      $walk["create_date"]    = $row["create_date"];
      $walk["year"]           = 'Unknown';
      $walk["country"]        = 'Unknown';
      $walk["approved"]       = $row["approved"];
      $walk["can_be_deleted"] = $_SESSION['logged_on'] != "FALSE" && ($_SESSION['admin_user'] == "TRUE" || strtolower($row["user"]) == strtolower($_SESSION['userid'])) ? "Y" : "N";

      $result["walks"][] = $walk;
    }
  }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
