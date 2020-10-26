<?php
// получение и фильтрация данных (либо по умолчанию)
if (isset($_POST['end']) && isset($_POST['start']) && $_POST['end']!='' && $_POST['start']!='') {
    $start_work = filter_var($_POST['start'], FILTER_VALIDATE_FLOAT);
    $end_work = filter_var($_POST['end'], FILTER_VALIDATE_FLOAT);
} else {
    $start_work = 9.5;
    $end_work = 16;
}
// конвертируем в строку
$start_work = toStr($start_work);
$end_work =  toStr($end_work);

$get_datetime = new DateTime('now', new DateTimeZone('America/Guadeloupe'));
$now_get_datetime = clone $get_datetime;    //текущая дата
$get_datetime_result = '';  // интервал в секундах до начала(конца) работы
$get_type = ''; // начало/конец (для светодиода и "закроются"/"откроются"

while ($get_datetime_result == '') {
    // проверка на субботу, воскресенье
    if (isWeekend($get_datetime)) {
        $get_datetime->add(new DateInterval("P1D"));
        $get_datetime->setTime(0, 0);
        continue;
    }
    // проверка на праздник
    if (isHoliday($get_datetime)) {
        $get_datetime->add(new DateInterval("P1D"));
        $get_datetime->setTime(0, 0);
        continue;
    }

    //дата начала рабочего дня (со временем)
    $cur_day= clone $get_datetime;
    $start_day = $cur_day->setTime(0, 0)->modify('+' . $start_work );
    //дата конца рабочего дня (со временем)
    $cur_day=  clone $get_datetime;
    $end_day = $cur_day->setTime(0, 0)->modify('+' .  $end_work);

    if ($get_datetime < $start_day) { // рабочий день, время до начала торгов
        $get_datetime_result = sumIntervals($start_day->diff($get_datetime, true),
            $now_get_datetime->diff($get_datetime, true));
        $get_type = 'start';
    } elseif ($get_datetime > $start_day && $get_datetime < $end_day) { // рабочий день, идут торги
        $get_datetime_result = sumIntervals($end_day->diff($get_datetime, true),
            $now_get_datetime->diff($get_datetime, true));
        $get_type = 'end';
    } else { // рабочий день, торги закончились
        $get_datetime->add(new DateInterval("P1D"));
        continue;
    }
    // перевод в интервал
    $time_rest = $get_datetime_result->s + $get_datetime_result->i * 60 + $get_datetime_result->h * 3600 + $get_datetime_result->d * 24 * 3600;

    $json = json_encode([
        'time_rest' => $time_rest,
        'type' => $get_type
    ]);
    echo $json;

}

/**
 * Проверяет является ли текущая дата выходным
 *
 * @param $date
 * @return bool
 */
function isWeekend($date)
{
    return $date->format('N')>5;
}

/**
 * Проверяет является ли текущая дата праздником
 *
 * @param $date
 * @return false
 */
function isHoliday($date)
{
    $currentYear = date("Y");
    $holidays = [
        getObservedDate(new DateTime("1 jan $currentYear")),//
        getObservedDate(new DateTime("third monday of january $currentYear")),//
        getObservedDate(new DateTime("third monday of feb $currentYear")),//
        getObservedDate(new DateTime("10 apr $currentYear")),
        getObservedDate(new DateTime("last monday of may $currentYear")),//
        getObservedDate(new DateTime("4 july $currentYear")),
        getObservedDate(new DateTime("first monday of september $currentYear")),
        getObservedDate(new DateTime("last thursday of november $currentYear")),//
        getObservedDate(new DateTime("25 dec $currentYear")),//
    ];

    foreach($holidays as $holiday) {
        if ($holiday->diff($date)->days == 0)
            return true;
    }
    return false;
}

/**
 * Смещает дату на один, если праздник попадает на субботу или воскресенье
 *
 * @param $holidayDate
 * @return mixed
 */
function getObservedDate($holidayDate)
{
    $dayofweek = $holidayDate->format('N');

    if ($dayofweek == 6) $holidayDate->sub(new DateInterval("P1D")); //суббота на пятницу
    else if ($dayofweek == 0)  $holidayDate->add(new DateInterval("P1D"));  //воскресенье на понедельник
    return $holidayDate;
}

/**
 * Суммирует два интервала
 *
 * @param $first
 * @param $second
 * @return DateInterval|false
 */
function sumIntervals($first, $second)
{
    $dt = new DateTime();
    $dt_diff = clone $dt;
    $dt->add($first);
    $dt->add($second);
    return $dt->diff($dt_diff);
}

/**
 * Конвертирует число в строку
 * 9 => 9 hours, 9.5 => 9 hours 30 minutes
 *
 * @param $num
 * @return string
 */
function toStr($num) {
    $c = floor($num);
    $d = ($num - $c)*60;
    if ($d>0)
        return $c . ' hours ' . $d . ' minutes';
    else
        return $c . ' hours ';
}
