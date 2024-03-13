<?php

/* ---------------------------------------------------------
// Queries to run through mysql - all for walk id 83
SELECT w.id                              AS id,
       w.name                            AS name,
       w.user                            AS user,
       w.email                           AS email,
       w.course                          AS course,
       w.class                           AS class,
       w.create_date                     AS create_date,
       w.approved                        AS approved,
       w.optimum_minutes                 AS optimum_minutes,
       w.optimum_seconds                 AS optimum_seconds,
       w.uuid                            AS uuid,
       IFNULL(cy.country_name,'Unknown') AS country_name,
       IFNULL(y.year,'Unknown')          AS year_name,
       IFNULL(c.course_name,'Unknown')   AS course_name,
       IFNULL(cl.class,'Unknown')        AS class_name
FROM cwc_walk w
LEFT OUTER JOIN cwc_country cy ON cy.id = w.country_id
LEFT OUTER JOIN cwc_year    y  ON y.id  = w.year_id
LEFT OUTER JOIN cwc_course  c  ON c.id  = w.course_id
LEFT OUTER JOIN cwc_class   cl ON cl.id = w.class_id
WHERE w.id = 83

SELECT latitude,
       longitude,
       distance,
       create_date
FROM cwc_walk_track
WHERE walk_id = 83 
ORDER BY sequence_id

SELECT latitude,
       longitude
FROM cwc_walk_waypoint
WHERE walk_id = 83
ORDER BY sequence_id

SELECT latitude,
       longitude,
       image_name
FROM cwc_walk_image
WHERE walk_id = 83 
ORDER BY sequence_id
   --------------------------------------------------------- */


// Get the input parameters
$walk_id      = $_GET["id"];
$for_approval = $_GET["for_approval"];

// Initialise variables
$result           = [];
$result["result"] = "success";
$walk             = [];

