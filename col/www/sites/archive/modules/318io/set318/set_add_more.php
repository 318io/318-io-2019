<?php

function set_icon_for_selection($collections) {
  $images = [];
  foreach($collections as $collection_id) {
    $image_link = _coll_get_feature_image($collection_id, 'medium', url("node/{$collection_id}"), false);
    //$image_link = _coll_get_feature_image($collection_id, 'medium', false, false);
    if(!$image_link) continue; // 檔案不存在
    $images[] = "<li class='ui-state-default col-xs-6 col-sm-4 col-md-3 col-lg-2' id=\"{$collection_id}\">" . $image_link . '</li>';
  }
  return $images;
}


// copy from coll/coll.page.inc , _coll_search_page
function set_add_more($set_nid) {

  drupal_add_css(drupal_get_path('module', 'set318') . "/css/set.css");
  drupal_add_js(drupal_get_path('module', 'set318') . "/js/set.js");

  //$rowperpage = _coll_get_rowperpage(); // $r = DT::array_get($_GET, 'rowperpage', 20);
  $rowperpage = 24;

  $path = $_GET['q'];
  $mode = DT::array_get($_GET, 'mode', 'full');
  $row = DT::array_get($_GET, 'row', 0);
  $row = (int) $row;
  $qs = $_GET['qs'];
  $qs = str_replace(array('　'), ' ', $qs);
  if(array_key_exists('page', $_GET)) {
    $page = (int) $_GET['page'];
    $row = $rowperpage * ($page - 1);
    drupal_goto($path,array('query'=>array('qs'=>$qs, 'row'=>$row) ,));
  }

  if($row%$rowperpage > 0) {
    $row = floor($row/$rowperpage) * $rowperpage;
    drupal_goto($path,array('query'=>array('qs'=>$qs, 'row'=>$row) ,));
  }

  $ret = _coll_search($qs, $row, $rowperpage);
  $count = $ret['count'];
  if($count <= 0) {
    drupal_set_message(t('沒有符合<b>:qs</b>的資料，請重新搜尋', array(':qs'=>$qs)), 'error');
    drupal_goto($path);
  }
  if(!$qs) {
    $bread = '';
  } else {
    $bread = $qs;
  }

  _coll_set_breadcrumb($bread);

  $maxrow = $count-1;

  if($row > $maxrow) {
    $row = $maxrow;
    drupal_goto($path,array('query'=>array('qs'=>$qs, 'row'=>$row) ,));
  } else if($row < 0) {
    $row = 0;
    drupal_goto($path,array('query'=>array('qs'=>$qs, 'row'=>$row) ,));
  }

  $nids = $ret['identifier'];

  $build=[];
  $build['#collections'] = set_icon_for_selection($nids);
  $build['#theme'] = 'set_selection_grid';
  $build['#qs'] = $qs;
  $build['#nid'] = $set_nid;
  $build['#type'] = 'add';
  $r[] = $build;

  $nav = _set318_search_page_pagination($path, $qs, $count, $rowperpage, $row);

  $r[] = array('#markup' => $nav, );

  if($mode== 'ajax') {
    $ret = array('content' => render($r));
    print drupal_json_output($ret);
    exit();
  }
  return drupal_render($r);
}

