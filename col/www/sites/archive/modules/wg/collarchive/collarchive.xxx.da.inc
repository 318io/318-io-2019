<?php
use Drupal\wg\WGEntity;

function _export_to_dacenter() {
  $i = 0;
  $nids = _collarchive_get_topublic_nids('_export_to_dacenter_nids_mm');
  foreach($nids as $nid) {
    $i++;
    _export_to_dacenter_op_mm($nid);
    dnotice($i.' '.$nid);
  }


  $nids = _collarchive_get_topublic_nids('_export_to_dacenter_nids_obj');
  foreach($nids as $nid) {
    $i++;
    _export_to_dacenter_op_obj($nid);
    dnotice($i.' '.$nid);
  }

}

function _export_to_dacenter_nids_obj(&$query) {
  $query->join('field_data_field_mainformat', 'fmf', 'n.nid = fmf.entity_id');
  $query->condition('fmf.field_mainformat_tid', 89, '<>');
  //$query->range(0,10);
}

function _export_to_dacenter_op_obj($nid) {
  $node = node_load($nid, null, true);
  $license = WGEntity::field_get_term_name($node, 'field_license');
  switch($license) {
    case 'CC BY 4.0':
    case 'CC0 1.0':
    case 'CC BY 3.0 TW':
    case 'CC BY-NC-SA 3.0 TW':
    case 'CC BY-NC-ND 3.0 TW':
      _export_to_dacenter_op_obj_cc($node);
      break;
    default:
      if(!empty($license)) {
        echo '*x**'.$nid.'  '.$license."***\n";
        die();
      }
      _export_to_dacenter_op_obj_nf($node);
  }
}

function _export_to_dacenter_op_obj_nf($node) {
  $nid = $node->nid;
  MXML::$content_encode = true;
  $s = '';
  $s .= '<?xml version="1.0" encoding="UTF-8"?>';
  $s .= '<DACatalog>';
  $s .='<AdminDesc>';
  $s .='<Project Creator="中央研究院歷史語言研究所、中央研究院資訊科學研究所" GenDate="'.date('Y-m-d H:i:s').'">318公民運動文物紀錄典藏庫</Project>';
  $s .='<Catalog>';
  $ar = WGEntity::field_get_all_term_name($node, 'field_mainformat');
  $mainformat = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:主要形式:'.$item);
    }
  }
  $ar = WGEntity::field_get_all_term_name($node, 'field_format_category');
  $format_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:形式分類:'.$item);
    }
  }

  $ar = WGEntity::field_get_all_term_name($node, 'field_content_category');
  $content_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:內容分類:'.$item);
    }
  }

  $license = WGEntity::field_get_term_name($node, 'field_license');
  $creator = WGEntity::field_get_text($node, 'field_license_note');

  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:歷史語言研究所:318公民運動巨量資料的收集、整理與研究');
  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:資訊科學研究所:318公民運動巨量資料的收集、整理與研究');

  $s .='</Catalog>';
  $s .= '<DigiArchiveID>'.$nid.'</DigiArchiveID>';
  $s .= '<Hyperlink>http://public.318.io/'.$nid.'</Hyperlink>';
  /*
  $files = _coll_get_digifiles($nid, 'public', 'jpg');
  if($files) {
    $pathid = _coll_get_pathpart($nid);
    foreach($files as $file) {
      $dffn = str_replace('public://digicoll/public/'.$pathid.'/', '', $file);
      $s .= '<ICON license="'.$license.'" creator="'.$creator.'">'.$dffn.'</ICON>';
    }
  }
  */

  $s .='</AdminDesc>';

  $s .='<MetaDesc license="CC0 1.0">';
  $s .= MXML::tag('Title',  $mainformat.'（'.$nid.'）', ['field' => "主要形式"]);
  $s .='<Subject>社會運動</Subject>';
  $s .= MXML::tag('Subject', WGEntity::field_get_term_name($node, 'field_event'), ['field' => "所屬事件"]);
  $s .= MXML::tag('Description', WGEntity::field_get_textlong($node, 'field_description'));
  $s .= MXML::tag('Description', $content_category, ['field' => "內容分類"]);
  $s .= MXML::tag('Publisher', '中央研究院歷史語言研究所、中央研究院資訊科學研究所', ['field' => "數位化執行單位"]);
  //$s .= MXML::tag('Date', WGEntity::field_get_text($node, 'field_created_time'), ['field' => "產製日期"]);

  $s .= MXML::tag('Type', $format_category, ['field' => "形式分類"]);
  $type = _xxx_get_collection_type($nid);
  $s .= MXML::tag('Type', $type, ['field' => "型式"]);
  $ar = WGEntity::field_get_all_term_name($node, 'field_material');
  $field_material = implode('、', $ar);
  $s .= MXML::tag('Format', $field_material, ['field' => "材質"]);

  $s .= MXML::tag('Format', WGEntity::field_get_text($node, 'field_measurement'), ['field' => "測量"]);
  $s .= MXML::tag('Identifier', $nid, ['field' => "識別號"]);

  $ar = WGEntity::field_get_all_term_name($node, 'field_rightgranted');
  $field_rightgranted = implode('、', $ar);

  //$s .= MXML::tag('Coverage', WGEntity::field_get_text($node, 'field_created_place'), ['field' => "產製地點"]);

  $s .= MXML::tag('Rights', $field_rightgranted, ['field' => "權利狀態"]);
  /*
  $license = WGEntity::field_get_term_name($node, 'field_license');
  $s .= MXML::tag('Rights', $license, ['field' => "數位物件釋出條款"]);
  $s .= MXML::tag('Rights', WGEntity::field_get_text($node, 'field_license_note'), ['field' => "姓名標示值"]);
  */


  $s .='</MetaDesc>';
  $s .='</DACatalog>';
  $fn = '/tmp/318daxml/'.$nid.'.xml';
  MXML::write($s, $fn, false);
}


