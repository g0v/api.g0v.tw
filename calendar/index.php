<?php

require_once(__DIR__ . '/../vendor/autoload.php');
use Google\Client;
use Google\Service\Calendar;
use RRule\RRule;

include(__DIR__ . '/../config.php');

if ($_GET['date'] ?? false) {
    $date = strtotime($_GET['date']);
} else {
    $date = strtotime(date('Y-m-01'));
}
$start_date = strtotime('-1 month', $date);
$end_date = strtotime('+2 month', $date);

// 取得前後各一個月的所有資料

$curl = curl_init();
$limit = 100;
$url = 'https://www.googleapis.com/calendar/v3/calendars/cpcf6iv5pt9l6gl2ue3svo63e8@group.calendar.google.com/events?'
    . 'key=' . getenv('GOOGLE_API_KEY')
    . '&timeMin=' . date('Y-m-d', $start_date) . 'T00:00:00Z'
    . '&timeMax=' . date('Y-m-d', $end_date) . 'T00:00:00Z'
    . '&maxResults=' . $limit;
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$obj = curl_exec($curl);
curl_close($curl);

$ret = new StdClass;
$ret->start_date = date('Y-m-d', $start_date);
$ret->end_date = date('Y-m-d', $end_date);
$ret->items = [];
$obj = json_decode($obj);
foreach ($obj->items as $item) {
    $recurrences = $item->recurrence ?? [];
    if ($recurrences) {
        // RRULE:FREQ=WEEKLY;INTERVAL=2;BYDAY=WE
        // EXDATE:TZID=Asia/Taipei:20230118T210000,20230215T210000,20240214T210000
        $config = [];
        $config['DTSTART'] = date('Y-m-d', $start_date);
        $config['UNTIL'] = date('Y-m-d', $end_date);
        $exdate = [];

        foreach ($recurrences as $recurrence) {
            if (preg_match('#RRULE:(.*)#', $recurrence, $matches)) {
                foreach (explode(';', $matches[1]) as $rule) {
                    list($key, $value) = explode('=', $rule);
                    $config[$key] = $value;
                }
            } else if (preg_match('#EXDATE;TZID=Asia/Taipei:([0-9,T]+)#', $recurrence, $matches)) {
                $exdate  = explode(',', $matches[1]);
            } else {
                print_R($recurrence);
                exit;
            }
        }
        $rrule = new RRule($config);
        foreach ($rrule as $occurrence ) {
            $result = new StdClass;
            $result->id = $item->id;
            $result->summary = $item->summary;
            $result->description = $item->description;
            $result->location = $item->location;
            $result->htmlLink = $item->htmlLink;
            $result->start = $occurrence->format('Y-m-d') . 'T' . date('H:i:s', strtotime($item->start->dateTime));
            $result->end = $occurrence->format('Y-m-d') . 'T' . date('H:i:s', strtotime($item->end->dateTime));
            $result->period = 'weekly';
            $ret->items[] = $result;
        }
    } else {
        $result = new StdClass;
        $result->id = $item->id;
        $result->summary = $item->summary;
        $result->description = $item->description;
        $result->location = $item->location;
        $result->htmlLink = $item->htmlLink;
        $result->start = $item->start->dateTime ?? $item->start->date;
        $result->end = $item->end->dateTime ?? $item->end->date;
        $result->period = 'once';
        $ret->items[] = $result;
    }

}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
usort($ret->items, function($a, $b) {
    return $a->start > $b->start;
});
echo json_encode($ret);
