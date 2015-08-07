<?php
function stable_usort(&$array, $cmp_function) {
    if(count($array) < 2) {
    return;
    }

    $halfway = count($array) / 2;
    $array1 = array_slice($array, 0, $halfway, TRUE);
    $array2 = array_slice($array, $halfway, NULL, TRUE);

    stable_usort($array1, $cmp_function);
    stable_usort($array2, $cmp_function);

    if(call_user_func($cmp_function, end($array1), reset($array2)) < 1) {
        $array = $array1 + $array2;
        return;
    }

    $array = array();
    reset($array1);
    reset($array2);
    while(current($array1) && current($array2)) {
    if(call_user_func($cmp_function, current($array1), current($array2)) < 1) {
    $array[key($array1)] = current($array1);
    next($array1);
    } else {
    $array[key($array2)] = current($array2);
    next($array2);
    }
    }
    while(current($array1)) {
    $array[key($array1)] = current($array1);
    next($array1);
    }
    while(current($array2)) {
    $array[key($array2)] = current($array2);
    next($array2);
    }
return;
}
?>