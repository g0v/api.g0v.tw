<?php

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
echo json_encode($records, JSON_PRETTY_PRINT);
