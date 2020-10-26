<?php

if (isset($_POST['end']) && isset($_POST['start'])) {
    $start_work = filter_var($_POST['start'], FILTER_VALIDATE_FLOAT);
    $end_work = filter_var($_POST['end'], FILTER_VALIDATE_FLOAT);
} else {
    $start_work = 9.5;
    $end_work = 16;
}

date_default_timezone_set('Etc/GMT-4');
const MSK = 7;  // если сервер настроен на UTC, то 3
const USA = 0; // если сервер настроен на UTC, то -4

$date = new DateTime();
//echo $date->getTimestamp();
$now_datetime = $date->getTimestamp();
$now_datetime_msk = $date->getTimestamp() + (MSK * 3600);

$get_datetime = $now_datetime;
$get_datetime_result = '';
$get_type = '';

while ($get_datetime_result == '') {
    if (isWeekend($get_datetime)) {
        $get_datetime = plusOneDay($get_datetime);
        continue;
    }
    if (isHoliday($get_datetime)) {
        $get_datetime = plusOneDay($get_datetime);
        continue;
    }

    $curDay = getCurrentDay($get_datetime);
    $start_day = $curDay + (3600 * ($start_work+1));
    $end_day = $curDay + (3600 * ($end_work+1) );

    if ($get_datetime < $start_day) { // рабочий день, время до начала торгов
        $get_datetime_result = ($start_day - $get_datetime + ($get_datetime - $now_datetime)); //до начала
        $get_type = 'start';
    } elseif ($get_datetime > $start_day && $get_datetime < $end_day) { // рабочий день, идут торги
        $get_datetime_result = $end_day - $get_datetime;
        $get_type = 'end';
    } else { // рабочий день, торги закончились
        $get_datetime = plusOneDay($get_datetime);
        continue;
    }
    $json = json_encode([
        'time_in_usa' => $now_datetime,
        'time_in_msk' => $now_datetime_msk,
        'time_rest' => $get_datetime_result,
        'type' => $get_type
    ]);
    echo $json;

}


/**
 * Увеличивает дату на один день
 *
 * @param $date
 * @return false|int
 */
function plusOneDay($date)
{
    $new_date = strtotime('+1 day', $date);
    $new_date = strtotime('00:00', $new_date);
    return $new_date;
}

/**
 * Возвращает полночь текущей даты
 *
 * @param $date
 * @return false|int
 */
function getCurrentDay($date)
{
    return strtotime(date('Y-m-d', $date) . ' midnight');
}

/**
 * Проверяет является ли текущая дата выходным
 *
 * @param $date
 * @return bool
 */
function isWeekend($date)
{



    $weekday= date("l", $date );

    if ($weekday =="Saturday" OR $weekday =="Sunday") { return true; }
    else {return false; }
}

/**
 * Проверяет является ли текущая дата праздником
 *
 * @param $date
 * @return false
 */
function isHoliday($date)
{
    return false;
}
