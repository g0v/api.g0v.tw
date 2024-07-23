<?php

$curl = curl_init();
$url = 'https://g0v.hackmd.io/@jothon/community99';
curl_setopt($curl, CURLOPT_URL, $url);
// ipv4
curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_HEADER, false);
$content = curl_exec($curl);
preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches);

$urls = [];
foreach ($matches[1] as $idx => $name) {
    if (!preg_match('#(\d\d\d\d\/\d\d)#', $name, $m)) {
        continue;
    }
    $urls[] = [$m[1], $matches[2][$idx]];
}

$ret = new StdClass;
$ret->notes = [];
for ($i = 0; $i < 10; $i ++) {
    $url = 'https://g0v.hackmd.io' . $urls[$i][1];
    $tmp_file = '/tmp/community99-' . str_replace('/', '', $urls[$i][0]) . '.md';
    if (!file_exists($tmp_file)) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Accept: */*',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
        ]);
        $content = curl_exec($curl);
        file_put_contents($tmp_file, $content);
    }
    $doc = new DOMDocument();
    @$doc->loadHTMLFile($tmp_file);
    $markdown = $doc->getElementById('doc')->textContent;
    $note = new StdClass;
    $note->year_month = $urls[$i][0];
    $note->url = $url;
    if (preg_match('#^description: (.*)\n#m', $markdown, $m)) {
        $note->description = $m[1];
    }
    if (preg_match('#^\# (.*)\n#m', $markdown, $m)) {
        $note->title = $m[1];
    }
    if (preg_match('#\!\[\]\(([^)]+)\)#', $markdown, $m)) {
        $note->image = $m[1];
    }
    preg_match_all('#^\#\#\# (.*)\n#m', $markdown, $matches);
    $note->sections = [];
    foreach ($matches[1] as $section) {
        $note->sections[] = $section;
    }
    $ret->notes[] = $note;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($ret, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