function _export_to_dacenter_op_obj_cc($node) {
  $nid = $node->nid;
  MXML::$content_encode = true;

  $s = '';
  $s .= '<?xml version="1.0" encoding="UTF-8"?>';
  $s .= '<DACatalog>';
  $s .='<AdminDesc>';
  $s .='<Project Creator="中央研究院歷史語言研究所、中央研究院資訊科學研究所" GenDate="'.date('Y-m-d H:i:s').'">318公民運動文物紀錄典藏庫</Project>';
  $s .='<Catalog>';
  $ar = WGEntity::field_get_all_term_name($node, 'field_mainformat');
  $mainformat = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:主要形式:'.$item);
    }
  }
  $ar = WGEntity::field_get_all_term_name($node, 'field_format_category');
  $format_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:形式分類:'.$item);
    }
  }

  $ar = WGEntity::field_get_all_term_name($node, 'field_content_category');
  $content_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:內容分類:'.$item);
    }
  }

  $license = WGEntity::field_get_term_name($node, 'field_license');
  $creator = WGEntity::field_get_text($node, 'field_license_note');

  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:歷史語言研究所:318公民運動巨量資料的收集、整理與研究');
  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:資訊科學研究所:318公民運動巨量資料的收集、整理與研究');
  $s .='</Catalog>';
  $s .= '<DigiArchiveID>'.$nid.'</DigiArchiveID>';
  $s .= '<Hyperlink>http://public.318.io/'.$nid.'</Hyperlink>';
  $files = _coll_get_digifiles($nid, 'public', 'jpg');
  if($files) {
    $pathid = _coll_get_pathpart($nid);
    foreach($files as $file) {
      $dffn = str_replace('public://digicoll/public/'.$pathid.'/', '', $file);
      $source = drupal_realpath($file);
      $target = '/nas/158/toda/files/'.$dffn;
      if(!file_exists($target)){
        copy($source, $target);
      }
      $s .= '<ICON license="'.$license.'" creator="'.$creator.'">'.$dffn.'</ICON>';
    }
  }

  $s .='</AdminDesc>';

  $s .='<MetaDesc license="CC0 1.0">';


  $s .= MXML::tag('Title',  $mainformat.'（'.$nid.'）', ['field' => "主要形式"]);
  $s .='<Subject>社會運動</Subject>';
  $s .= MXML::tag('Subject', WGEntity::field_get_term_name($node, 'field_event'), ['field' => "所屬事件"]);
  $s .= MXML::tag('Description', WGEntity::field_get_textlong($node, 'field_description'));
  $s .= MXML::tag('Description', $content_category, ['field' => "內容分類"]);
  $s .= MXML::tag('Publisher', '中央研究院歷史語言研究所、中央研究院資訊科學研究所', ['field' => "數位化執行單位"]);
  //$s .= MXML::tag('Date', WGEntity::field_get_text($node, 'field_created_time'), ['field' => "產製日期"]);

  $s .= MXML::tag('Type', $format_category, ['field' => "形式分類"]);
  $type = _xxx_get_collection_type($nid);
  $s .= MXML::tag('Type', $type, ['field' => "型式"]);
  $ar = WGEntity::field_get_all_term_name($node, 'field_material');
  $field_material = implode('、', $ar);
  $s .= MXML::tag('Format', $field_material, ['field' => "材質"]);

  $s .= MXML::tag('Format', WGEntity::field_get_text($node, 'field_measurement'), ['field' => "測量"]);
  $s .= MXML::tag('Identifier', $nid, ['field' => "識別號"]);

  $ar = WGEntity::field_get_all_term_name($node, 'field_rightgranted');
  $field_rightgranted = implode('、', $ar);

  //$s .= MXML::tag('Coverage', WGEntity::field_get_text($node, 'field_created_place'), ['field' => "產製地點"]);

  $s .= MXML::tag('Rights', $field_rightgranted, ['field' => "權利狀態"]);
  $license = WGEntity::field_get_term_name($node, 'field_license');
  $s .= MXML::tag('Rights', $license, ['field' => "數位物件釋出條款"]);
  $s .= MXML::tag('Rights', WGEntity::field_get_text($node, 'field_license_note'), ['field' => "姓名標示值"]);


  $s .='</MetaDesc>';
  $s .='</DACatalog>';
  $fn = '/tmp/318daxml/'.$nid.'.xml';
  MXML::write($s, $fn, false);
}




