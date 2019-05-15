<?php
use Drupal\wg\WGEntity;

function _xx_nmth_metadata() {
  $nids = db_query('SELECT * FROM node n left join field_data_field_mainformat m on m.entity_id=n.nid where type= :type and field_mainformat_tid != 89 order by nid', [':type'=> 'collection'])->fetchCol();
  if(!$nids) return;
  $csv = '/tmp/318nmthmeta.csv';
  $fp = fopen($csv, 'w');

  $headers = [
               'n_a' => '物件編號',
               'n_a2' => '原件典藏編號',
               'n_b' => '識別號',
               'n_c' => 'whole',
               'n_d' => '物件性質',
               'n_e' => '中文名稱',
               'n_f' => '外文名稱',
               'n_g' => '實體件數',
               'n_h' => '年代-歷史分期',
               'n_i' => '年代-準確日期(起)',
               'n_j' => '年代-準確日期(迄)',
               'n_k' => '年代-推測年份(起)',
               'n_l' => '年代-推測年份(迄)',
               'n_m' => '年代-年代描述',
               'n_n' => '類別一-第二層分類',
               'n_o' => '類別一-第三層分類',
               'n_p' => '類別一-第四層分類',
               'n_q' => '類別二-第二層分類',
               'n_r' => '類別二-第三層分類',
               'n_s' => '類別二-第四層分類',
               'n_t' => '主要材質',
               'n_u' => '尺寸重量-長(X軸):cm',
               'n_v' => '尺寸重量-寬(Y軸):cm',
               'n_w' => '尺寸重量-高(Z軸):cm',
               'n_x' => '尺寸重量-直徑:cm',
               'n_y' => '尺寸重量-重量:kg',
               'n_z' => '創作者/製造者',
               'n_aa' => '產地源始/製造地',
               'n_ab' => '狀況描述-保存狀況等級',
               'n_ac' => '狀況描述-說明',
               'n_ad' => '價值-市場價值',
               'n_ae' => '價值-購買金額',
               'n_af' => '價值-保險金額',
               'n_ag' => '關鍵詞',
               'n_ah' => '文物描述',
               'n_ai' => '參考資料',
               'n_aj' => '備註',
               'n_ak' => '蒐藏宗旨',
               'n_al' => '蒐藏方向',
               'n_am' => '藏品分級',
               'n_an' => '分級說明',
               'n_ao' => '數位檔資訊',
             ];
  DT::fputcsv($fp, $headers);
  foreach($nids as $nid) {
    $ar = _xx_nmth_metadata_row($nid);
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

function _xx_nmth_metadata_row($nid) {
  $d = [
         'n_a' => '物件編號',
         'n_a2' => '原件典藏編號',
         'n_b' => '識別號',
         'n_c' => 'whole',
         'n_d' => '物件性質',
         'n_e' => '中文名稱',
         'n_f' => '外文名稱',
         'n_g' => '實體件數',
         'n_h' => '年代-歷史分期',
         'n_i' => '年代-準確日期(起)',
         'n_j' => '年代-準確日期(迄)',
         'n_k' => '年代-推測年份(起)',
         'n_l' => '年代-推測年份(迄)',
         'n_m' => '年代-年代描述',
         'n_n' => '類別一-第二層分類',
         'n_o' => '類別一-第三層分類',
         'n_p' => '類別一-第四層分類',
         'n_q' => '類別二-第二層分類',
         'n_r' => '類別二-第三層分類',
         'n_s' => '類別二-第四層分類',
         'n_t' => '主要材質',
         'n_u' => '尺寸重量-長(X軸):cm',
         'n_v' => '尺寸重量-寬(Y軸):cm',
         'n_w' => '尺寸重量-高(Z軸):cm',
         'n_x' => '尺寸重量-直徑:cm',
         'n_y' => '尺寸重量-重量:kg',
         'n_z' => '創作者/製造者',
         'n_aa' => '產地源始/製造地',
         'n_ab' => '狀況描述-保存狀況等級',
         'n_ac' => '狀況描述-說明',
         'n_ad' => '價值-市場價值',
         'n_ae' => '價值-購買金額',
         'n_af' => '價值-保險金額',
         'n_ag' => '關鍵詞',
         'n_ah' => '文物描述',
         'n_ai' => '參考資料',
         'n_aj' => '備註',
         'n_ak' => '蒐藏宗旨',
         'n_al' => '蒐藏方向',
         'n_am' => '藏品分級',
         'n_an' => '分級說明',
         'n_ao' => '數位檔資訊',
       ];

  $node = node_load($nid, null, false);

  $remarks = [];

  $d['n_a'] = '';
  $d['n_a2'] = WGEntity::field_get_text($node, 'field_repository_id');
  $d['n_b'] = $node->nid;

  _xx_nmth_metadata_fpart($node, $d, $remarks);
  $d['n_d'] = '01';
  _xx_nmth_metadata_fname($node, $d, $remarks);
  $d['n_h'] = '0720';

  $d['n_i'] = WGEntity::field_get_text($node, 'field_created_time');
  /*
  ? null ? format ?
  "產製日期
  yyyymmdd"
  */
  $d['n_j'] = '';
  $d['n_k'] = '';
  $d['n_l'] = '';
  $d['n_m'] = '';

  _xx_nmth_metadata_fcate($node, $d, $remarks);
  _xx_nmth_metadata_fmat($node, $d, $remarks);
  _xx_nmth_metadata_fmea($node, $d, $remarks);

  $d['n_y'] = '';
  $d['n_z'] = WGEntity::field_get_term_name($node, 'field_creator');
  $d['n_aa'] = WGEntity::field_get_text($node, 'field_created_place');

  _xx_nmth_metadata_fcond($node, $d, $remarks);


  $d['n_ad'] = '';
  $d['n_ae'] = '';
  $d['n_af'] = '';
  _xx_nmth_metadata_fkeyword($node, $d, $remarks);

  _xx_nmth_metadata_fdesc($node, $d, $remarks);

  $d['n_ai'] = '';
  _xx_nmth_metadata_remark($node, $d, $remarks);
  $d['n_ak'] = '04';
  $d['n_al'] = '02、04';
  $d['n_am'] = '05';
  $d['n_an'] = '本批物件為2014年3月18日後所採集的學運相關物件，具有當代社會重大議題之性質，惟數量龐大且屬近代物件，建列本館之三級藏品。';
  $d['n_ao'] = '';
  $files = _coll_get_digifiles($nid, 'archive', 'tif');
  if($files) {
  foreach($files as &$file){
    preg_match('%([0-9_]+)\.(tif|jpg)%', $file, $m);
    //$file = str_replace(['public://digicoll/public/'], [''], $file);
    $file = $m[1].'.'.$m[2];
  }
  $d['n_ao'] = implode(';', $files);
    }

  return $d;

}


function _xx_nmth_metadata_fpart($node, &$d, &$remarks) {
  $nid = $node->nid;
  $whole_id = db_query('select field_relcol_partof_value as id from field_data_field_relcol_partof where entity_id = :nid', [':nid'=>$nid])->fetchField();
  if(!$whole_id) $whole_id = $node->nid;

  $d['n_c'] = $whole_id;
  //部件
  $part_ids = db_query('select entity_id from field_data_field_relcol_partof where field_relcol_partof_value = :nid', [':nid'=>$nid])->fetchCol();
  if($node->nid != $whole_id) {
    //部件自已的話:0
    $d['n_g'] = 0;
  } else {
    if(!$part_ids) {
      //單件:1
      $d['n_g'] = 1;
    } else {
      $d['n_g'] = count($part_ids);
    }
  }
}

function _xx_nmth_metadata_fname($node, &$d, &$remarks) {
  $format_cate = WGEntity::field_get_term_name($node, 'field_format_category');
  if(!$format_cate) $format_cate = '無主題';
    $content = WGEntity::field_get_textlong($node, 'field_content');
  if(!$content){
  $content = WGEntity::field_get_textlong($node, 'field_description');
  }
  if($content) {
    $content = str_replace(["\M", "\r\n", "\n", '-', '；', ':',';', ',', '.', '，','：', '。', '、', '？', '！', ' ', '/', '「', '」', '(', ')', '（', '）', '〈', '〉'], '', $content);
    $content = mb_substr($content, 0, 5);
    $content = '「'.$content.'... 」';
  }
//  [ 318公民運動 +「內容前五個字(描述前5個字*去標點*)+... 」+形式分類(無主題)  ]
  $name = '318公民運動'.$content.$format_cate;
  $d['n_e'] = $name;

  $d['n_f'] = ''; //外文名稱
}

function _xx_nmth_metadata_fkeyword($node, &$d, &$remarks) {
  $events = WGEntity::field_get_term_name($node, 'field_event');
  $tags = WGEntity::field_get_term_name($node, 'field_tagtag');
  print_r($tags);
  if($events && $tags) {
    $keywords = $events.'、'.$tags;
  }
  elseif($events && !$tags) {
    $keywords = $events;
  }
  elseif(!$events && $tags) {
    $keywords = $tags;
  }
  else {
    $keywords = '';
  }
  $d['n_ag'] = $keywords;
}


function _xx_nmth_metadata_fmea($node, &$d, &$remarks) {
  $ss = WGEntity::field_get_text($node, 'field_measurement');
  $x = '';
  $y = '';
  $z = '';
  $r = '';
  if($ss) {
    $ss = str_replace(['；'], ';', $ss);
    $ss = str_replace('325mmx17mm;直徑17mm', '325x17mm;直徑17mm', $ss);
    $ss = str_replace('99x99;直徑99mm', '99x99mm;直徑99mm', $ss);


    if(preg_match('%^([0-9]+)x([0-9]+)x([0-9]+)mm$%i', $ss, $m)) {
      $x = $m[1]/100;
      $y = $m[2]/100;
      $z = $m[3]/100;
    }
    elseif(preg_match('%^([0-9]+)x *([0-9]+)mm(;總計[0-9]+筆)?$%i', $ss, $m)) {
      $x = $m[1]/100;
      $y = $m[2]/100;
      //$z = $m[3];
    }
    elseif(preg_match('%^直徑([0-9]+)mm$%i', $ss, $m)) {
      $r = $m[1]/100;
    }
    elseif(preg_match('%^([0-9]+)x([0-9]+)x([0-9]+)mm;直徑([0-9]+)mm$%i', $ss, $m)) {
      $x = $m[1]/100;
      $y = $m[2]/100;
      $z = $m[3]/100;
      $r = $m[4]/100;
    }
    elseif(preg_match('%^([0-9]+)x *([0-9]+)mm;直徑([0-9]+)mm$%i', $ss, $m)) {
      $x = $m[1]/100;
      $y = $m[2]/100;
      $r = $m[3]/100;
    }
    else {
      echo ' ['.$node->nid.'] '.$ss.' measurement error'."\n";
      //die();
    }
  }

  $d['n_u'] = $x;
  $d['n_v'] = $y;
  $d['n_w'] = $z;
  $d['n_x'] = $r;
}


function _xx_nmth_metadata_fmat($node, &$d, &$remarks) {
  $ss = WGEntity::field_get_term_name($node, 'field_material');
  $d['n_t'] = '';
  $ccmap = [
             '紙' => '09',
             '厚紙板' => '09',
             '塑膠瓦稜板' => '09',
             '粉彩紙' => '09',
             '透明膠帶' => '08',
             '瓦楞紙' => '09',
             '書面紙' => '09',
             '珍珠板' => '09',
             '丹迪紙' => '09',
             '宣紙' => '09',
             '貼紙' => '09',
             '棉' => '06',
             '塑膠' => '08',
             '雲彩紙' => '09',
             '發泡塑膠' => '08',
             '竹' => '01',
             '布' => '06',
             '木材' => '01',
             '保麗龍' => '08',
             '合成紙' => '09',
             '金屬' => '05',
             '尼龍繩' => '08',
             '毛' => '07',
             '橡皮筋' => '08',
             '牛皮紙' => '09',
             '瓦稜板' => '09',
             '帆布' => '08',
             '麻' => '06',
             '紙布' => '06',
             '玻璃' => '02',
             '泡棉' => '08',
             '橡膠' => '08',
             '鋁箔' => '05',
             '紙紙' => '09',
             '絲' => '06',
             '紗' => '06',
             '矽膠' => '08',
           ];

  if(array_key_exists($ss, $ccmap )) {
    $d['n_t'] = $ccmap[$ss];
  }

}

function _xx_nmth_metadata_fcond($node, &$d, &$remarks) {
  $ss = WGEntity::field_get_term_name($node, 'field_condition');
  $d['n_ab'] = '';
  $ccmap = [
             '良好' => '01',
             '破損' => '03',
             '汙損' => '02',
             '黏合' => '03',
             '正常' => '01',
             '異常' => '03',
             '缺件' => '03',
             '鬆脫' => '04',
             '風乾' => '03',
           ];

  if(array_key_exists($ss, $ccmap )) {
    $d['n_ab'] = $ccmap[$ss];
  }

  $d['n_ac'] = WGEntity::field_get_textlong($node, 'field_condition_note');

}

function _xx_nmth_metadata_fcate($node, &$d, &$remarks) {
  $field_content_category = WGEntity::field_get_term_name($node, 'field_content_category');
  $ccmap = [
             '鼓勵語'=>[['01', '0108', '99'], ['02', '0209', '99']],
             '繪畫'=>[['02', '0209', '99'], ['01', '0109', '03']],
             '簽名'=>[['02', '0209', '99'], ['01', '0108', '99']],
             '標語'=>[['02', '0209', '99']],
             '短語'=>[['02', '0209', '99']],
             '照片'=>[['02', '0209', '99'], ['01', '0106', '99']],
             '通訊資料'=>[['02', '0209', '99']],
             '詩詞'=>[['02', '0209', '99']],
             '經文'=>[['02', '0209', '99']],
             '題記'=>[['02', '0209', '99']],
             '塗鴉'=>[['02', '0209', '99']],
             '歌詞'=>[['02', '0209', '99']],
             '外國語'=>[['02', '0209', '99']],
             '標示'=>[['02', '0209', '99']],
             '文章'=>[['02', '0209', '99']],
             '規範'=>[['02', '0209', '99']],
             '承諾書'=>[['02', '0209', '99']],
             '流程'=>[['02', '0209', '99']],
             '計畫'=>[['02', '0209', '99']],
             '心智圖'=>[['02', '0209', '99']],
             '裝飾藝術'=>[['02', '0209', '99'], ['01', '0109', '03']],
             '太陽餅'=>[['02', '0209', '99']],
             '漫畫'=>[['02', '0209', '99']],
             '識別證'=>[['02', '0209', '99']],
             '表格'=>[['02', '0209', '99']],
             '實錄'=>[['02', '0209', '99']],
             '地圖'=>[['02', '0209', '99'], ['01', '0110', '99']],
             '圖解'=>[['02', '0209', '99']],
             '玩具熊'=>[['02', '0209', '99']],
             '封面'=>[['02', '0209', '99']],
             '封底'=>[['02', '0209', '99']],
             '問答'=>[['02', '0209', '99']],
             '斗笠'=>[['02', '0209', '99']],
             '文書'=>[['02', '0209', '99']],
             '公文'=>[['02', '0209', '99']],
             '衣物'=>[['02', '0209', '99']],
             '國旗'=>[['02', '0209', '99']],
           ];

  $d['n_n'] = '';
  $d['n_o'] = '';
  $d['n_p'] = '';
  $d['n_q'] = '';
  $d['n_r'] = '';
  $d['n_s'] = '';

  if($field_content_category && array_key_exists($field_content_category, $ccmap )) {
    $vvs = $ccmap[$field_content_category];
    if($vvs) {
      $vv = $vvs[0];

      $d['n_n'] = $vv[1];
      $d['n_o'] = $vv[2];
      $d['n_p'] = '';
      if(isset($vvs[1])) {
        $vv = $vvs[1];
        $d['n_q'] = $vv[1];
        $d['n_r'] = $vv[2];
        $d['n_s'] = '';
      }

    }
  }
}

function _xx_nmth_metadata_fdesc($node, &$d, &$remarks) {
  $ss = '';
  $ss .= '描述:'.WGEntity::field_get_textlong($node, 'field_description')."\n";
  $ss .= '內容:'.WGEntity::field_get_textlong($node, 'field_content');
  $d['n_ah'] = $ss;
}


function _xx_nmth_metadata_remark($node, &$d, &$remarks) {
  //$ss = WGEntity::field_get_text($node, 'field_repository_id');
  //$remarks[] = '原件典藏編號:'.$ss;
  $ss = WGEntity::field_get_term_name($node, 'field_provider');
  $remarks[] = '提供者:'.$ss;
  $ss = WGEntity::field_get_term_name($node, 'field_collector');
  $remarks[] = '取得者:'.$ss;
  $ss = WGEntity::field_get_text($node, 'field_collected_time');
  $remarks[] = '取得日期:'.$ss;
  $ss = WGEntity::field_get_text($node, 'field_collected_place');
  $remarks[] = '取得地點:'.$ss;
  $ss = WGEntity::field_get_text($node, 'field_collected_method');
  $remarks[] = '取得方式:'.$ss;
  $ss = WGEntity::field_get_term_name($node, 'field_mainformat');

  $ids = db_query('select entity_id from field_data_field_relcol_relatedto where field_relcol_relatedto_value = :nid', [':nid'=>$nid])->fetchCol();
  $remarks[] = '關係藏品-關聯:'.implode('、', $ids);
  $ss = WGEntity::field_get_term_name($node, 'field_mainformat');
  $ss = WGEntity::field_get_term_name($node, 'field_mainformat');
  $remarks[] = '主要形式:'.$ss;
  $ss = WGEntity::field_get_term_name($node, 'field_format_category');
  $remarks[] = '形式分類:'.$ss;
  $ss = WGEntity::field_get_term_name($node, 'field_note');
  $remarks[] = '備註:'.$ss;

  $d['n_aj'] = implode('\r\n', $remarks);
}
