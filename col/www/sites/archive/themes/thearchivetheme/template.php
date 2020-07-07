<?php

function thearchivetheme_preprocess_html(&$vars) {
  drupal_add_css(path_to_theme() . '/css/ie8.css', array('group' => CSS_THEME, 'browsers' => array('IE' => '(lt IE 9)', '!IE' => FALSE), 'preprocess' => FALSE));
  drupal_add_js( path_to_theme() . '/js/animate-plus.js');
  drupal_add_css(path_to_theme() . '/css/animate.css');
}

function thearchivetheme_process_page(&$vars) {
  $usernav = '';
  if(user_is_logged_in()){
    global $user;
    $node = menu_get_object();
    $ar = array();
    if($node && $node->type == 'collection'){
      $nid = $node->nid;
      if(user_access('update collection')) $ar[] = '<a href="collection/edit/'.$nid.'">編輯</a>';
      if(user_access('update collection')) $ar[] = '<a href="collection/update/file/'.$nid.'">更新數位檔</a>';
      if(user_access('update collection') && editcol_is_video_collection($nid)) $ar[] = '<a href="collection/upload/video_icons/'.$nid.'">自定影片圖示</a>';
      if(user_access('delete collection')) $ar[] = '<a href="collection/delete/'.$nid.'">刪除</a>';
    }
    $ar[] = '<a href="/sets">特藏集</a>';
    if(user_access('control panel'))  $ar[] = '<a href="/control_panel/1">Control Panel</a>';
    $ar[] = 'Login as '.$user->name;
    $ar[] = '<a href="/user/logout">Log out</a>';
    $usernav = '<div class="row">'.implode(' | ', $ar).'</div>';
  }
  $vars['usernav'] = $usernav;
}

function thearchivetheme_breadcrumb($vars) {
  $breadcrumbs = $vars['breadcrumb'];
  $hometext = '<span class="glyphicon glyphicon-home" aria-hidden="true"></span>';
  $home = array_shift($breadcrumbs);
  $home = l($hometext, '<front>', array('html' =>true, 'attributes'=>array('class'=>array('home') ) ));
  array_unshift($breadcrumbs, $home);
  $out = _wgtheme_theme_breadcrumb($breadcrumbs, ' <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span> ');
  return $out;
}

// ref : https://www.drupal.org/node/337022
function thearchivetheme_preprocess_node(&$variables) {
  $node = $variables['node'];

  // Create preprocess functions per content type.
  $function = __FUNCTION__ . '_' . $node->type;
    if (function_exists($function)) {
      $function($variables);
    }
}

function output_pure_content($content) {

  if(empty($content)) return "";

  //if(isHTML($content)) return $content;

  if(strstr($content, "\r\n")) $d = "\r\n";
  else                         $d = "\n";

  //$contents = array_filter(explode($d, $content));
  $contents = explode($d, $content);
  $contents = array_map(function($item) {
      if($item == '') return "<br>";
      return "<p class='resizable-text'>" . $item . "</p>";
  }, $contents);
  return implode("", $contents);
}


function thearchivetheme_preprocess_node_set(&$variables) {
  drupal_add_css(drupal_get_path('theme', 'thearchivetheme') . "/css/set.css");
  drupal_add_js(drupal_get_path('theme', 'thearchivetheme') . "/js/set.js");
  $node = $variables['node'];

  $collections = !empty($node->field_collections)? drupal_json_decode($node->field_collections['und'][0]['value']) : [];

  // meta datas
  // l($qs, 'search', array('query'=> array('qs'=>$qs)))

  $meta = [];
  // 識別號
  if(!empty($node->field_identifier)) $meta[] = ['label' => '識別號 identifier', 'value' => $node->field_identifier['und'][0]['value']];

  // 發布日期
  if(!empty($node->field_release_date)) {
    $date = date('Y/m/d', $node->field_release_date['und'][0]['value']);
    $meta[] = ['label' => '發布日期 release_date', 'value' => $date];
  }

  // 發布者
  if(!empty($node->field_publisher)) $meta[] = ['label' => '發布者 publisher', 'value' => $node->field_publisher['und'][0]['value']];

  // 姓名標示值
  if(!empty($node->field_license_note)) $meta[] = ['label' => '姓名標示值 license_note', 'value' => $node->field_license_note['und'][0]['value']];

  // 授權條款
  if(!empty($node->field_license)) {
    $tid = $node->field_license['und'][0]['tid'];
    $term = taxonomy_term_load($tid);
    $term_html = l($term->name, 'search', array('query'=> array('qs'=>$term->name))); // ex. <a href="/search?qs=hello">hello</a>
    $meta[] = ['label' => '授權條款 license', 'value' => $term_html];
  }
  // 地點
  if(!empty($node->field_location)) $meta[] = ['label' => '地點 location', 'value' => $node->field_location['und'][0]['value']];

  // 公開與否
  if(!empty($node->field_public)) {
    $tid = $node->field_public['und'][0]['tid'];
    $term = taxonomy_term_load($tid);
    $term_html = l($term->name, 'search', array('query'=> array('qs'=>$term->name))); // ex. <a href="/search?qs=hello">hello</a>
    $meta[] = ['label' => '公開與否 public', 'value' => $term_html];
  }
  // 公開與否註記
  if(!empty($node->field_public_note)) $meta[] = ['label' => '公開與否註記 public_note', 'value' => $node->field_public_note['und'][0]['value']];

  // 關鍵字
  if(!empty($node->field_keyword)) {
    $keywords = "";
    foreach($node->field_keyword['und'] as $k) {
      $tid = $k['tid'];
      $term = taxonomy_term_load($tid);
      $term_html = l($term->name, 'search', array('query'=> array('qs'=>$term->name)));
      $keywords .= $term_html;
    }
    $meta[] = ['label' => '關鍵字 keyword' , 'value' => $keywords];
  }

  $variables['description'] = output_pure_content($node->field_description['und'][0]['value']);

  $variables['set_meta'] = $meta;

  $images = [];
  foreach($collections as $collection_id) {
    $image_link = _coll_get_feature_image($collection_id, 'medium', url("node/{$collection_id}"), true);
    if(!$image_link) continue; // 檔案不存在
    $images[] = "<li class='ui-state-default col-xs-6 col-sm-4 col-md-3 col-lg-2' id=\"{$collection_id}\">" . $image_link . '</li>';
  }

  $variables['collections'] = $images;
}
