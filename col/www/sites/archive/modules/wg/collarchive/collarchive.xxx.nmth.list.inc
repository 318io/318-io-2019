<?php
use Drupal\wg\WGEntity;
function _xx_nmth_mmlist() {
  for($i=0; $i<=10; $i++) {
    $rowsperfile = 3000;
    $start = $i*$rowsperfile;
    dnotice($start);
    $nids = db_query('SELECT * FROM node n left join field_data_field_mainformat m on m.entity_id=n.nid where type= :type and field_mainformat_tid = 89 order by nid limit '.$start.',  '.$rowsperfile, [':type'=> 'collection'])->fetchCol();
    if(!$nids)break;
    $out = _xx_nmth_mmlist0($nids, $start);
    file_put_contents('/tmp/318nmthlist.'.$i.'.html', $out);
  }
}

function _xx_nmth_mmlist0($nids, $start) {
  $r = '
       <!DOCTYPE html>
       <html lang="zh-hant" dir="ltr">
       <head>
       <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
       <title>archive.318.io</title>
       <style>
       body{
       font-size:12px;
     }
       table, th, td {
       border: 1px solid #666;
       border-collapse: collapse;
       padding:2px;
       verticle-align:top;
     }
       tr.row{
     }
       table{
       page-break-after: always;
     }
       img{
       max-width:60px;
       max-height:60px;
       padding:2px;
     }
       .posi{
       font-size:10px;
     }
       .head{
       font-size:10px;
     }
       </style>
       </head>
       <body>
       ';
  $r .= date('Y-m-d H:i', time());
  $i = 0;
  $table_header = '<table><tr class="head"><td style="width:30px">項次</td><td style="width:40px">識別號</td><td style="width:90px">原件典藏編號</td><td style="width:65px">形式</td><td style="width:100px">內容分類</td><td style="width:430px">數位原始檔儲存位置</td><td style="width:80px">權利依據</td></tr>';
  $table_footer = '</table>';

  $r .= $table_header;
  foreach($nids as $nid) {
    if($i> 1 && $i%30 == 0) {
      $r .= $table_footer;
      $r .= $table_header;
    }
    $identifier = $nid;

    $node = node_load($nid, null, true);
    $desc = trim(strip_tags($node->field_content['und'][0]['value'])).trim(strip_tags($node->field_description['und'][0]['value']));
    $desc = str_replace(["\r", "\n", "、", "，", "。"], "", $desc);
    $desc = mb_substr($desc, 0,10);
    $field_digi_path = trim(strip_tags($node->field_digi_path['und'][0]['value']));
    $field_digi_path = str_replace(["\r", "\n", "、", "，", "。"], "", $field_digi_path);

    $i++;

    $r .= '<tr class="row">';
    $r .= '<td>'.($i+$start).'</td>';
    $r .= '<td>'.$node->nid.'</td>';
    $r .= '<td>'.$node->field_repository_id['und'][0]['value'].'</td>';
    $r .= '<td>'._xx_nmth_list_getterm($node, 'field_format_category').'</td>';
    $r .= '<td>'._xx_nmth_list_getterm($node, 'field_content_category').'</td>';

    $r .= '<td class="posi">'.$field_digi_path.'</td>';
    $r .= '<td>'.$node->field_rightgranted_note['und'][0]['value'].'</td>';
    $r .= '</tr>';
  }
  $r .= $table_footer;
  $r .= '</body></html>';
  return $r;
}



function _xx_nmth_list() {
  for($i=0; $i<=10; $i++) {
    $rowsperfile = 1300;
    $start = $i*$rowsperfile;
    dnotice($start);
    $nids = db_query('SELECT * FROM node n left join field_data_field_mainformat m on m.entity_id=n.nid where type= :type and field_mainformat_tid != 89 order by nid limit '.$start.',  '.$rowsperfile, [':type'=> 'collection'])->fetchCol();
    if(!$nids)break;
    $out = _xx_nmth_list0($nids, $start);
    file_put_contents('/tmp/318nmthlist.'.$i.'.html', $out);
  }
}

