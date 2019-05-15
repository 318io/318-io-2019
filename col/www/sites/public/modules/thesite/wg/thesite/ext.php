<?php

function myddl($data, $logfilename = NULL, $overwrite = false) {
    if($logfilename !== NULL && is_string($logfilename)) {
      $log = "/tmp/{$logfilename}";
    } else {
      $log = "/tmp/myddl.log";
    }
  
    $current_date = date('Y-m-d h:i:s', time());
    $data         = print_r($data, true);
  
    $to_print =<<<DATA
  ------------------- {$current_date} -------------------
  {$data}\n
DATA;
    if($overwrite) file_put_contents($log, print_r($to_print, true));
    else           file_put_contents($log, print_r($to_print, true), FILE_APPEND);
}

/*
[
     "field_identifier" => [
       "is-public" => true,
       "name" => "identifier",
       "label" => "識別號",
       "type" => "text",
     ],
     "field_mainformat" => [
       "is-public" => true,
       "name" => "mainformat",
       "label" => "主要形式",
       "type" => "taxon",
       "vocabulary" => "mainform",
       "widget" => "category",
     ],
     "field_format_category" => [
       "is-public" => true,
       "name" => "format_category",
       "label" => "形式分類",
       "type" => "taxon",
       "vocabulary" => "formcate",
       "widget" => "category",
       "cardinality" => -1,
     ],
     ..............
]
*/
function set_collection_field_infos() {
  $vars = _collpub_ef_variables();  
  $ret = [];
  foreach($vars['node']['0']['field'] as $f) {
    if($f['is-public'] == 1) {
      $ret['field_'.$f['name']] = $f;
    }
  }
  variable_set('collection_fields_info', $ret);
}

// 輸出中英對照 json
function export_json($identifier) {
    global $base_url;   
    $nid = $identifier;
    $node = node_load($nid);
    $opts = &drupal_static(__FUNCTION__);
    if (!isset($opts)) {
      $vars = _collpub_ef_variables();  // 1.txt
      $vars = DTEF::entity_create_prepare($vars['node']);  // 2.txt

      $entities = $vars['entity'];
      $opts['nodetype'] = key($entities);
      $opts['entity'] = current($entities);
  
      $opts['nodetype'] = key($entities);
      $entity = current($entities);
      foreach ($entity['field'] as $key => $v) {
        $opts['emap'][$key] = $v['vardef']['name']; // english name
        $opts['cmap'][$key] = $v['vardef']['label']; // chinese name 
      }
    }
    extract($opts); // 3.txt
  
    $evalues = array();
    $cvalues = array();

    $lang = 'und';
    foreach($emap as $f => $n) { // $f , field name; $n, english name of field
      if(!property_exists($node, $f)) continue;
      $d = $node->$f;
      $ev = array();
      $cv = array();
  
      if($d && array_key_exists($lang, $d)) {
        $v0 = $d['und'];
        $vardef = $entity['field'][$f]['vardef'];
        if($v0) {
          if($vardef['name'] == 'mediainfo') {
            foreach($v0 as $v00) {
              if($v00['value']) {
                $x = str_replace('＂', '"', $v00['value']);
                $ev = json_decode($x);
                $cv = json_decode($x);
              }
            }
          } else {
            switch($vardef['type']) {
              case 'text':
              case 'longtext':
                foreach($v0 as $v00) {
                  if($v00['value'])
                    //$v[] = $v00['value'];
                    $ev = $v00['value'];
                    $cv = $v00['value'];
                }
                break;
              case 'taxon':
                foreach($v0 as $v00) {
                  $tid = $v00['tid'];
                  if($tid) {
                    $term = taxonomy_term_load($tid);
                    $ev[] = $term->field_name_en[$lang][0]['value'];
                    $cv[] = $term->name;
                  }
                }
                break;
              default:
            }
          }
        }
      }
      if($ev)  {
        $evalues[$n] = $ev;
        $cvalues[$cmap[$f]] = $cv;
      }
    }
    $r = array(
           'id'=>$identifier,
           'media' => array(),
           'link'  => "{$base_url}/{$identifier}",
           'metadata_en' => $evalues,
           'metadata_zh' => $cvalues,
         );
  
    _coll_get_feature_image($identifier); // to generate video icon
    $files = _coll_get_digifiles($identifier);
    if($files) {
      foreach($files as $file) {
        $r['media'][] = file_create_url($file);
      }
    }

    //print_r($r);
    drupal_json_output($r);
    exit;  
}

function is_editor() {
  // Load the currently logged in user.
   global $user;

   myddl($user);

   // Check if the user has the 'Editor' role.
   if ($user->uid == 1 || in_array('Editor', $user->roles) || in_array('System Administrator', $user->roles)) {
     return true;
   }
   return false;
}


// 最新消息列表
function news_list() {

  $header = array(
    'title'   => array('data' => t('標題'), 'field' => 'title', 'sort' => 'desc'),
    'created' => array('data' => t('發表日期'), 'field' => 'created'),
  );  

  if(is_editor()) {
    $header = array(
      'title'   => array('data' => t('標題'), 'field' => 'title', 'sort' => 'desc'),
      'created' => array('data' => t('發表日期'), 'field' => 'created'),
      'edit'  => array('data' => t('編輯'), 'field' => 'edit'),
    );  
  }


  $query = db_select('node', 'n')
           ->extend('PagerDefault');

  $query->fields('n', array('nid', 'title', 'created', 'changed', 'status'))
        ->orderBy('created', 'DESC')
        ->limit(10);

  $query->condition('n.type', 'news');

  $rows = array();
  $result = $query->execute();
  while($record = $result->fetchAssoc()) { 
    $nid = $record['nid'];
    $title = $record['title'];
    $created = $record['created'];
    $changed = $record['changed'];

    //print_r($record);

    $title_field = array('data'=> array('#markup' => l($title, "/node/{$nid}")));
    $created_field = format_date($created, 'short');
    $edit_field = array('data'=> array('#markup' => l('編輯', "/node/{$nid}/edit/")));

    if(is_editor()){
      $rows[] = array(
        'title' => $title_field,
        'created' => $created_field,
        'edit'    => $edit_field
      );  
    } else {
      $rows[] = array(
        'title' => $title_field,
        'created' => $created_field,
      );  
    }
  }

  $build = [];

  if(is_editor()) {
    $build['add'] = array(
      '#type' => 'item',
      '#markup' => '<a href="/node/add/news" class="btn btn-primary" role="button">新增</a>',
      '#weight' => 998
    );  
  }

  if($user->uid == 1) {
    $the_pager = theme('pager', array('element' => 1));
  } else {
    $the_pager = theme('pager');
  }
  
  $build['pager'] = array(
  	'#markup' => $the_pager,
  	'#weight' => 999
  );


  $build['table'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    //'#options' => $options,
    //'#multiple' => TRUE,
    '#empty' => t('No content')
  );

  //print_r(drupal_render($build));
  //exit();

  return drupal_render($build);
}