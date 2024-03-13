<?php
include 'common_functions.php';

$ERROR_FILE = "walk_upload.err";

$ERR_ALL_OK      = "Walk uploaded successfully";
$ERR_PARSE_JSON  = "Server error - please report this message to admin@".SERVER." quoting error ";
$ERR_SQL_CONNECT = "Server error - please report this message to admin@".SERVER." quoting error ";
$ERR_SQL_EXECUTE = "Server error - please report this message to admin@".SERVER." quoting error ";
$error_id        = uniqid(ERRORSTEM);
$emptyString     = "";

class MyException extends Exception { }

/* ----------------------------------------------------------------------- */
// Function to fetch an id (country, class etc) if the item exists, and to */
// create the item if it does not exist
/* ----------------------------------------------------------------------- */
function fetchOrCreateId($mysqli,$s1,$s2,$param,$type)
{
  $id   = -1;
  $stmt = $mysqli->prepare($s1);
  $stmt->bind_param($type,$param);
  if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $id = $row["id"];
    } else {
      $stmt = $mysqli->prepare($s2);
      $stmt->bind_param($type,$param);
      if ($stmt->execute()) {
        $id = $mysqli->insert_id;
      } else {
        reportInternalError("None",__FILE__,"Error executing INSERT INTO course/class/year/country",$mysqli->error);
        throw new MyException($ERR_SQL_EXECUTE.$error_id);
      }
    }
  } else {
    reportInternalError("None",__FILE__,"Error executing INSERT INTO course/class/year/country",$mysqli->error);
    throw new MyException($ERR_SQL_EXECUTE.$error_id);
  }
  return($id);
}

// Initialise the response
$errc = 200;
$errm = $ERR_ALL_OK;

// Parse the JSON out of the input and create a JSON object from it
try {
  $data = json_decode(file_get_contents('php://input'), true);
  $s    = "";
  foreach ($data as $row) {
    $s .= chr($row);
  }
  $rawjson = $s;
  $json = json_decode($s, true);
} catch(Exception $e) {
  reportInternalError("None",__FILE__,"WalkUpload - Parse JSON error",$e->getMessage());
  $errc = 500;
  $errm = $ERR_PARSE_JSON.$error_id;
}

// Check that the top level JSON is as expected
if (!(array_key_exists("walk",$json) && is_array($json["walk"]))) {
  reportInternalError("None",__FILE__,"Walk does not exist or is not an array in the input JSON",$emptyString);
  $errc = 500;
  $errm = $ERR_PARSE_JSON.$error_id;
}
$walk = $json["walk"];
if ($errc == 200 &&
    !(array_key_exists("device_uuid",$json) && is_string($json["device_uuid"]) &&
      array_key_exists("user",$json)    && is_string($json["user"]) &&
      array_key_exists("name",$json)    && is_string($json["name"]) &&
      array_key_exists("uuid",$json)    && is_string($json["uuid"]) &&
      array_key_exists("email",$json)   && is_string($json["email"]) &&
      array_key_exists("name",$walk)    && is_string($walk["name"]) &&
      array_key_exists("created",$walk) && is_string($walk["created"]))) {
  reportInternalError("None",__FILE__,"Missing top level VALUE or invalid TYPE in JSON ",$json);
  $errc = 500;
  $errm = $ERR_PARSE_JSON.$error_id;
}
if ($errc == 200 &&
    (array_key_exists("email",$json)        && !is_string($json["email"])) ||
    (array_key_exists("country",$json)      && !is_string($json["country"])) ||
    (array_key_exists("class",$json)        && !is_string($json["class"])) ||
    (array_key_exists("year",$json)         && !is_int($json["year"])) ||
    (array_key_exists("notes",$json)        && !is_string($json["notes"])) ||
    (array_key_exists("optimum_mins",$walk) && !is_int($walk["optimum_mins"])) ||
    (array_key_exists("optimum_secs",$walk) && !is_int($walk["optimum_secs"]))) {
  reportInternalError("None",__FILE__,"Invalid top level TYPE in JSON ".
                      $json["email"]." ".$json["country"]." ".$json["class"]." ".
                      $json["year"]." ".$walk["optimum_mins"]." ".$walk["optimum_secs"]. "\n".$json,$emptyString);
  $errm = $ERR_PARSE_JSON.$error_id;
}

