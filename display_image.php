<html>
<head>
<style>
a {
  text-decoration: none;
  display: inline-block;
  padding: 8px 16px;
}

a:hover {
  background-color: #ddd;
  color: black;
}

.previous {
  background-color: #04AA6D;
  color: white;
}

.next {
  background-color: #04AA6D;
  color: white;
}

span {
  vertical-align: top;
}

.round {
  border-radius: 50%;
}
</style>
</head>
<body>
    
<?php
include_once 'common_functions.php';

// https://stackoverflow.com/questions/38093549/javascript-place-marker-on-image-onclick
// https://www.w3schools.com/howto/howto_css_next_prev.asp
// https://stackoverflow.com/questions/2906582/how-do-i-create-an-html-button-that-acts-like-a-link

// Check that the walk id is valid

// Get the walk and image id
$walk_id = $_GET["walk_id"];
$image_id = $_GET["image_id"];

$image_path = [];
$res = query("SELECT image_name
              FROM cwc_walk_image
              WHERE walk_id = ?
              ORDER BY sequence_id;","i",$walk_id);
if (sizeof($res) > 0)
{
   foreach($res as $row) {
       $image_path[] = $row["image_name"];
   }
}

// Ensure we have a valid image id
if ($image_id < 0) $image_id = 0;
else if ($image_id >= sizeof($image_path)) $image_id = sizeof($image_path) - 1;

printf("<div>\n");

// Display the image: display PREV and NEXT buttons according to where we are in the image array
if ($image_id > 0) {
  printf("<span><a href=\"actions.php?operation=display_image&walk_id=%d&image_id=%d\" class=\"previous\">&laquo; Previous</a></span>\n",$walk_id,$image_id - 1);
}

printf("<img style='vertical-align:middle' src=%s/%s>\n",CONFIG_IMAGE_DIR,$image_path[$image_id]);
if ($image_id < sizeof($image_path) - 1) {
  printf("<span><a href=\"actions.php?operation=display_image&walk_id=%d&image_id=%d\" class=\"next\">Next &raquo;</a></span>\n",$walk_id,$image_id + 1);
}
printf("<div>\n");

?>

</body>
</html>