function _set318_search_page_pagination($path, $qs, $count, $rowperpage, $row) {
  $ar = array();

  $num = 10;

  $classes_base = array('btn', 'btn-primary', 'btn-sm');

  $maxpage = floor(($count-1)/$rowperpage);
  $cpage = floor($row / $rowperpage);
  $min = $cpage - ($num/2);
  if($min < 0) $min = 0;
  $max = $min + ($num -1);
  if($max > $maxpage) {
    $max = $maxpage;
    $min = $max - ($num -1);
    if($min < 0) $min = 0;
  }

  $classes_first = array_merge($classes_base, array('first'));
  $classes_prev = array_merge($classes_base, array('prev'));
  if($row <= 0) {
    $prevrow = 0;
    $classes_prev[] = 'disabled';
    $classes_first[] = 'disabled';
  } else {
    $prevrow= $row - $rowperpage;
    if($prevrow <0) $prevrow = 0;
  }
  $viewmode = $_GET['viewmode'];
  if(!$viewmode) $viewmode='grid';

  /*
  $txt = _wg_bt_icon('th'). '';
  $classes = array_merge($classes_base, array('viewmode-grid'));
  if($viewmode == 'grid') $classes = array_merge($classes_base, array('viewmode-grid', 'disabled'));
  else $classes = array_merge($classes_base, array('viewmode-grid'));
  $ar[] = _coll_search_page_pagination_link($txt, $path, $qs, $row, $classes, array('query' => array('viewmode'=>'grid')));

  $txt = _wg_bt_icon('th-list'). '';
  if($viewmode == 'list') $classes = array_merge($classes_base, array('viewmode-list', 'disabled'));
  else $classes = array_merge($classes_base, array('viewmode-list'));
  $ar[] = _coll_search_page_pagination_link($txt, $path, $qs, $row, $classes, array('query' => array('viewmode'=>'list')));

  $txt = _wg_bt_icon('home'). ' 首頁';
  $classes = array_merge($classes_base, array('home'));
  $ar[] = _coll_search_page_pagination_link($txt, '<front>', '', 0, $classes);
  */

  $txt = _wg_bt_icon('chevron-left')._wg_bt_icon('chevron-left');
  $ar[] = _coll_search_page_pagination_link($txt, $path, $qs, 0, $classes_first, array('query' => array('viewmode'=>$viewmode)));

  $txt = _wg_bt_icon('chevron-left');
  $ar[] = _coll_search_page_pagination_link($txt, $path, $qs, $prevrow, $classes_prev, array('query' => array('viewmode'=>$viewmode)));

  for($i = $min; $i<=$max; $i++) {
    $classes = array_merge($classes_base, array('pagina'));
    if($i == $cpage) {
      $classes[] = 'disabled';
    }
    $txt = $i + 1;
    $prow = $i * $rowperpage;
    $options = array(
                 'query'=>array('qs'=>$qs, 'row'=>$prow, 'viewmode'=>$viewmode) ,
                 'attributes'=>array('class'=>$classes  ),
                 'html' => true,
               );
    $ar[] = l($txt, $path, $options);
  }

  $classes_last = array_merge($classes_base, array('last'));
  $classes_next = array_merge($classes_base, array('next'));

  $maxrow = $count-1;

  if($row > ($maxrow - $rowperpage)) {
    $nextrow = $maxrow;
    $classes_next[] = 'disabled';
    $classes_last[] = 'disabled';
  } else {
    $nextrow= $row + $rowperpage;
  }

  $txt = _wg_bt_icon('chevron-right');
  $ar[] = _coll_search_page_pagination_link($txt, $path, $qs, $nextrow, $classes_next, array('query' => array('viewmode'=>$viewmode)));

  $txt = _wg_bt_icon('chevron-right')._wg_bt_icon('chevron-right');
  $maxrow = $maxpage*$rowperpage;
  $ar[] = _coll_search_page_pagination_link($txt, $path, $qs, $maxrow, $classes_last, array('query' => array('viewmode'=>$viewmode)));

  $gotol = '<form class="goto-form goto-page-form" action="search">
           <input name="qs" type="hidden" value="'.$qs.'"/>
           <input name="page" type="text" size="3" placeholder="到X頁"/>
           <input class="submit" type="submit" value="到"/>
           </form>';

  $rowstart = $row;
  $rowend = $rowstart+$rowperpage-1;
  if($rowend > $count) $rowend = $count-1;
  $nav = '<div id="thenav">'.implode(' ', $ar). ' '.$gotol. ' (共'.($maxpage+1).'頁，'.$count.'筆，本頁顯示'.($rowstart+1).'-'.($rowend+1).'筆)'.'</div>';

  return $nav;
}
