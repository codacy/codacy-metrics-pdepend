<?php
// #Metrics: {"complexity": 5, "loc": 38, "cloc": 3, "nrMethods": 2, "nrClasses": 0}
// #LineComplexity: 2
function merge_sort($my_array)
{
    if (count($my_array) == 1) return $my_array;
    $mid = count($my_array) / 2;
    $left = array_slice($my_array, 0, $mid);
    $right = array_slice($my_array, $mid);
    $left = merge_sort($left);
    $right = merge_sort($right);
    return merge($left, $right);
}
// #LineComplexity: 5
function merge($left, $right)
{
    $res = [];
    while (count($left) > 0 && count($right) > 0) {
        if ($left[0] > $right[0]) {
            $res[] = $right[0];
            $right = array_slice($right, 1);
        } else {
            $res[] = $left[0];
            $left = array_slice($left, 1);
        }
    }
    while (count($left) > 0) {
        $res[] = $left[0];
        $left = array_slice($left, 1);
    }
    while (count($right) > 0) {
        $res[] = $right[0];
        $right = array_slice($right, 1);
    }
    return $res;
}
$test_array = [100, 54, 7, 2, 5, 4, 1];
echo "Original Array : ";
echo implode(', ', $test_array);
echo "\nSorted Array :";
echo implode(', ', merge_sort($test_array)) . "\n";
