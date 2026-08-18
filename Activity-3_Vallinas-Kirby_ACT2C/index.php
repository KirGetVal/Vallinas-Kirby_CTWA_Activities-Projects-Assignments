<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KGV | Activity #3</title>
</head>

<body>
  <?php
// Activity 3: Grade Evaluator
// Vallinas-Kirby_ACT2C

$vallinas_average = 91;

$vallinas_remarks = ($vallinas_average >= 74.5) ? "Passed" : "Failed";

if ($vallinas_average >= 90 && $vallinas_average <= 100) {
    $vallinas_letterGrade = "A";
} elseif ($vallinas_average >= 80 && $vallinas_average <= 89) {
    $vallinas_letterGrade = "B";
} elseif ($vallinas_average >= 70 && $vallinas_average <= 79) {
    $vallinas_letterGrade = "C";
} elseif ($vallinas_average >= 60 && $vallinas_average <= 69) {
    $vallinas_letterGrade = "D";
} else {
    $vallinas_letterGrade = "F";
}


echo "Average Grade: " . $vallinas_average . "<br>";
echo "Remarks: " . $vallinas_remarks . "<br>";
echo "Letter Grade: " . $vallinas_letterGrade . "<br>";
?>
</body>

</html>