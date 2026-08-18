<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KGV | Activity #2</title>
</head>
<body>
  <?php

$courses = ["BSIS", "ACT", "BSOM", "BTVTED"];

print_r($courses);

echo "<br>";
echo "<ol>";
for($i=0; $i<4; $i++){
    echo "<li>$courses[$i]</li>";
}
echo "</ol>";

$courses[3] = "BSCA";

echo "<br>";
echo "<ol>";
for($i=0; $i<4; $i++){
    echo "<li>$courses[$i]</li>";
}
echo "</ol>";

echo "<br>";
echo "<br>Associative Array:<br>";

$user = ["name" => "Kirby Vallinas", "age" => 19, "address" => "Guiguinto, Bulacan"];

echo "<b>Name: </b><br>";
echo $user["name"];
echo "</li><br>";

echo "<b>Age: </b><br>";
echo $user["age"];
echo "</li><br>";

echo "<b>Address: </b><br>";
echo $user["address"];
echo "</li><br>";

?>
</body>
</html>