function _xx_nmth_list0($nids, $start) {
  $r = '
       <!DOCTYPE html>
       <html lang="zh-hant" dir="ltr">
       <head>
       <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
       <title>archive.318.io</title>
       <style>
       body{
       font-size:14px;
     }
       table, th, td {
       border: 1px solid #666;
       border-collapse: collapse;
       padding:2px;
     }
       table{
       page-break-after: always;
     }
       img{
       max-width:60px;
       max-height:60px;
       padding:2px;
     }
       </style>
       </head>
       <body>
       ';
  $r .= date('Y-m-d H:i', time());
  $i = 0;
  $table_header = '<table><tr><td>項次</td><td style="width:130px">縮圖</td><td>識別號</td><td>原件典藏編號</td><td>主要形式</td><td>形式分類</td><td>測量</td><td>材質</td><td>描述</td></tr>';
  $table_footer = '</table>';

  $r .= $table_header;
  foreach($nids as $nid) {
    if($i> 1 && $i%13 == 0) {
      $r .= $table_footer;
      $r .= $table_header;
    }
    $identifier = $nid;

    $icon = '';
    $files = _coll_get_digifiles($identifier, 'public', 'jpg');
    if($files) {
      $f = array_shift($files);
      $icon = _wg_image_style('large', $f, '');
      if($files) {
        $f =  array_shift($files);
        $icon .= _wg_image_style('large', $f, '');
      }
      $icon = str_replace('http://archive.318.io', 'http://the318io:318iotheone@archive.318.io', $icon);
    }
    $node = node_load($nid, null, true);
    $desc = trim(strip_tags($node->field_content['und'][0]['value'])).trim(strip_tags($node->field_description['und'][0]['value']));
    $desc = str_replace(["\r", "\n", "、", "，", "。"], "", $desc);
    $desc = mb_substr($desc, 0,10);
    $i++;

    $r .= '<tr>';
    $r .= '<td>'.($i+$start).'</td>';
    $r .= '<td>'.$icon.'</td>';
    $r .= '<td>'.$node->nid.'</td>';
    $r .= '<td>'.$node->field_repository_id['und'][0]['value'].'</td>';
    $r .= '<td>'._xx_nmth_list_getterm($node, 'field_mainformat').'</td>';
    $r .= '<td>'._xx_nmth_list_getterm($node, 'field_format_category').'</td>';
    $r .= '<td>'.$node->field_measurement['und'][0]['value'].'</td>';
    $r .= '<td>'._xx_nmth_list_getterm($node, 'field_material').'</td>';
    $r .= '<td>'.$desc.'</td>';
    $r .= '</tr>';
  }
  $r .= $table_footer;
  $r .= '</body></html>';
  return $r;
}

function _xx_nmth_list_getterm($node, $fn, $sep = '、') {
  $ar = $node->$fn;
  $xx = [];
  if($ar) {
    foreach($ar['und'] as $i) {
      $tid = $i['tid'];
      $xx[] = _xx_get_term_name($tid);
    }
  }
  if(!$xx) {
    dnotice('ep term: '.$node->nid.' '.$fn);
  }
  return implode($sep, $xx);
}

function _xx_get_term_name($tid) {
  return db_select('taxonomy_term_data', 't')
         ->fields('t', array('name'))
         ->condition('tid', $tid)
         ->execute()
         ->fetchField();
}

function _xx_fputcsv($fh, array $fields) {
  $delimiter = ',';
  $enclosure = '"';
  $delimiter_esc = preg_quote($delimiter, '/');
  $enclosure_esc = preg_quote($enclosure, '/');

  $output = array();
  foreach ($fields as $field) {
    $field = str_replace('"', '＂', $field);
    $s = $field;
    if(preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
      $s = str_replace($enclosure, $enclosure . $enclosure, $field);
    } else {
      $s = $field;
    }
    $s = str_replace(["\r\n", "\n"], '\r\n', $s);
    $s = $enclosure .$s . $enclosure;

    $output[] = $s;
  }

  fwrite($fh, join($delimiter, $output) . "\n");
}

function _xx_nmth_csvlist() {
  $csv = '/tmp/318nmthlist.csv';
  $fp = fopen($csv, 'w');

  $nids = db_query('SELECT * FROM node n left join field_data_field_mainformat m on m.entity_id=n.nid where type= :type and field_mainformat_tid != 89 order by nid', [':type'=> 'collection'])->fetchCol();

  $headers = [
               'sn' =>'項次',
               'img' => '縮圖',
               'id' => '識別號',
               'oid' => '原件典藏編號',
               'mf' => '主要形式',
               'fc' => '形式分類',
               'me' => '測量',
               'ma' => '材質',
               'de' => '描述',
             ];
  DT::fputcsv($fp, $headers);
  $sn = 0;
  foreach($nids as $nid) {
    $sn++;
    $ar = _xx_nmth_csvlist_row($nid, $sn);
    if($ar) {
      dnotice($nid);
      _xx_fputcsv($fp, $ar);
    } else {
      echo $nid."\n";
      die('error');
    }

  }
  fclose($fp);
}

function _xx_nmth_csvlist_row($nid, $sn) {
  $d = [
               'sn' =>'項次',
               'img' => '縮圖',
               'id' => '識別號',
               'oid' => '原件典藏編號',
               'mf' => '主要形式',
               'fc' => '形式分類',
               'me' => '測量',
               'ma' => '材質',
               'de' => '描述',
       ];

  $node = node_load($nid, null, false);

  $d['sn'] = $sn;
  $d['img'] = '';
  $d['id'] = $nid;

  $d['oid'] = $node->field_repository_id['und'][0]['value'];
  $d['mf'] = _xx_nmth_list_getterm($node, 'field_mainformat');
  $d['fc'] = _xx_nmth_list_getterm($node, 'field_format_category');
  $d['me'] = $node->field_measurement['und'][0]['value'];
  $d['ma'] = _xx_nmth_list_getterm($node, 'field_material');



    $desc = trim(strip_tags($node->field_content['und'][0]['value'])).trim(strip_tags($node->field_description['und'][0]['value']));
    $desc = str_replace(["\r", "\n", "、", "，", "。"], "", $desc);
    $desc = mb_substr($desc, 0,10);
  $d['de'] = $desc;


  return $d;

}
