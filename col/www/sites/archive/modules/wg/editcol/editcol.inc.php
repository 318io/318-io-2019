<?php

//2019.11 added

function export_csv($range) {
    $options['nid_range'] = $range;  
    _collarchive_crud_process_export_archive($options);
}
  
// $key would be a key like 'csv_PpQXn7COf'
function export_csv_unordered($key) {
    $nids = variable_get($key, array());
    if(!empty($nids)) {
        $options['nids'] = $nids;
        variable_del($key);
        _collarchive_crud_process_export_archive($options);
    } else {
        drupal_set_message('export_csv_unordered(): invalid key.');
    }
}
  
function get_post_json() {
    // http://php.net/manual/en/reserved.variables.server.php
    // Validate the request is a post and return proper response code on failure.
    if ($_SERVER['REQUEST_METHOD'] != 'POST') { header('HTTP/1.1 405 Not Post'); }
  
    $content_type = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]);
    if($content_type != 'application/json') { header('HTTP/1.1 415 Unsupported Content Type'); }
  
    // parse json
    $received_json = file_get_contents("php://input",  TRUE);
    $json = drupal_json_decode($received_json, TRUE); // http://php.net/json_decode
    return $json;
}
  
// [[ 更新數位檔 --------------------------------------------------------------------------------------------------------------------------
function _choose_collection_file_update_way($form, &$form_state, $collection_id) {
  
    $form['title'] = array(
      '#type' => 'item',
      '#markup' => '<p>請選擇更新數位檔的方式：</p>'
    );
  
    $form['collection_id'] = array(
      '#type' => 'hidden',
      '#value' => $collection_id
    );
  
    $form['all'] = array(
      '#type' => 'submit',
      '#value' => t('全部'),
      '#submit' => array('redirect_file_update_all')
    );
  
    $form['part'] = array(
      '#type' => 'submit',
      '#value' => t('個別'),
      '#submit' => array('redirect_file_update_idv')
    );
  
    $form['desc'] = array(
      '#type' => 'item',
      '#markup' => '<p></p><p>Note: <ul><li>選擇「全部」會將舊的檔案全部刪除，然後使用新的檔案取代。</li><li>影片類藏品只能全部更新。</li></ul></p>'
    );
  
    return $form;
}
  
function redirect_file_update_all($form , &$form_state) {
    $collection_id = $form_state['values']['collection_id'];
    drupal_goto('collection/update/file/all/' . $collection_id);
}
  
function redirect_file_update_idv($form , &$form_state) {
    $collection_id = $form_state['values']['collection_id'];
  
    if(is_empty_collection($collection_id)) {
      drupal_set_message('此藏品無數位檔，請新增。');
      drupal_goto('collection/update/file/all/' . $collection_id);
    }
  
    if(editcol_is_video_collection($collection_id)) {
      drupal_set_message('影片檔不支援個別更新，使用全部更新界面。');
      drupal_goto('collection/update/file/all/' . $collection_id);
    } else {
      drupal_goto('collection/update/file/idv/' . $collection_id);
    }
}
  
function collection_file_update_all_upload($nid) {
  
    $p = drupal_get_path('module', 'editcol');
  
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.css");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.theme.min.css");
    drupal_add_css("$p/js/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css");
    drupal_add_css("$p/css/modal.css");
  
    drupal_add_js("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.js");
    drupal_add_js("$p/js/plupload/js/plupload.full.min.js");
    drupal_add_js("$p/js/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js");
    drupal_add_js("$p/js/modal.js");
  
    if(is_empty_collection($nid)) {
      return _collection_all_file_update_form($nid, 2); //defined at editcol_form_function.php
    }
  
    // 若 $nid 是影片檔，關閉 interactive mosiac mode, 並只允許新增影片
    if(editcol_is_video_collection($nid)) {
      return _collection_all_file_update_form($nid, 0); //defined at editcol_form_function.php
    } else {
      return _collection_all_file_update_form($nid, 1); //defined at editcol_form_function.php
    }
}
  
