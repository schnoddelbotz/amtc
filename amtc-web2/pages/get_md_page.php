<?php

/// this ___IS___ a hack and will leave soon

error_reporting(E_ALL);
ini_set('display_errors','on');

header('Content-type: application/json');

$pageno = sprintf("%d", basename($_SERVER['REQUEST_URI']));
$file = sprintf("%d.md", $pageno);

$contents = 'Not found';
if (is_readable($file)) {
  $contents = file_get_contents($file);
}

$arr = array(
  'page'=>array(
    'id' => $pageno,
    'page_name' => 'unused',
    'page_title' => 'unused',
    'page_content' => $contents
  )
);

echo json_encode($arr);
