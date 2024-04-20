<?php

$url = 'https://g0v-jothon.kktix.cc/events.json?locale=zh-TW';
$obj = json_decode(file_get_contents($url));
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($obj);
