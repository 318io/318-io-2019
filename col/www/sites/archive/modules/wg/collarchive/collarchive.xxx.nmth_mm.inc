<?php
use Drupal\wg\WGEntity;

function _xx_nmth_metadata_mm() {
  $nids = db_query('SELECT * FROM node n left join field_data_field_mainformat m on m.entity_id=n.nid where type= :type and field_mainformat_tid = 89 order by nid', [':type'=> 'collection'])->fetchCol();
  if(!$nids) return;
  $csv = '/tmp/318nmthmeta_mm.csv';
  $fp = fopen($csv, 'w');

  $headers = [
               'n_a' => '物件編號',
               'n_a0' => '識別號',
               'n_b' => '資源主類別',
               'n_c' => '自定義次類型（屬性）',
               'n_d' => '物件標題',
               'n_e' => '外文名稱',
               'n_f' => '歷史分期(起)',
               'n_g' => '歷史分期(訖)',
               'n_h' => '年代（起）',
               'n_i' => '年代（訖）',
               'n_j' => '時間描述',
               'n_k' => '指涉地點-臺灣行政區域',
               'n_l' => '指涉地點-補充資訊',
               'n_m' => '指涉地點-其他',
               'n_n' => '物件描述',
               'n_o' => '關鍵詞',
               'n_p' => '拍攝者',
               'n_q' => '拍攝者單位名稱',
               'n_r' => '訪談者名稱',
               'n_s' => '訪談者單位名稱',
               'n_t' => '訪談者-計劃名稱',
               'n_u' => '調查者名稱',
               'n_v' => '調查者單位名稱',
               'n_w' => '調查者-計劃名稱',
               'n_x' => '受訪者名稱',
               'n_y' => '受訪者單位名稱',
               'n_z' => '紀錄者名稱',
               'n_aa' => '紀錄者單位名稱',
               'n_ab' => '整理者名稱',
               'n_ac' => '整理者單位名稱',
               'n_ad' => '影像形式',
               'n_ae' => '影像字幕語文',
               'n_af' => '影像旁白語文',
               'n_ag' => '影像撥放時間',
               'n_ah' => '導演',
               'n_ai' => '導演單位名稱',
               'n_aj' => '發行資訊-發行者／單位',
               'n_ak' => '發行資訊-註明',
               'n_al' => '語言／文字',
               'n_am' => '收藏取得來源',
               'n_an' => '收藏取得來源描述',
               'n_ao' => '提供者姓名',
               'n_ap' => '提供者所屬單位',
               'n_aq' => '來源典藏資訊-單位',
               'n_ar' => '來源典藏資訊-題名',
               'n_as' => '來源典藏資訊-編號',
               'n_at' => '研究調查計畫-執行單位',
               'n_au' => '研究調查計畫-計畫名稱',
               'n_av' => '專著論集資訊-專著／論集名',
               'n_aw' => '專著論集資訊-輯／卷／期別',
               'n_ax' => '會議／學位論文-舉辦單位',
               'n_ay' => '會議／學位論文-會議名稱',
               'n_az' => '會議／學位論文-學校系所',
               'n_ba' => '長度（X軸）',
               'n_bb' => '寬度（Y軸）',
               'n_bc' => '高度（Z軸）',
               'n_bd' => '直徑',
               'n_be' => '尺寸單位',
               'n_bf' => '重量',
               'n_bg' => '重量單位',
               'n_bh' => '使用對象為全館同仁',
               'n_bi' => '使用權限'
             ];
  DT::fputcsv($fp, $headers);
  foreach($nids as $nid) {
    $ar = _xx_nmth_metadata_mm_row($nid);
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

function _xx_nmth_metadata_mm_row($nid) {
  $d = [
         'n_a' => '',
         'n_a0' => 'f識別號',
         'n_b' => '電影紀錄片',
         'n_c' => 'f自定義次類型（屬性）',
         'n_d' => 'f物件標題',
         'n_e' => '',
         'n_f' => '0702',
         'n_g' => '',
         'n_h' => '',
         'n_i' => '',
         'n_j' => 'f時間描述',
         'n_k' => 'f指涉地點-臺灣行政區域',
         'n_l' => 'f指涉地點-補充資訊',
         'n_m' => 'f指涉地點-其他',
         'n_n' => 'f物件描述',
         'n_o' => 'f關鍵詞',
         'n_p' => 'f拍攝者',
         'n_q' => '',
         'n_r' => '',
         'n_s' => '',
         'n_t' => '',
         'n_u' => '',
         'n_v' => '',
         'n_w' => '',
         'n_x' => '',
         'n_y' => '',
         'n_z' => '',
         'n_aa' => '',
         'n_ab' => 'f整理者名稱',
         'n_ac' => '中央研究院資訊科學研究所',
         'n_ad' => '',
         'n_ae' => '',
         'n_af' => '',
         'n_ag' => 'f影像撥放時間',
         'n_ah' => '',
         'n_ai' => '',
         'n_aj' => '',
         'n_ak' => '',
         'n_al' => '',
         'n_am' => '5',
         'n_an' => 'f收藏取得來源描述',
         'n_ao' => 'f提供者姓名',
         'n_ap' => '',
         'n_aq' => 'f來源典藏資訊-單位',
         'n_ar' => 'f來源典藏資訊-題名',
         'n_as' => 'f來源典藏資訊-編號',
         'n_at' => '',
         'n_au' => '',
         'n_av' => '',
         'n_aw' => '',
         'n_ax' => '',
         'n_ay' => '',
         'n_az' => '',
         'n_ba' => '',
         'n_bb' => '',
         'n_bc' => '',
         'n_bd' => '',
         'n_be' => '',
         'n_bf' => '',
         'n_bg' => '',
         'n_bh' => 'f使用對象為全館同仁',
         'n_bi' => 'f使用權限'
       ];

  $node = node_load($nid, null, false);

  $remarks = [];
  $d['n_a0'] = $node->nid;
  $d['n_c'] = WGEntity::field_get_term_name($node, 'field_format_category');
  _xx_nmth_metadata_mm_fname($node, $d, $remarks);

  $created_time = WGEntity::field_get_text($node, 'field_created_time');
  if(preg_match('%([0-9]{2})/([0-9]{2})/([0-9]{4})%', $created_time, $m)) {
    $created_time = $m[3].$m[1].$m[2];
  }

  $d['n_j'] = $created_time;

  _xx_nmth_metadata_place($node, $d, $remarks);

  $d['n_n'] = WGEntity::field_get_textlong($node, 'field_description');
  _xx_nmth_metadata_mm_fkeyword($node, $d, $remarks);

  $creator = WGEntity::field_get_term_name($node, 'field_creator');
  $d['n_p'] = $creator;
  $d['n_ab'] = WGEntity::field_get_term_name($node, 'field_recorder');

  $d['n_ag'] = WGEntity::field_get_text($node, 'field_measurement');

  $d['n_an'] = WGEntity::field_get_text($node, 'field_created_time').WGEntity::field_get_text($node, 'field_created_place');
  $d['n_ao'] = WGEntity::field_get_term_name($node, 'field_provider');
  $d['n_aq'] = WGEntity::field_get_term_name($node, 'field_collector');
  $d['n_ar'] = WGEntity::field_get_term_name($node, 'field_collected_method').WGEntity::field_get_text($node, 'field_collected_time').WGEntity::field_get_text($node, 'field_collected_place');
  $d['n_as'] = '原件典藏編號：'.WGEntity::field_get_text($node, 'field_repository_id').'、識別號：'.$node->nid;
  $rightgranted = WGEntity::field_get_term_name($node, 'field_rightgranted');
  $d['n_bh'] = ($rightgranted == '宣告為公眾領域 ')?'Y':'N';
  $d['n_bi'] = ($rightgranted == '宣告為公眾領域 ')?'2':'1';

  return $d;

}

function _xx_nmth_metadata_place($node, &$d, &$remarks) {
  $d['n_k'] = '指涉地點-臺灣行政區域';
  $d['n_l'] = '指涉地點-補充資訊';
  $d['n_m'] = '';
  $ss = WGEntity::field_get_text($node, 'field_created_place');
  $d['n_t'] = '';
  $ccmap0 = [
              '凱恩斯,澳洲' => '指涉地點-其他',
              '坎培拉,澳洲' => '指涉地點-其他',
              '日本' => '指涉地點-其他',
              '法國' => '指涉地點-其他',
              '法國,巴黎' => '指涉地點-其他',
              '法國里昂' => '指涉地點-其他',
              '法國，巴黎人權廣場' => '指涉地點-其他',
              '美國' => '指涉地點-其他',
              '美國紐約' => '指涉地點-其他',
            ];

  $ccmap = [
             '若本欄空白' => '1',
             '中安街（八二三紀念公園側廣場）' => '3',
             '中山北路' => '3',
             '中山南路' => '3',
             '中山南路 立法院 正門場' => '3',
             '中山南路口' => '3',
             '中山南路（監察院外路口）' => '3',
             '中山南路（立法院區內）' => '3',
             '中山南路（立法院議會內）' => '3',
             '中正一分局' => '3',
             '仁愛路' => '3',
             '凱達格蘭大道' => '3',
             '台北賓館' => '3',
             '台南' => '15',
             '忠孝東路一段' => '3',
             '忠孝東路一段中山南路交叉路口（立法院周遭）' => '3',
             '忠孝東路一段（行政院院區內）' => '3',
             '忠孝西路' => '3',
             '忠孝西路天橋' => '3',
             '景福門' => '3',
             '濟南路' => '3',
             '濟南路一段' => '3',
             '濟南路一段13巷巷口' => '3',
             '濟南路一段13巷巷弄間' => '3',
             '濟南路一段中山南路交叉路口' => '3',
             '濟南路林森南路口' => '3',
             '立法院前' => '3',
             '立法院外帳篷' => '3',
             '立法院議場內一樓' => '3',
             '立法院議場內二樓' => '3',
             '立法院週邊' => '3',
             '立法院週邊物資區' => '3',
             '立法院週邊，也有出現在青島東路上過（見DIGIV000914）' => '3',
             '立法院週邊，某個舞台後方' => '3',
             '立法院門口(中山南路)' => '3',
             '立法院院內' => '3',
             '立法院院區周圍' => '3',
             '行政院內' => '3',
             '行政院週邊' => '3',
             '新北市板橋遠東路一段' => '4',
             '新北市板橋遠東路一段為起點周遭不定點活動' => '4',
             '鎮江街' => '3',
             '青島中山物資站外' => '3',
             '青島東路' => '3',
             '青島東路上' => '3',
             '青島東路鎮江街口' => '3',
             '青島東路（中正一分局）' => '3',
             '青島東路（立法院院區內）' => '3',
           ];

  $d['n_k'] = '1';
  $d['n_l'] = $ss;
  if(!$ss) {
    $d['n_k'] = '1';
  } else {
    if(array_key_exists($ss, $ccmap )) {
      $d['n_k'] = $ccmap[$ss];
    }
    if(array_key_exists($ss, $ccmap0 )) {
      $d['n_m'] = $ccmap0[$ss];
    }
  }

}

function _xx_nmth_metadata_mm_fname($node, &$d, &$remarks) {
  /*
  // 318公民運動 + 姓名標示值(若無授權則匿名) +內容分類(無主題) +描述前五個字(去標點) ]
  $creator = WGEntity::field_get_text($node, 'field_license_note');
  if(!$creator) $creator = '匿名';
  $format_cate = WGEntity::field_get_term_name($node, 'field_format_category');
  if(!$format_cate) $format_cate = '無主題';
  $content = WGEntity::field_get_textlong($node, 'field_description');
  if($content) {
    $content = str_replace(["\M", "\r\n", "\n", '-', '；', ':',';', ',', '.', '，','：', '。', '、', '？', '！', ' ', '/', '「', '」', '(', ')', '（', '）', '〈', '〉'], '', $content);
    $content = mb_substr($content, 0, 5);
  }

  $name = '318公民運動 '.$creator.' '.$format_cate.' '.$content;
  */
  $format_cate = WGEntity::field_get_term_name($node, 'field_format_category');
  if(!$format_cate) $format_cate = '無主題';
  $content = WGEntity::field_get_textlong($node, 'field_description');
  if(!$content){
    $content = WGEntity::field_get_textlong($node, 'field_content');
  }
  if($content) {
    $content = str_replace(["\M", "\r\n", "\n", '-', '；', ':',';', ',', '.', '，','：', '。', '、', '？', '！', ' ', '/', '「', '」', '(', ')', '（', '）', '〈', '〉'], '', $content);
    $content = mb_substr($content, 0, 5);
    $content = '「'.$content.'... 」';
  }
//  [ 318公民運動 +「內容前五個字(描述前5個字*去標點*)+... 」+形式分類(無主題)  ]
  $name = '318公民運動'.$content.$format_cate;

  $d['n_d'] = $name;

  $d['n_e'] = ''; //外文名稱
}

function _xx_nmth_metadata_mm_fkeyword($node, &$d, &$remarks) {
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
  $d['n_o'] = $keywords;
}
