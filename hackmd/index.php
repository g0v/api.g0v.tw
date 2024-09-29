<?php

if ($_GET['tag'] ?? false) {
    $url = sprintf("https://raw.githubusercontent.com/g0v-data/g0v-hackmd-archive/main/tags/%s.md", rawurlencode($_GET['tag']));
    $content = file_get_contents($url);

    $summaries = file_get_contents(sprintf("https://raw.githubusercontent.com/g0v-data/g0v-hackmd-archive/main/tags_summary/%s.json", rawurlencode($_GET['tag'])));
    $summaries = json_decode($summaries) ?? new StdClass;

    $lines = explode("\n", $content);
    $records = [];
    foreach ($lines as $line) {
        if (strpos($line, '|') !== 0) {
            continue;
        }
        $line = trim($line, '|');
        $terms = explode('|', $line);
        $terms = array_map('trim', $terms);
        if (strpos($terms[1], '[') !== 0) {
            continue;
        }
        $record = new StdClass;
        if (!preg_match('#\[(.+)\]\((.+)\)#', $terms[1], $matches)) {
            continue;
        }
        $record->title = $matches[1];
        $record->id = explode('.', explode('/', $matches[2])[2])[0];
        $record->updated_at = $terms[2];
        $record->created_at = $terms[3];
        if (property_exists($summaries, $record->id)) {
            $record->summary = $summaries->{$record->id};
        } else {
            $record->summary = '';
        }

        $records[] = $record;
    }
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($records, JSON_PRETTY_PRINT);
    exit;
}
$content = file_get_contents('https://raw.githubusercontent.com/g0v-data/g0v-hackmd-archive/main/README.md');
$lines = explode("\n", $content);
$records = [];
foreach ($lines as $line) {
    if (strpos($line, '|') !== 0) {
        continue;
    }
    $line = trim($line, '|');
    $terms = explode('|', $line);
    $terms = array_map('trim', $terms);
    if (strpos($terms[0], '[') !== 0) {
        continue;
    }
    $record = new StdClass;
    if (!preg_match('#\[(.+)\]\((.+)\)#', $terms[0], $matches)) {
        continue;
    }
    $record->title = $matches[1];
    $record->count = intval($terms[1]);
    $record->updated_at = $terms[2];

    $records[] = $record;
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($records, JSON_PRETTY_PRINT);
