<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KGV | Activity #1</title>
</head>
<body>
    <marquee><h1>KIRBY G. VALLINAS</h1></marquee>

    <?php
    $name = "KIRBY";
    $age = 19;
    $human = true;

    var_dump($name);
    echo "<br>";
    var_dump($age);
    echo "<br>";
    var_dump($human);
    echo "<br>";

    printf("my name is %s.", $name);
    echo "<br>";
    $array = ["A", "B", "C"];

    print_r($array);

    echo "<br>";
    $cys = "ACT 2C";
    echo strlen($cys);

    echo "<br>";
    $cys = "hello i am albert.";
    echo str_word_count($cys);

    echo "<br>";
    $cys = "Albert";
    echo strrev($cys);

    echo "<br>";
    $cys = "I LIKE CATS";
    echo str_replace("i like", "love", $cys);

    echo "<br>";
    $cys = "I LIKE CATS";
    echo strpos($cys, "CATS");
    ?>

</body>
</html>