function _export_to_dacenter_nids_mm(&$query) {
  $query->join('field_data_field_mainformat', 'fmf', 'n.nid = fmf.entity_id');
  $query->condition('fmf.field_mainformat_tid', 89, '=');
  //$query->range(0,10);
}

function _export_to_dacenter_op_mm($nid) {
  $node = node_load($nid, null, true);
  $license = WGEntity::field_get_term_name($node, 'field_license');
  switch($license) {
    case 'CC BY 4.0':
    case 'CC0 1.0':
    case 'CC BY-NC-SA 3.0 TW':
    case 'CC BY-NC 4.0':
      _export_to_dacenter_op_mm_cc($node);
      break;
    default:
          if(!empty($license)) {

      echo '*x**'.$nid.'  '.$license."***\n";
    }
      _export_to_dacenter_op_mm_nf($node);
  }
}

function _export_to_dacenter_op_mm_nf($node) {
  $nid = $node->nid;
  MXML::$content_encode = true;

  $s = '';
  $s .= '<?xml version="1.0" encoding="UTF-8"?>';
  $s .= '<DACatalog>';
  $s .='<AdminDesc>';
  $s .='<Project Creator="中央研究院歷史語言研究所、中央研究院資訊科學研究所" GenDate="'.date('Y-m-d H:i:s').'">318公民運動文物紀錄典藏庫</Project>';
  $s .='<Catalog>';
  $ar = WGEntity::field_get_all_term_name($node, 'field_mainformat');
  $mainformat = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:主要形式:'.$item);
    }
  }
  $ar = WGEntity::field_get_all_term_name($node, 'field_format_category');
  $format_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:形式分類:'.$item);
    }
  }

  $ar = WGEntity::field_get_all_term_name($node, 'field_content_category');
  $content_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:內容分類:'.$item);
    }
  }

  $license = WGEntity::field_get_term_name($node, 'field_license');
  $creator = WGEntity::field_get_text($node, 'field_license_note');

  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:歷史語言研究所:318公民運動巨量資料的收集、整理與研究');
  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:資訊科學研究所:318公民運動巨量資料的收集、整理與研究');
  $s .='</Catalog>';
  $s .= '<DigiArchiveID>'.$nid.'</DigiArchiveID>';
  $s .= '<Hyperlink>http://public.318.io/'.$nid.'</Hyperlink>';
  /*
  $files = _coll_get_digifiles($nid, 'public', 'webm');
  if($files) {
    $pathid = _coll_get_pathpart($nid);
    foreach($files as $file) {
      $dffn = str_replace('public://digicoll/public/'.$pathid.'/', '', $file);
      //copy(realpath($file),'/nas/158/toda/files/'.$dffn);
      $s .= '<Multimedia license="'.$license.'" creator="'.$creator.'">'.$dffn.'</Multimedia>';
    }
  }
  */


  $s .='</AdminDesc>';

  $s .='<MetaDesc license="CC0 1.0">';

  $s .= MXML::tag('Title',  $mainformat.'（'.$nid.'）', ['field' => "主要形式"]);
  $s .='<Subject>社會運動</Subject>';
  $s .= MXML::tag('Subject', WGEntity::field_get_term_name($node, 'field_event'), ['field' => "所屬事件"]);
  $s .= MXML::tag('Description', WGEntity::field_get_textlong($node, 'field_description'));
  $s .= MXML::tag('Description', $content_category, ['field' => "內容分類"]);
  $s .= MXML::tag('Publisher', '中央研究院歷史語言研究所、中央研究院資訊科學研究所', ['field' => "數位化執行單位"]);
  $s .= MXML::tag('Date', WGEntity::field_get_text($node, 'field_created_time'), ['field' => "產製日期"]);


  $s .= MXML::tag('Type', $format_category, ['field' => "形式分類"]);
  $type = _xxx_get_collection_type($nid);
  $s .= MXML::tag('Type', $type, ['field' => "型式"]);
  /*$ar = WGEntity::field_get_all_term_name($node, 'field_material');
  $field_material = implode('、', $ar);
  $s .= MXML::tag('Format', $field_material, ['field' => "材質"]);
  */

  $s .= MXML::tag('Format', WGEntity::field_get_text($node, 'field_measurement'), ['field' => "測量"]);
  $s .= MXML::tag('Identifier', $nid, ['field' => "識別號"]);

  $ar = WGEntity::field_get_all_term_name($node, 'field_rightgranted');
  $field_rightgranted = implode('、', $ar);

  $s .= MXML::tag('Coverage', WGEntity::field_get_text($node, 'field_created_place'), ['field' => "產製地點"]);

  $s .= MXML::tag('Rights', $field_rightgranted, ['field' => "權利狀態"]);
  /*
  $license = WGEntity::field_get_term_name($node, 'field_license');
  $s .= MXML::tag('Rights', $license, ['field' => "數位物件釋出條款"]);
  $s .= MXML::tag('Rights', WGEntity::field_get_text($node, 'field_license_note'), ['field' => "姓名標示值"]);
  */


  $s .='</MetaDesc>';
  $s .='</DACatalog>';
  $fn = '/tmp/318daxml/'.$nid.'.xml';
  MXML::write($s, $fn, false);
}

