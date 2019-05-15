<?php

/*
  $header = ['head1', 'head2', 'head3'];
  $lines = [
    ['data1', 'data2', 'data3', 'data4'],
    ['data1', 'data2', 'data3', 'data4'],
    ['data1', 'data2', 'data3', 'data4'],
  ]
*/

function wg_export_csv($header, $lines, $filename)
{
  drupal_add_http_header('Content-Encoding', 'UTF-8');
  drupal_add_http_header('Content-type', 'text/csv; charset=UTF-8');
  drupal_add_http_header('Content-Disposition', 'attachment;filename='.$filename);
  $fp = fopen('php://output', 'w');
  
  // for testing
  //$fp = fopen("/tmp/{$filename}", 'w');

  fputcsv($fp, $header, ',', '"', "\0"); // evirt mod for rfc 4180
  foreach ($lines as $line) {
    $line = str_replace(['&nbsp;'], ' ', $line);
    fputcsv($fp, $line, ',', '"', "\0"); // evirt mod for rfc 4180
    //fputcsv($fp, strip_nl($line));
  }
  fclose($fp);
  drupal_exit();
}

function wg_save_csv($header, $lines, $filename)
{
  $fp = fopen("/tmp/{$filename}", 'w');
  
  fputcsv($fp, $header, ',', '"', "\0"); // evirt mod for rfc 4180
  foreach ($lines as $line) {
    $line = str_replace(['&nbsp;'], ' ', $line);
    fputcsv($fp, $line, ',', '"', "\0"); // evirt mod for rfc 4180
    //fputcsv($fp, strip_nl($line));
  }
  fclose($fp);
}


function test_wg_export_csv()
{
  $header = ['head1', 'head2', 'head3'];
  $lines = [
    ['data1', 'data2', 'data3', 'data4'],
    ['data1', 'data2', 'data3', 'data4'],
    ['data1', 'data2', 'data3', 'data4'],
  ];

  wg_export_csv($header, $lines, 'aaa.csv');
  
}

function export_zip_file($zip_file)
{
  $file_name = basename($zip_file);
  header("Content-Type: application/zip");
  header("Content-Disposition: attachment; filename={$file_name}");
  header("Content-Length: " . filesize($zip_file));
  readfile($zip_file);
  exit;
}