// Get the walk details
$details = [];
$res = query("SELECT w.id                              AS id,
                     w.name                            AS name,
                     w.user                            AS user,
                     w.email                           AS email,
                     w.course                          AS course,
                     w.class                           AS class,
                     w.notes                           AS notes,
                     w.create_date                     AS create_date,
                     w.approved                        AS approved,
                     w.optimum_minutes                 AS optimum_minutes,
                     w.optimum_seconds                 AS optimum_seconds,
                     w.uuid                            AS uuid,
                     IFNULL(cy.country_name,'Unknown') AS country_name,
                     IFNULL(y.year,'Unknown')          AS year_name,
                     IFNULL(c.course_name,'Unknown')   AS course_name,
                     IFNULL(cl.class,'Unknown')        AS class_name
              FROM cwc_walk w
              LEFT OUTER JOIN cwc_country cy ON cy.id = w.country_id
              LEFT OUTER JOIN cwc_year    y  ON y.id  = w.year_id
              LEFT OUTER JOIN cwc_course  c  ON c.id  = w.course_id
              LEFT OUTER JOIN cwc_class   cl ON cl.id = w.class_id
              WHERE w.id = ?","i",$walk_id);
if (sizeof($res) > 0)
{
  // Extract the walk details
  foreach($res as $row) {
    $details['walk_id']      = $row["id"];
    $details['name']         = htmlspecialchars($row["name"]);
    $details['user']         = htmlspecialchars($row["user"]);
    $details['email']        = htmlspecialchars($row["email"]);
    $details['course']       = htmlspecialchars($row["course"]);
    $details['class']        = htmlspecialchars($row["class"]);
    $details['notes']        = htmlspecialchars($row["notes"]);
    $details['create_date']  = $row["create_date"];
    $details['approved']     = $row["approved"];
    $details['optimum_time'] = sprintf("%d:%02d", $row["optimum_minutes"],$row["optimum_seconds"]);
    $details['country_name'] = htmlspecialchars($row["country_name"]);
    $details['year_name']    = htmlspecialchars($row["year_name"]);
    $details['course_name']  = htmlspecialchars($row["course_name"]);
    $details['class_name']   = htmlspecialchars($row["class_name"]);
  }

  // Get the walk track
  $track   = [];
  $min_lat = 90.0; 
  $max_lat = -90.0;
  $min_lon = 180.0;
  $max_lon = -180.0;
  $res = query("SELECT latitude,
                       longitude,
                       distance,
                       create_date
                FROM cwc_walk_track
                WHERE walk_id = ?
                ORDER BY sequence_id","i",$walk_id);
  if (sizeof($res) > 0) {
    foreach($res as $row) {
      $lat      = $row["latitude"];
      $lon      = $row["longitude"];
      $distance = $row["distance"];
      $created  = $row["create_date"];
      if ($lat > $max_lat) $max_lat = $lat;
      if ($lat < $min_lat) $min_lat = $lat;
      if ($lon > $max_lon) $max_lon = $lon;
      if ($lon < $min_lon) $min_lon = $lon;
      $track['latitude'][]        = $lat;
      $track['longitude'][]       = $lon;
      $walk_track['distances'][]  = $distance;
      $walk_track['created_at'][] = $created;
    }
    $details['total_distance'] = $distance;
    $track['centre_lat']       = ($max_lat - $min_lat)/2.0 + $min_lat;
    $track['centre_lon']       = ($max_lon - $min_lon)/2.0 + $min_lon;
    $track['min_lon']          = $min_lon;
    $track['max_lon']          = $max_lon;
    $track['min_lat']          = $min_lat;
    $track['max_lat']          = $max_lat;
  }

  // Get the walk waypoints
  $waypoints = [];
  $res = query("SELECT latitude,
                       longitude
                FROM cwc_walk_waypoint
                WHERE walk_id = ?
                ORDER BY sequence_id","i",$walk_id);
  if (sizeof($res) > 0)
  {
    foreach($res as $row) {
      $waypoints['latitude'][]  = $row["latitude"];
      $waypoints['longitude'][] = $row["longitude"];
    }
  }

  // Get the images
  $images = [];
  $res = query("SELECT latitude,
                       longitude,
                       image_name
                FROM cwc_walk_image
                WHERE walk_id = ?
                ORDER BY sequence_id","i",$walk_id);
  if (sizeof($res) > 0)
  {
    foreach($res as $row) {
      $images['latitude'][]  = $row["latitude"];
      $images['longitude'][] = $row["longitude"];
      $images['path'][]      = $row["image_name"];
    }
  }

  // Copy the details
  $walk['details']   = $details;
  $walk['track']     = $track;
  $walk['waypoints'] = $waypoints;
  $walk['images']    = $images;

  // Get the country/year/course/class values if it's for approval
  if ($for_approval == "true") {
    $countries = [];
    $res = query("SELECT country_name FROM cwc_country");
    if (sizeof($res) > 0)
    {
      foreach($res as $row) {
        $countries[] = $row["country_name"];
      }
    }

    $years = [];
    $res = query("SELECT year FROM cwc_year");
    if (sizeof($res) > 0)
    {
      foreach($res as $row) {
        $years[] = $row["year"];
      }
    }
  
    $courses = [];
    $res = query("SELECT course_name FROM cwc_course");
    if (sizeof($res) > 0)
    {
      foreach($res as $row) {
        $courses[] = $row["course_name"];
      }
    }
  
    $classes = [];
    $res = query("SELECT class FROM cwc_class");
    if (sizeof($res) > 0)
    {
      foreach($res as $row) {
        $classes[] = $row["class"];
      }
    }

    $result['countries'] = $countries;
    $result['years']     = $years;
    $result['courses']   = $courses;
    $result['classes']   = $classes;
  }

  $result['walk'] = $walk;
} else {
  $result["result"] = "fail";
}
  

header('Content-type: application/json');
echo json_encode($result);
exit();
?>
