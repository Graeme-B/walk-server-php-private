<?php
$walk_id = $_POST["id"];
$country = $_POST["country"];
$year    = $_POST["year"];
$course  = $_POST["course"];
$class   = $_POST["class"];

/*
$str = "";
foreach ($_POST as $key => $value) {
 $str = $str.$key." ".$value."\n";
}
debug($str);
*/

function getId($value,$query) {
  $results = query($query,"s",$value);
  if (sizeof($results) > 0)
  {
    $id = $results[0]["id"];
  } else {
    $id = -1;
  }
  return $id;
}

function sortOutId($value,$query,$insert) {
  if (!is_null($value) && strlen($value) > 0) {
    $id = getId($value,$query);
    if ($id < 0) {
      $result = query($insert,"s",$value);
      $id = getId($value,$query);
    }
  } else {
    $id = -1;
  }
  return $id;
}

$country_id = sortOutId($country,"SELECT id FROM cwc_country WHERE country_name = ?","INSERT INTO cwc_country(country_name) VALUES(?)");
$year_id    = sortOutId($year,"SELECT id FROM cwc_year WHERE year = ?","INSERT INTO cwc_year(year) VALUES(?)");
$course_id  = sortOutId($course,"SELECT id FROM cwc_course WHERE course_name = ?","INSERT INTO cwc_course(course_name) VALUES(?)");
$class_id   = sortOutId($class,"SELECT id FROM cwc_class WHERE class = ?","INSERT INTO cwc_class(class) VALUES(?)");

// if (!is_null($country) && strlen($country) > 0) {
//   $country_id = getId($country,"SELECT id FROM cwc_country WHERE country_name = ?");
//   if ($country_id < 0) {
//       $result = query("INSERT INTO cwc_country(country_name)
//                      VALUES(?)","s",$country);
//       $country_id = getId($country,"SELECT id FROM cwc_country WHERE country_name = ?");
//   }
// } else {
//   $country_id = -1;
// }
                 
// if (!is_null($course) && strlen($course) > 0) {
//   $course_id = getId($course,"SELECT id FROM cwc_course WHERE course_name = ?");
//   if ($course_id < 0) {
//       $result = query("INSERT INTO cwc_course(course_name)
//                      VALUES(?)","s",$course);
//       $course_id = getId($course,"SELECT id FROM cwc_course WHERE course_name = ?");
//   }
// } else {
//   $course_id = -1;
// }

$result = query("UPDATE cwc_walk
                 SET approved = 'approved',
                     country_id = ?,
                     year_id = ?,
                     course_id = ?,
                     class_id = ?
                 WHERE id = ?","iiiii",$country_id,$year_id,$course_id,$class_id,$walk_id);
if (count($result) == 0)
{
  $result['result'] = 'success';
}

header('Content-type: application/json');
echo json_encode($result);
exit();
?>