function _export_to_dacenter_op_mm_cc($node) {
  $nid = $node->nid;
  MXML::$content_encode = true;

  $s = '';
  $s .= '<?xml version="1.0" encoding="UTF-8"?>';
  $s .= '<DACatalog>';
  $s .='<AdminDesc>';
  $s .='<Project Creator="中央研究院歷史語言研究所、中央研究院資訊科學研究所" GenDate="'.date('Y-m-d H:i:s').'">318公民運動文物紀錄典藏庫</Project>';
  $s .='<Catalog>';
  $ar = WGEntity::field_get_all_term_name($node, 'field_mainformat');
  $mainformat = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:主要形式:'.$item);
    }
  }
  $ar = WGEntity::field_get_all_term_name($node, 'field_format_category');
  $format_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:形式分類:'.$item);
    }
  }

  $ar = WGEntity::field_get_all_term_name($node, 'field_content_category');
  $content_category = implode('、', $ar);
  if($ar) {
    foreach($ar as $item) {
      $s .= MXML::tag('Record', '內容主題:檔案:318公民運動文物:內容分類:'.$item);
    }
  }

  $license = WGEntity::field_get_term_name($node, 'field_license');
  $creator = WGEntity::field_get_text($node, 'field_license_note');

  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:歷史語言研究所:318公民運動巨量資料的收集、整理與研究');
  $s .= MXML::tag('Record','典藏機構與計畫:中央研究院:資訊科學研究所:318公民運動巨量資料的收集、整理與研究');
  $s .='</Catalog>';
  $s .= '<DigiArchiveID>'.$nid.'</DigiArchiveID>';
  $s .= '<Hyperlink>http://public.318.io/'.$nid.'</Hyperlink>';
  $files = _coll_get_digifiles($nid, 'public', 'webm');
  if($files) {
    $pathid = _coll_get_pathpart($nid);
    foreach($files as $file) {
      $dffn = str_replace('public://digicoll/public/'.$pathid.'/', '', $file);
      $source = drupal_realpath($file);
      $target = '/nas/158/toda/files/'.$dffn;
      if(!file_exists($target)){
        copy($source, $target);
      }

      $s .= '<Multimedia license="'.$license.'" creator="'.$creator.'">'.$dffn.'</Multimedia>';
    }
  }
  $dffn = $nid.'.webm';


  $s .='</AdminDesc>';

  $s .='<MetaDesc license="CC0 1.0">';

  $s .= MXML::tag('Title',  $mainformat.'（'.$nid.'）', ['field' => "主要形式"]);
  $s .='<Subject>社會運動</Subject>';
  $s .= MXML::tag('Subject', WGEntity::field_get_term_name($node, 'field_event'), ['field' => "所屬事件"]);
  $s .= MXML::tag('Description', WGEntity::field_get_textlong($node, 'field_description'));
  $s .= MXML::tag('Description', $content_category, ['field' => "內容分類"]);
  $s .= MXML::tag('Publisher', '中央研究院歷史語言研究所、中央研究院資訊科學研究所', ['field' => "數位化執行單位"]);
  $s .= MXML::tag('Date', WGEntity::field_get_text($node, 'field_created_time'), ['field' => "產製日期"]);


  $s .= MXML::tag('Type', $format_category, ['field' => "形式分類"]);
  $type = _xxx_get_collection_type($nid);
  $s .= MXML::tag('Type', $type, ['field' => "型式"]);
  /*$ar = WGEntity::field_get_all_term_name($node, 'field_material');
  $field_material = implode('、', $ar);
  $s .= MXML::tag('Format', $field_material, ['field' => "材質"]);
  */

  $s .= MXML::tag('Format', WGEntity::field_get_text($node, 'field_measurement'), ['field' => "測量"]);
  $s .= MXML::tag('Identifier', $nid, ['field' => "識別號"]);

  $ar = WGEntity::field_get_all_term_name($node, 'field_rightgranted');
  $field_rightgranted = implode('、', $ar);

  $s .= MXML::tag('Coverage', WGEntity::field_get_text($node, 'field_created_place'), ['field' => "產製地點"]);

  $s .= MXML::tag('Rights', $field_rightgranted, ['field' => "權利狀態"]);
  $license = WGEntity::field_get_term_name($node, 'field_license');
  $s .= MXML::tag('Rights', $license, ['field' => "數位物件釋出條款"]);
  $s .= MXML::tag('Rights', WGEntity::field_get_text($node, 'field_license_note'), ['field' => "姓名標示值"]);


  $s .='</MetaDesc>';
  $s .='</DACatalog>';
  $fn = '/tmp/318daxml/'.$nid.'.xml';
  MXML::write($s, $fn, false);
}