// Connect to the database
if ($errc == 200) {
  $mysqli = new mysqli($CONFIG_host, $CONFIG_user, $CONFIG_pass, $CONFIG_dbase );
  if ($mysqli->connect_error)
  {
    reportInternalError("None",__FILE__,"Connect SQL error no ".$mysqli->connect_errno." error ".$mysqli->connect_error,$emptyString);
    $errc = 500;
    $errm = $ERR_SQL_CONNECT.$error_id;
  }
}

// If we connected OK, do our stuff in a transaction block
if ($errc == 200)
{
  $mysqli->begin_transaction();

  // Get the year, country, course and class IDs
  try {
    $year    = array_key_exists("year",$json) ? urldecode($json["year"]) : 2023;
    $country = array_key_exists("country",$json) ? urldecode($json["country"]) : "unknown";
    $course  = array_key_exists("name",$json) ? urldecode($json["name"]) : "unknown";
    $class   = array_key_exists("class",$json) ? urldecode($json["class"]) : "unknown";
    $yearId = fetchOrCreateId($mysqli,
                              "SELECT id FROM cwc_year WHERE year = ?",
                              "INSERT INTO cwc_year(year) VALUES(?)",
                              $year,"i");
    $countryId = fetchOrCreateId($mysqli,
                                 "SELECT id FROM cwc_country WHERE UPPER(country_name) = UPPER(?)",
                                 "INSERT INTO cwc_country(country_name) VALUES(?)",
                                 $country,"s");
    $courseId = fetchOrCreateId($mysqli,
                                "SELECT id FROM cwc_course WHERE UPPER(course_name) = UPPER(?)",
                                "INSERT INTO cwc_course(course_name) VALUES(?)",
                                $course,"s");
    $classId = fetchOrCreateId($mysqli,
                               "SELECT id FROM cwc_class WHERE UPPER(class) = UPPER(?)",
                               "INSERT INTO cwc_class(class) VALUES(?)",
                               $class,"s");
  }
  catch (MyException $e)
  {
    reportInternalError("None",__FILE__,"Get year/country/course/class error",$e->getMessage());
    $errc = 500;
    $errm = $e->getMessage();
  }

  // Add into the WALK table
  if ($errc == 200)
  {
    $stmt = $mysqli->prepare("INSERT INTO cwc_walk(
                             device_uuid,
                             user,
                             email,
                             course,
                             class,
                             uuid,
                             name,
                             notes,
                             create_date,
                             approved,
                             optimum_minutes,
                             optimum_seconds)
                             VALUES(?,?,?,?,?,?,?,?,?,'unapproved',?,?)");
    $stmt->bind_param("sssssssssii",
                      $json["device_uuid"],
                      urldecode($json["user"]),
                      urldecode($json["email"]),
                      urldecode($json["name"]),
                      urldecode($json["class"]),
                      $json["uuid"],
                      urldecode($walk["name"]),
                      urldecode($json["notes"]),
                      $walk["created"],
                      $walk["optimum_mins"],
                      $walk["optimum_secs"]);
    if (!$stmt->execute())
    {
      reportInternalError("None",__FILE__,"Error executing INSERT INTO cwc_walk",$mysqli->error);
      $errc = 500;
      $errm = $ERR_SQL_EXECUTE.$error_id;
    }
  }

  // Add the walk track  
  if ($errc == 200) {
    $walk_id = $mysqli->insert_id;

    // Check the track is valid if it exists
    if ((array_key_exists("track",$walk) && !is_array($walk["track"]))) {
      reportInternalError("None",__FILE__,"Track is not an array in the input JSON",$emptyString);
      $errc = 500;
      $errm = $ERR_PARSE_JSON.$error_id;
    }
    $track = $walk["track"];

    // Prepare the statement and iterate over the points: for each, check the
    // JSON is valid and then run the statement
    if (!is_null($track)) {
      $stmt = $mysqli->prepare("INSERT INTO cwc_walk_track(
                               walk_id,
                               sequence_id,
                               create_date,
                               latitude,
                               longitude,
                               distance,
                               provider,
                               accuracy,
                               elapsed_time)
                      VALUES(?,?,?,?,?,?,?,?,?)");
      foreach($track as $trackpoint) {

        if ($errc == 200 &&
            !(array_key_exists("sequence",$trackpoint) && is_int($trackpoint["sequence"]) &&
              array_key_exists("created",$trackpoint) && is_string($trackpoint["created"]) &&
              array_key_exists("latitude",$trackpoint) && is_double($trackpoint["latitude"]) &&
              array_key_exists("longitude",$trackpoint) && is_double($trackpoint["longitude"]) &&
              array_key_exists("distance",$trackpoint) && is_double($trackpoint["distance"]) &&
              array_key_exists("provider",$trackpoint) && is_string($trackpoint["provider"]) &&
              array_key_exists("accuracy",$trackpoint) && is_double($trackpoint["accuracy"]) &&
              array_key_exists("elapsed_time",$trackpoint) && is_int($trackpoint["elapsed_time"]))) {
          reportInternalError("None",__FILE__,"Missing VALUE or invalid TYPE in TRACKPOINT JSON ".$trackpoint,$emptyString);
          $errc = 500;
          $errm = $ERR_PARSE_JSON.$error_id;
        }
        if ($errc == 200) {
          $stmt->bind_param("iisdddsdi",
                            $walk_id,
                            $trackpoint['sequence'],
                            $trackpoint['created'],
                            $trackpoint['latitude'],
                            $trackpoint['longitude'],
                            $trackpoint["distance"],
                            $trackpoint["provider"],
                            $trackpoint["accuracy"],
                            $trackpoint["elapsed_time"]);
          if (!$stmt->execute()) {
            reportInternalError("None",__FILE__,"Error executing INSERT INTO cwc_walk_track",$mysqli->error);
            $errc = 500;
            $errm = $ERR_SQL_EXECUTE.$error_id;
          }
        }
      }
    }
  }

  // Add the waypoints
  if ($errc == 200) {

    // Check the waypoints are valid if they exists
    if ((array_key_exists("waypoints",$walk) && !is_array($walk["waypoints"]))) {
      reportInternalError("None",__FILE__,"Waypoints is not an array in the input JSON",$emptyString);
      $errc = 500;
      $errm = $ERR_PARSE_JSON.$error_id;
    }
    $waypoints = $walk["waypoints"];

    // Prepare the statement and iterate over the points: for each, check the
    // JSON is valid and then run the statement
    if (!is_null($waypoints)) {
      $stmt = $mysqli->prepare("INSERT INTO cwc_walk_waypoint(
                               walk_id,
                               sequence_id,
                               latitude,
                               longitude)
                      VALUES(?,?,?,?)");
      foreach($waypoints as $waypoint) {
        if ($errc == 200 &&
            !(array_key_exists("sequence",$waypoint) && is_int($waypoint["sequence"]) &&
              array_key_exists("latitude",$waypoint) && is_double($waypoint["latitude"]) &&
              array_key_exists("longitude",$waypoint) && is_double($waypoint["longitude"]))) {
          reportInternalError("None",__FILE__,"Missing VALUE or invalid TYPE in WAYPOINTS JSON ".$json,$emptyString);
          $errc = 500;
          $errm = $ERR_PARSE_JSON.$error_id;
        }
        if ($errc == 200) {
          $stmt->bind_param("iidd",
                            $walk_id,
                            $waypoint['sequence'],
                            $waypoint['latitude'],
                            $waypoint['longitude']);
          if (!$stmt->execute()) {
            reportInternalError("None",__FILE__,"Error executing INSERT INTO cwc_walk_waypoint - error ",$mysqli->error);
            $errc = 500;
            $errm = $ERR_SQL_EXECUTE.$error_id;
          }
        }
      }
    }
  }

// Commit or rollback depending on the error situation
  if ($errc == 200) {
    $mysqli->commit();
  } else {
    $mysqli->rollback();
  }
}

if ($mysqli != null) {
    $mysqli->close();
}

// Write the response (whatever it was)
http_response_code($errc);
echo $errm;

exit();
?>
