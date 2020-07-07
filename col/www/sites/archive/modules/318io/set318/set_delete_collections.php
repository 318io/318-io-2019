<?php

function set_delete_collections($set_nid) {
  drupal_add_css(drupal_get_path('module', 'set318') . "/css/set.css");
  drupal_add_js(drupal_get_path('module', 'set318') . "/js/set.js");

  //$rowperpage = _coll_get_rowperpage(); // $r = DT::array_get($_GET, 'rowperpage', 20);
  $rowperpage = 24;

  $set = node_load($set_nid);

  $r =[];
  if(!empty($set)) {
    $collections = !empty($set->field_collections)? drupal_json_decode($set->field_collections['und'][0]['value']) : [];
    $build=[];
    $build['#collections'] = set_icon_for_selection($collections);
    $build['#theme'] = 'set_selection_grid';
    $build['#qs'] = $qs;
    $build['#nid'] = $set_nid;
    $build['#type'] = 'del';
    $r[] = $build;
  }

  //$r[] = array('#markup' => $nav, );
  /*
  if($mode== 'ajax') {
    $ret = array('content' => render($r));
    print drupal_json_output($ret);
    exit();
  }*/
  return drupal_render($r);
}
