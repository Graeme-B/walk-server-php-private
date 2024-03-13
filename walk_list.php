<?php
// https://stackoverflow.com/questions/35256950/how-to-start-from-a-certain-row-and-end-in-a-certain-row-in-mysql-php
// Needs to return selected course/country/class/year!
// Needs to return number of rows
// Do we go back to row 0 when course/country/class/year are changed?
// Also need USER!
// LIMIT 3,8  - start at row 3 (start AFTER row 3 and return 8 rows)

// Get the input parameters
if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
   $parms = $_GET;
}
else
{
   $parms = $_POST;
}

// Get the start row, number of rows, course, country, class and year
$startRow = array_key_exists("start_row",$parms) && is_numeric($parms["start_row"]) ? $parms["start_row"] : 0;
$numRows  = array_key_exists("num_rows",$parms)  && is_numeric($parms["num_rows"])  ? $parms["num_rows"]  : PHP_INT_MAX;
$course   = array_key_exists("course",$parms)    && $parms["course"] != ""          ? $parms["course"]    : "All";
$country  = array_key_exists("country",$parms)   && $parms["country"] != ""         ? $parms["country"]   : "All";
$class    = array_key_exists("class",$parms)     && $parms["class"] != ""           ? $parms["class"]     : "All";
$year     = array_key_exists("year",$parms)      && $parms["year"] != ""            ? $parms["year"]      : "All";
if ($startRow < 0) $startRow = 0;

// Initialise variables
$result           = [];
$result["result"] = "success";

$result["start_row"] = $startRow;
$result["num_rows"]  = $numRows;
$result["course"]    = $course;
$result["country"]   = $country;
$result["class"]     = $class;
$result["year"]      = $year;

// Get courses, countries, classes and years
$result["courses"] = ["All"];
$res = query("SELECT course_name FROM cwc_course");
if (sizeof($res) > 0)
{
  foreach($res as $row) {
    $result["courses"][] = $row["course_name"];
  }
}

$result["countries"] = ["All"];
$res = query("SELECT country_name FROM cwc_country");
if (sizeof($res) > 0)
{
  foreach($res as $row) {
    $result["countries"][] = $row["country_name"];
  }
}

$result["years"] = ["All"];
$res = query("SELECT year FROM cwc_year");
if (sizeof($res) > 0)
{
  foreach($res as $row) {
    $result["years"][] = $row["year"];
  }
}

$result["classes"] = ["All"];
$res = query("SELECT class FROM cwc_class");
if (sizeof($res) > 0)
{
  foreach($res as $row) {
    $result["classes"][] = $row["class"];
  }
}

// Build the query
// Unknown values (USER and EMAIL) are to keep the columns in line with walk_list_for_approval
$qry = "
SELECT w.id                              AS id,
       w.status                          AS status,
       w.optimum_minutes                 AS optimum_minutes,
       w.optimum_seconds                 AS optimum_seconds,
       name                              AS name,
       user                              AS user,
       create_date                       AS create_date,
       approved                          AS approved,
       'Unknown'                         AS email,
       IFNULL(cy.country_name,'Unknown') AS country,
       IFNULL(y.year,'Unknown')          AS year,
       IFNULL(c.course_name,'Unknown')   AS course,
       IFNULL(cl.class,'Unknown')        AS class
FROM cwc_walk w
LEFT OUTER JOIN cwc_country cy ON cy.id = w.country_id
LEFT OUTER JOIN cwc_year    y  ON y.id  = w.year_id
LEFT OUTER JOIN cwc_course  c  ON c.id  = w.course_id
LEFT OUTER JOIN cwc_class   cl ON cl.id = w.class_id";
if ($_SESSION['logged_on'] == "FALSE")
{
  $qry = $qry."
WHERE w.approved = 'approved'";
} else if ($_SESSION['logged_on'] != "FALSE" && $_SESSION['admin_user'] == "FALSE") {
  $qry = $qry."
WHERE (w.approved = 'approved'
OR     (w.approved = 'unapproved' AND LOWER(w.user) = LOWER('".$_SESSION['userid']."')))";
}

if ($course != "All")
{
  $qry .=  "
AND w.course_id = (SELECT id FROM cwc_course WHERE course_name = '".$course."')";
}
if ($country != "All")
{
  $qry = $qry."
AND w.country_id = (SELECT id FROM cwc_country WHERE country_name = '".$country."')";
}
if ($class != "All")
{
  $qry = $qry."
AND w.class_id = (SELECT id FROM cwc_class WHERE class = '".$class."')";
}
if ($year != "All")
{
  $qry = $qry."
AND w.year_id = (SELECT id FROM cwc_year WHERE year = '".$year."')";
}
$qry = $qry."
ORDER BY w.id";

$countQry = "SELECT COUNT(*) AS num_rows FROM (".$qry.") AS t";
$res = query($countQry);
if (sizeof($res) > 0)
{
  $result["num_rows"] = $res[0]["num_rows"];
}
else
{
  reportInternalError("No rows found",__FILE__,"Error retrieving row count",$errorMessage);
}

// Add the limits, run the query and get the results
$qry             = $qry.' LIMIT '.$startRow.','.$numRows;
$res             = query($qry);
$result["walks"] = [];
if (sizeof($res) > 0)
{
  foreach($res as $row) {
    if (is_countable($row)) {
      $walk                   = [];
      $walk["id"]             = $row["id"];
      $walk["status"]         = $row["status"];
      $walk["optimum_time"]   = sprintf("%d:%02d",$row["optimum_minutes"],$row["optimum_seconds"]);
      $walk["name"]           = htmlspecialchars($row["name"]);
      $walk["can_be_deleted"] = $_SESSION['logged_on'] != "FALSE" && ($_SESSION['admin_user'] == "TRUE" || strtolower($row["user"]) == strtolower($_SESSION['userid'])) ? "Y" : "N";
      $walk["create_date"]    = $row["create_date"];
      $walk["approved"]       = htmlspecialchars($row["approved"]);
      $walk["user"]           = htmlspecialchars($row["user"]);
      $walk["email"]          = htmlspecialchars($row["email"]);
      $walk["country"]        = htmlspecialchars($row["country"]);
      $walk["course"]         = htmlspecialchars($row["course"]);
      $walk["year"]           = htmlspecialchars($row["year"]);
      $walk["class"]          = htmlspecialchars($row["class"]);

      $result["walks"][] = $walk;
    }
  }
}

header('Content-type: application/json');

echo json_encode($result);
exit();
?>