// 更新全部數位檔案
function _collection_all_file_update_form_submit() {
    $json =  get_post_json(); // { uniq: ... }
    $uniq =  $json['uniq'];
    $nid  =  $json['nid'];
    if(empty($json) || empty($uniq) || empty($nid)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
  
    // delete old files first, all thumbnails will be deleted as well
    delete_collection_files($nid);
  
    $node = node_load($nid);
  
    if(!empty($node)) {
      $repid = $node->field_repository_id[LANGUAGE_NONE][0]['value'];
      $nid = _do_single_upload($uniq, $repid, $node);
      modify_update_info($nid);
      drupal_json_output('{"nid":' . $nid. "}");
    } else {
      header("HTTP/1.0 406 Request is Not Acceptable"); exit;
    }
}
  
// 單檔更新的選擇介面
function collection_file_update_idv($form , &$form_state, $nid) {
  
    $p = drupal_get_path('module', 'editcol');
  
    drupal_add_css("$p/css/single_file_selection.css");
  
  
    $targets  = get_store_of_collection($nid);
    $archve   = $targets[0];
    $archive0 = $targets[1];
    $archive_mosbk = $targets[2];
    $public   = $targets[3];
    $public0  = $targets[4];
  
    $files = get_collection_files($nid);
    $archive_files = $files['archive'];
  
    foreach($archive_files as $a_file_name) {
  
      $the_file_id = filename_without_ext($a_file_name);
  
  
      if(is_mosaic_file_in_archive($nid, $a_file_name) ) { // 此檔為馬賽克檔
        $the_mosaic_file = $public . '/'  . $the_file_id . '.jpg';
        $the_normal_file = $public0 . '/' . $the_file_id . '.jpg';
        $the_mosaic_icon = image_style_url('200_300', $the_mosaic_file);
        $the_normal_icon = image_style_url('200_300', $the_normal_file);
  
        $form[$the_file_id] = array(  // top container for this file
          '#type' => 'container',
          '#title' => $a_file_name,
          '#attributes' => array('class' => array('single_selection_top_container_2_file')),
          //'#weight' => 5,
        );
  
        $form[$the_file_id][$the_file_id . '_mosaic'] = array(   // sub container for mosiac file
          '#type' => 'container',
          '#title' => $the_file_id . '_mosaic',
          '#attributes' => array('class' => array('single_selection_sub_container')),
          //'#weight' => 5,
        );
        $form[$the_file_id][$the_file_id . '_normal'] = array(   // sub container for normal file
          '#type' => 'container',
          '#title' => $the_file_id . '_normal',
          '#attributes' => array('class' => array('single_selection_sub_container')),
          //'#weight' => 5,
        );
  
        $form[$the_file_id][$the_file_id . '_mosaic']['icon'] = array(
          '#type' => 'item',
          '#title' => $the_file_id . ' 馬賽克檔',
          '#markup' => "<div class='single_selection_image_icon'><img src='$the_mosaic_icon'></div>"
        );
  
        $form[$the_file_id][$the_file_id . '_mosaic']['update'] = array(
          '#type' => 'button',
          '#value' => t('更新'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/mosaic/file/update/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
  
        $form[$the_file_id][$the_file_id . '_mosaic']['delete'] = array(
          '#type' => 'button',
          '#value' => t('刪除'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/mosaic/file/delete/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['icon'] = array(
          '#type' => 'item',
          '#title' => $the_file_id . ' 典藏檔',
          '#markup' => "<div class='single_selection_image_icon'><img src='$the_normal_icon'></div>"
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['update'] = array(
          '#type' => 'button',
          '#value' => t('更新'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/normal/file/update/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['delete'] = array(
          '#type' => 'button',
          '#value' => t('刪除'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/normal/file/delete/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
  
      } else {                                        // 此檔為非馬賽克檔
  
        $the_normal_file = $public . '/' . $the_file_id . '.jpg';
        $the_normal_icon = image_style_url('200_300', $the_normal_file);
  
        $form[$the_file_id] = array(  // top container for this file
          '#type' => 'container',
          '#title' => $a_file_name,
          '#attributes' => array('class' => array('single_selection_top_container_1_file')),
          //'#weight' => 5,
        );
  
        $form[$the_file_id][$the_file_id . '_normal'] = array(   // sub container for normal file
          '#type' => 'container',
          '#title' => $the_file_id .'_normal' ,
          '#attributes' => array('class' => array('single_selection_sub_container_wide')),
          //'#weight' => 5,
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['icon'] = array(
          '#type' => 'item',
          '#title' => $the_file_id . ' 典藏檔',
          '#markup' => "<div class='single_selection_image_icon'><img  src='$the_normal_icon'></div>"
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['update'] = array(
          '#type' => 'button',
          '#value' => t('更新'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/normal/file/update/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['add'] = array(
          '#type' => 'button',
          '#value' => t('新增馬賽克'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/normal/file/addmosaic/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
  
        $form[$the_file_id][$the_file_id . '_normal']['delete'] = array(
          '#type' => 'button',
          '#value' => t('刪除'),
          //'#prefix' => '&nbsp;',
          '#attributes' => array('onClick' => "window.location.href='/collection/$nid/normal/file/delete/$a_file_name'; return true;"),
          '#post_render' => array('change_button_type'),
        );
      }
    }
    return $form;
}
  

// plupload single file upload 介面
function collection_mosaic_file_update($nid, $filename) {
    $p = drupal_get_path('module', 'editcol');
  
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.css");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.theme.min.css");
    drupal_add_css("$p/js/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css");
    drupal_add_css("$p/css/modal.css");
  
    drupal_add_js("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.js");
    drupal_add_js("$p/js/plupload/js/plupload.full.min.js");
    drupal_add_js("$p/js/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js");
    drupal_add_js("$p/js/modal.js");
  
    return _collection_idv_file_update_form($nid, $filename, 'update_mosaic'); //defined at editcol_form_function.php
}
  

function collection_mosaic_file_delete($form , &$form_state, $nid, $filename) {
  
    $targets  = get_store_of_collection($nid);
    $public   = $targets[3];
    $the_file_id = filename_without_ext($filename);
    $the_mosaic_file = $public . '/'  . $the_file_id . '.jpg';
    $the_mosaic_icon = image_style_url('large', $the_mosaic_file);
  
    $form['delete_file'] = array(
      '#type' => 'item',
      '#title' => t('你正要刪除一個馬賽克檔，你確定嗎？(Are you sure ?)'),
      '#markup' => "<div><img src='$the_mosaic_icon'></div>"
    );
  
    $form['collection_id'] = array(
      '#type' => 'hidden',
      '#value' => $nid
    );
  
    $form['filename'] = array(
      '#type' => 'hidden',
      '#value' => $filename
    );
  
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
    );
  
    /* reference : https://www.drupal.org/node/133861 */
    $form['cancel'] = array(
        '#type' => 'button',
        '#value' => t('Cancel'),
        '#prefix' => '&nbsp;',
        '#attributes' => array('onClick' => 'history.go(-1); return true;'),
        '#post_render' => array('change_button_type'),
    );
    return $form;
}
  

function collection_mosaic_file_delete_submit($form , &$form_state) {
    $nid = $form_state['values']['collection_id'];
    $filename = $form_state['values']['filename'];
    delete_archive_file_of_collection($nid, $filename, true);
    drupal_goto($nid);
}
  

// plupload single file upload 介面
function collection_normal_file_update($nid, $filename) {
    $p = drupal_get_path('module', 'editcol');
  
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.css");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.theme.min.css");
    drupal_add_css("$p/js/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css");
    drupal_add_css("$p/css/modal.css");
  
    drupal_add_js("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.js");
    drupal_add_js("$p/js/plupload/js/plupload.full.min.js");
    drupal_add_js("$p/js/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js");
    drupal_add_js("$p/js/modal.js");
  
    return _collection_idv_file_update_form($nid, $filename, 'update_normal'); //defined at editcol_form_function.php
}
  
function collection_normal_file_delete($form , &$form_state, $nid, $filename) {
  
    $targets  = get_store_of_collection($nid);
    $archve   = $targets[0];
    $archive0 = $targets[1];
    $public   = $targets[3];
    $public0  = $targets[4];
  
    $the_file_id = filename_without_ext($filename);
  
    if(has_file_in_archive0($nid, $filename)) { // 此非馬賽克檔位在 archive0
      $the_normal_file = $public0 . '/' . $the_file_id . '.jpg';
      $the_normal_icon = image_style_url('large', $the_normal_file);
    } else {                                    // // 此非馬賽克檔位在 archive
      $the_normal_file = $public . '/' . $the_file_id . '.jpg';
      $the_normal_icon = image_style_url('large', $the_normal_file);
    }
  
    $form['delete_file'] = array(
      '#type' => 'item',
      '#title' => t('你正要刪除一個典藏檔，刪除典藏檔會連馬賽克檔一併刪除，你確定嗎？(Are you sure ?)'),
      '#markup' => "<div><img src='$the_normal_icon'></div>"
    );
  
    $form['collection_id'] = array(
      '#type' => 'hidden',
      '#value' => $nid
    );
  
    $form['filename'] = array(
      '#type' => 'hidden',
      '#value' => $filename
    );
  
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
    );
  
    /* reference : https://www.drupal.org/node/133861 */
    $form['cancel'] = array(
        '#type' => 'button',
        '#value' => t('Cancel'),
        '#prefix' => '&nbsp;',
        '#attributes' => array('onClick' => 'history.go(-1); return true;'),
        '#post_render' => array('change_button_type'),
    );
    return $form;
}
  
function collection_normal_file_delete_submit($form , &$form_state) {
    $nid = $form_state['values']['collection_id'];
    $filename = $form_state['values']['filename'];
    delete_archive_file_of_collection($nid, $filename, false);
    drupal_goto($nid);
}
  
// plupload single file upload 介面
function collection_normal_file_addmosaic($nid, $filename) {
    $p = drupal_get_path('module', 'editcol');
  
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.css");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.theme.min.css");
    drupal_add_css("$p/js/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css");
    drupal_add_css("$p/css/modal.css");
  
    drupal_add_js("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.js");
    drupal_add_js("$p/js/plupload/js/plupload.full.min.js");
    drupal_add_js("$p/js/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js");
    drupal_add_js("$p/js/modal.js");
  
    return _collection_idv_file_update_form($nid, $filename, 'add_mosaic'); //defined at editcol_form_function.php
}
  
function _collection_idv_file_update_form_action() {
  
    $json = get_post_json(); // { uniq: ... }
  
    $uniq     = $json['uniq'];
    $nid      = $json['nid'];
    $op       = $json['op']; // update_mosaic, update_normal, add_mosaic
    $filename = $json['filename'];
  
    // mylog(print_r($json, true), 'json.txt');
  
    if(empty($json) || empty($uniq) || empty($nid) || empty($op)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
  
    // source path
    $source_path = '/tmp' . DIRECTORY_SEPARATOR . "plupload_" . $uniq;
    if (!is_dir($source_path) || !opendir($source_path)) {
      header("HTTP/1.0 406 Request is Not Acceptable since no uploaded files are founded in dir: ". $source_path . ".");
      exit;
    }
  
    try {
      switch($op) {
        case 'update_mosaic':
          //mylog('update_mosaic', 'debug.txt');
          update_archive_file_of_collection($nid, $source_path, $filename, true);
          break;
        case 'update_normal':
          //mylog('update_normal', 'debug.txt');
          update_archive_file_of_collection($nid, $source_path, $filename, false);
          break;
        case 'add_mosaic':
          //mylog('add_mosaic', 'debug.txt');
          add_mosaic_file_of_collection($nid, $source_path, $filename);
          break;
        default:
          drupal_set_message('_collection_idv_file_update_form_submit(): unknown operation.');
      }
      modify_update_info($nid);
    } catch(Exception $e) {
      //mylog(print_r($e, true), 'bug.txt');
      dbug_message($e);
    }
  
    drupal_json_output('{"nid":' . $nid. "}");
}
  
  
// 更新數位檔 ]]--------------------------------------------------------------------------------------------------------------------------
  
function control_panel($tab_id) {
    $p = drupal_get_path('module', 'editcol');
    //$p = url($p);
  
    drupal_add_css("$p/css/w2ui-1.4.2.min.css", array('group' => CSS_DEFAULT, 'type' => 'file'));
    drupal_add_css("$p/css/modal.css");
  
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_js("$p/js/w2ui-1.4.2.min.js");
    drupal_add_js("$p/js/w2ui_extend.js");
    drupal_add_js("$p/js/modal.js");
  
    //------------------
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.css");
    drupal_add_css("$p/js/jquery-ui-1.11.2.custom/jquery-ui.theme.min.css");
    drupal_add_css("$p/js/plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css");
  
    drupal_add_js("$p/js/jquery-ui-1.11.2.custom/jquery-ui.min.js");
  
    drupal_add_js("$p/js/plupload/js/plupload.full.min.js");
    drupal_add_js("$p/js/plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js");
  
    $model  = _form_model('collection');
  
    return _control_panel($tab_id, $model);
}
  
  
function icon_upload_form($form, &$form_state, $nid) {
    //drupal_set_message($nid);
  
    $store_dir  = strval3(floor($nid / 1000));
    $target_path = drupal_realpath('public://') . DIRECTORY_SEPARATOR . 'digicoll' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $store_dir;
  
    $form['head'] = array(
      '#type' => 'item',
      '#title' => '',
      '#markup'=> "<h4>請依序上傳每個影片的自定圖示。</h4>",
    );
  
    foreach(num_generator(1, 1000) as $i) {
  
      $video_file_name = $nid . sprintf('_%03d.webm', $i);
      $real_path = $target_path . DIRECTORY_SEPARATOR . $video_file_name;
      $full_path = file_create_url('public://digicoll'. DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR .  $store_dir . DIRECTORY_SEPARATOR . $video_file_name);
  
      if(!file_destination($real_path,  FILE_EXISTS_ERROR)) { // file exist
        $form['video_' . $i] = array(
          '#type' => 'item',
          '#title' => '',
          '#markup'=> "<video class='' style='width: 480px;height: 360px;' controls><source src=\"$full_path\" type='video/webm'>Your browser does not support the video tag.</video>",
        );
        $form['icon_' . $i] = array(
          '#type' => 'file',
          '#title' => $nid . sprintf('_%03d_icon.jpg', $i),
          '#description' => t('Upload a file, allowed extensions: jpg, jpeg, png, gif'),
        );
  
        $form_state['editcol_max_counter'] = $i; // record the max counter
      } else {
        break;
      }
    }
  
    if($form_state['editcol_max_counter'] > 0) {
  
      $form_state['editcol_nid'] = $nid; // save custom data for further process
  
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
    } else {
      $form['nothing'] = array(
        '#type' => 'item',
        '#title' => 'no video.',
        '#markup'=> "<p>This is not a video collection.</p>",
      );
    }
  
    return $form;
}
  
// 2015.08.11
// 2016.04.15 改用 archive 下的 ogv 檔判別
function editcol_is_video_collection($nid) {
    $store_dir  = strval3(floor($nid / 1000));
    //$target_path = drupal_realpath('public://') . DIRECTORY_SEPARATOR . 'digicoll' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $store_dir;
    $target_path = drupal_realpath('public://') . DIRECTORY_SEPARATOR . 'digicoll' . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . $store_dir;
    $video_file_name = $nid . sprintf('_%03d.ogv', 1); // $nid_001.ogv
  
    $real_path = $target_path . DIRECTORY_SEPARATOR . $video_file_name;
  
    if(!file_destination($real_path,  FILE_EXISTS_ERROR)) { // file exist
      return true;
    } else {
      return false;
    }
}
  
// 2015.08.11
function validate_and_save_icon($icon_form_field_name, $target_path, $filename) {
  
    // Drupal will attempt to resize the image if it is larger than
    // the following maximum dimensions (width x height)
    $max_dimensions = '800x600';
  
    // We don't care about the minimum dimensions
    $min_dimensions = 0;
  
    $file = file_save_upload(
      $icon_form_field_name,    // <-- the icons form field name
      array(
        'file_validate_extensions' => array('jpg jpeg'),
        'file_validate_is_image' => array(),
        'file_validate_image_resolution' => array($max_dimensions, $min_dimensions)
      ),
      FALSE,
      FILE_EXISTS_REPLACE
    );
  
    //mylog(print_r($file, true), 'aaa.txt');
  
    // If the file passed validation:
    if (isset($file->filename)) {
      // Move the file, into the Drupal file system
      if (file_move($file, $target_path . DIRECTORY_SEPARATOR . $filename, FILE_EXISTS_REPLACE)) {
        $file->status = FILE_STATUS_PERMANENT;
        @file_save($file);
      }
      else {
        form_set_error('file', t('Failed to write the uploaded file the site\'s file folder.'));
        return false;
      }
    }
    else {
      form_set_error('file', t('Invalid file, only images with the extension png, gif, jpg, jpeg are allowed'));
      return false;
    }
  
    drupal_set_message($file->filename . " is validated and saved.");
    return true;
}

// 2015.08.11
// ref
//   http://alvinalexander.com/drupal-code-examples/drupal-6-examples-module/form_example/form_example_tutorial.inc.shtml
//   http://www.bluepiccadilly.com/2011/12/handling-image-uploads-custom-validation-using-drupal-6-forms-api
function icon_upload_form_validate($form , &$form_state) {
  
    $nid = !empty($form_state['editcol_nid']) ? $form_state['editcol_nid'] : -1;
    $max_counter = !empty($form_state['editcol_max_counter']) ? $form_state['editcol_max_counter'] : -1;
  
    if($nid < 0 || $max_counter < 0) {
      form_set_error('file', t('nid or max_counter is negative.'));
      return;
    }
  
    $store_dir  = strval3(floor($nid / 1000));
    $target_path = 'public://digicoll' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $store_dir;
    //$target_path = drupal_realpath('public://') . DIRECTORY_SEPARATOR . 'digicoll' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $store_dir;
  
    foreach(num_generator(1, $max_counter) as $i) {
      $filename = $nid . sprintf('_%03d_icon.jpg', $i);
      validate_and_save_icon('icon_' . $i, $target_path, $filename);
    }
}
  
function icon_upload_form_submit($form, &$form_state) {
    $nid = !empty($form_state['editcol_nid']) ? $form_state['editcol_nid'] : -1;
    drupal_set_message(t('The form has been submitted and the image has been saved.'));
    delete_all_thumbnail_of_collection($nid);
    modify_update_info($nid);
    drupal_goto('/' . $nid);
}
  
function collection_multiple_upload_finish() {
  
    // 1. move to archive and create a new node
    $json =  get_post_json(); // { uniq: ... }
    $uniq =  $json['uniq'];
    if(empty($json) || empty($uniq)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
  
    // source path
    $source_path = '/tmp' . DIRECTORY_SEPARATOR . "plupload_" . $uniq;
    if (!is_dir($source_path) || !opendir($source_path)) {
      header("HTTP/1.0 406 Request is Not Acceptable since no uploaded files are founded in dir: ". $source_path . ".");
      exit;
    }
  
    // collect all nodes from file name and create it.
    $mapping = create_multiple_upload_nodes($source_path); // defined at editcol.upload.php
  
    mylog(print_r($mapping, true), 'mapping.txt');
  
    bg_unzip_move_and_mconvert($mapping, $source_path, $uniq);
  
    //mylog("$source_path | $uniq", "here.txt");
  
    $i = count($mapping);
  
    $range = "0-0";
    if($i > 0) {
      $first = current($mapping);
      $last  = array_values($mapping)[$i-1];
      $range = $first . "-" . $last;
    }

    myddl($range, 'multiple_upload_range.txt');
  
    drupal_json_output('{"status": "ok", "range": "' . $range . '"}');
}
  
/*
* 20151208 數位檔大量更新
*/
function collection_multiple_update_finish() {
  
    // 1. move to archive and create a new node
    $json =  get_post_json(); // { uniq: ... }
    $uniq =  $json['uniq'];
    if(empty($json) || empty($uniq)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
  
    // source path
    $source_path = '/tmp' . DIRECTORY_SEPARATOR . "plupload_" . $uniq;
    if (!is_dir($source_path) || !opendir($source_path)) {
      header("HTTP/1.0 406 Request is Not Acceptable since no uploaded files are founded in dir: ". $source_path . ".");
      exit;
    }
  
    // collect all nodes from file name.
    // $nodes is [ $repository_id => $node_id ....]
    $mapping = get_nodes_from_zipfiles_in_dir($source_path); // defined at editcol.upload.php
  
    mylog(print_r($mapping, true), 'mapping-for-update.txt');
  
    bg_unzip_move_and_mconvert($mapping, $source_path, $uniq);
  
    //mylog("$source_path | $uniq", "here.txt");
  
    $nids = array();
    foreach($mapping as $rid => $nid) { $nids[] = $nid; }
  
    post_multiple_update($nids); // 更新時間與公開藏品, 日後可能會把公開藏品拿掉
  
    $key = uniqid('csv_');
    variable_set($key, $nids);
  
    drupal_json_output('{"status": "ok", "key": "' . $key . '"}');
}
  
function modify_update_info($nid) {
    global $user;
  
    $node = node_load($nid);
    if($node) {
      $record = _get_form_record('collection', $node); // def at editcol.form_model.php
      $record_to_save = _normalize_for_collection_save($record); // // def at editcol.form_model.php
      $record_to_save['field_updated_time'] = date("m/d/Y H:i");
      $record_to_save['field_updator'] = $user->name;
      $node = _coll_item_save($record_to_save); // @ coll/coll.inc
      ft_table_update($node); // @ expsearch
    } else {
      drupal_set_message("modify_update_info(): unable to load node($nid)");
    }
}
  
  
/*
* 20151221
* 1. 設定為公開
* 2. 設定更新時間
*/
function post_update($nid) {
  
    global $user;
  
    $node = node_load($nid);
    if($node) {
      $record = _get_form_record('collection', $node); // def at editcol.form_model.php
      $record_to_save = _normalize_for_collection_save($record); // // def at editcol.form_model.php
      $record_to_save['field_updated_time'] = date("m/d/Y H:i");
      $record_to_save['field_updator'] = $user->name;
      $record_to_save['field_public'] = "是"; // 設定為公開
      $node = _coll_item_save($record_to_save); // @ coll/coll.inc
      ft_table_update($node); // @ expsearch
    } else {
      drupal_set_message("post_update(): unable to load node($nid)");
    }
}
  
/*
* 20151221
* 1. 設定為公開
* 2. 設定更新時間
*/
function post_multiple_update($nids) {
    foreach($nids as $nid) { post_update($nid); }
}
  
/*
    1. move uploaded file to archive and create a new node
    2. convert uploaded file noblockingly.
    3. return json with New node id.
*/
function _do_single_upload($uniq, $repository_id = null, $node = null) {
    // 1. get source path
    $source_path = '/tmp' . DIRECTORY_SEPARATOR . "plupload_" . $uniq;
    if (!is_dir($source_path) || !$src_dir_handler = opendir($source_path)) {
      header("HTTP/1.0 406 Request is Not Acceptable since no uploaded file are founded in dir: ". $source_path . ".");
      exit;
    }
  
    // 2. move file to archive folder and
    //    create a new node if $node is empty
    $archive = move_to_archive($source_path, $node);
  
    // for collection_file_update()
    if(!empty($repository_id) && !empty($node)) {
      $node->field_repository_id[LANGUAGE_NONE][0]['value'] = $repository_id;
      node_save($node);
      $archive['repository_id'] = $repository_id;
    }
  
    //mylog(print_r($archive, true), 'aaa.txt');
  
    // 3. invoke convertion non-blockingly(at background)
    bg_mconvert(array($archive), $uniq);
  
    // 4. return json with New ID, the client will redirect to meta data editing page according to the New ID
    $new_id = $archive['collection_id'];
  
    return $new_id;
}
  
function collection_single_upload_finish() {
  
    $json =  get_post_json(); // { uniq: ... }
    $uniq =  $json['uniq'];
    if(empty($json) || empty($uniq)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
  
    $new_id = _do_single_upload($uniq);
  
    drupal_json_output('{"new_id":' . $new_id. "}");
}
  
  
function collection_pl_upload() {
  
    // Make sure file is not cached (as it happens for example on iOS devices)
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
  
    if (empty($_FILES) || $_FILES['file']['error']) {
       //mylog($_FILES['file']['error'], 'die_here.txt');
       die('{"OK": 0, "info": "Failed to move uploaded file."}');
    }
  
    //[[ debug -----------------------------------
    /*
      $uniq = $_REQUEST['uniq'];
      $name  = $_REQUEST["name"];
      $name2 = $_FILES["file"]["name"];
      $chunk  = isset($_REQUEST["chunk"])  ? intval($_REQUEST["chunk"])  : 0;
      $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
      mylog($uniq . '_' . $name . '_' . $name2, 'log_' . random_string() . ".txt");
      mylog($chunk . '_' . $chunks, 'chunk' . random_string() . ".txt");
      drupal_json_output('{"jsonrpc" : "2.0", "result" : null, "id" : 10002 }');
      die();
      */
    //debug ]] -----------------------------------
  
    /*
    // Support CORS
    header("Access-Control-Allow-Origin: *");
    // other CORS headers if any...
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      exit; // finish preflight CORS requests here
    }
    */
  
    // 5 minutes execution time
    @set_time_limit(5 * 60);
  
    // Uncomment this one to fake upload time
    // usleep(5000);
    // Settings
  
    $uniq = $_REQUEST['uniq'];
  
    $targetDir = '/tmp' . DIRECTORY_SEPARATOR . "plupload_" . $uniq;   // each session has a uniqe upload folder
  
    $cleanupTargetDir = true;     // Remove old files
    $maxFileAge       = 5 * 3600; // Temp file age in seconds
  
    // Create target dir
    if (!file_exists($targetDir)) { @mkdir($targetDir); }
  
    // Get a file name
    if (isset($_REQUEST["name"])) $fileName = $_REQUEST["name"];
    elseif (!empty($_FILES))      $fileName = $_FILES["file"]["name"];
    else                          $fileName = uniqid("file_");
  
    $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
  
    // Chunking might be enabled
    $chunk  = isset($_REQUEST["chunk"])  ? intval($_REQUEST["chunk"])  : 0;
    $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
  
    // Open temp file
    if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
      die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
    }
    if (!empty($_FILES)) {
      if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
      }
      // Read binary input stream and append it to temp file
      if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
      }
    } else {
      if (!$in = @fopen("php://input", "rb")) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
      }
    }
  
    while($buff = fread($in, 4096)) { fwrite($out, $buff); }
    @fclose($out);
    @fclose($in);
  
    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1) {
      // Strip the temp .part suffix off
      rename("{$filePath}.part", $filePath);
    }
  
    // Return Success JSON-RPC response
    drupal_json_output('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
}
  
  
function collection_edit($nid = NULL) {
    $p = drupal_get_path('module', 'editcol');
  
    drupal_add_css("$p/css/w2ui-1.4.2.min.css", array('group' => CSS_DEFAULT, 'type' => 'file'));
  
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_js("$p/js/w2ui-1.4.2.min.js");
    drupal_add_js("$p/js/w2ui_extend.js");
  
    $node = node_load($nid);
    if($node) {
      $model  = _form_model('collection', $node);
      $record = _get_form_record('collection', $node);
      return _collection_multi_page_edit_form($model, $record);
    } else {  // redirect to /collection/new
      drupal_goto('control_panel/1');
    }
}
  
function collection_delete($nid = NULL) {
    $p = drupal_get_path('module', 'editcol');
    drupal_add_css("$p/css/w2ui-1.4.2.min.css", array('group' => CSS_DEFAULT, 'type' => 'file'));
    drupal_add_js("$p/js/jquery-2.1.1.min.js");
    drupal_add_js("$p/js/w2ui-1.4.2.min.js");
  
    $buttons = <<<BTNS
    <p> All files related to the collection $nid will be deleted as well !!! </p>
    <button class="btn" id="delbtn" onclick="
      $.ajax({
        type: 'POST',
        url: '/json/delete/collection',
        data: JSON.stringify({nid: $nid}),
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        processData: true,
        success: function (data, status, jqXHR) {
          window.location = '/';
        },
        error: function (xhr) {
          alert('Error' + xhr);
        }
      });
    ">Confirm</button>
BTNS;
    return $buttons;
}
  
  
function _collection_save($raw_post) {
  
    global $user;
  
    $njson = _normalize_for_collection_save($raw_post);
  
    mylog(print_r($njson, true), 'to_save.txt');
    
    if(empty($njson['field_recorder'])) $njson['field_recorder'] = $user->name;

    if(empty($njson['field_recorded_time'])) $njson['field_recorded_time'] = date("m/d/Y H:i",time());
    if(empty($njson['field_recorded_time'])) $njson['field_updator'] = $user->name;
    $njson['field_updated_time'] = date("m/d/Y H:i",time());
  
    $node = _coll_item_save($njson); // @ coll/coll.inc
  
    if(empty($njson['nid'])) { // new node, insert into full text search table
      ft_table_insert($node);
    } else {                   // update full text search table
      ft_table_update($node);
    }
  
    bg_indexer();
}
  
function json_edit_collection() {
  
    $json = get_post_json();
    if(empty($json)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
  
    _collection_save($json);
  
    drupal_json_output(json_encode(array('status'=>'success'))); // for w2ui post
}
  
/*
* invoke when node_delete() is called.
* 清除此 collection 的相關檔案, archive, archive0, archive_mosbk, public, public0, meta 內的檔案都需刪除
*/
function editcol_node_delete($node) {
    $nid = $node->nid;
    try{
      delete_collection_files($nid); // defined in editcol.upload.php
      ft_table_delete($nid);         // defined in expsearch.admin.inc
      bg_indexer();
    } catch(Exception $e) {
      drupal_set_message('editcol_node_delete(): got exception as following:');
      dbug_message($e);
    }
}
  
/*
* node_delete() will invoke hook_node_delete().
*/
function json_delete_collection() {
    $json = get_post_json();
    if(empty($json)) { header("HTTP/1.0 406 Request is Not Acceptable"); exit; }
    $nid = $json['nid'];
    node_delete($nid);
    drupal_json_output(json_encode(array('status'=>'success'))); // for w2ui post
}
  
function json_get_form_model_collection() {
    $form_model = _form_model('collection');
    drupal_json_output(json_encode($form_model));
}


  