function _xxx_get_collection_type($identifier) {
  $tid = &drupal_static(__FUNCTION__, NULL);
  if(!$tid) {
    $tid = db_query('select t.tid from taxonomy_term_data t left join taxonomy_vocabulary v on t.vid=v.vid where v.name=:voc and t.name=:term', [':voc'=>'mainform', ':term' => '數位媒體'])->fetchField();
    //watchdog('xxx', $tid);
  }
  $tid2 = db_query('SELECT field_mainformat_tid from field_data_field_mainformat where entity_id=:id', [':id'=>$identifier])->fetchField();
  if($tid2 == $tid) $r = '數位媒體';
  else $r ='實體物件';
  return $r;
}

function _collarchive_get_topublic_nids( $cb = '', $nodetype = 'collection') {
  $types = array($nodetype);
  $ispublis_tid = db_query("select tid from taxonomy_term_data as t, taxonomy_vocabulary as v WHERE t.vid=v.vid AND v.name = 'boolean' AND t.name = '是'")->fetchField();
  $query = db_select('node', 'n');
  $query->join('field_data_field_public', 'p', 'n.nid = p.entity_id');
  $query->condition('n.status', 1, '>=')->condition('n.type', $types, 'IN');
  $query->condition('p.field_public_tid', $ispublis_tid, '=');
  $query->fields('n', array('nid'));
  $query->orderBy('n.nid', 'ASC');
  if($cb) {
    $cb($query);
  }
  //$query->range(0, 100);
  $num_rows = $query->countQuery()->execute()->fetchField();
  $result = $query->execute();
  $nids = $result->fetchCol();
  return $nids;
}
