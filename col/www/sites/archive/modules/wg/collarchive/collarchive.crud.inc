<?php

require_once 'collarchive.crud.import.inc';
require_once 'collarchive.crud.export.inc';
require_once 'collarchive.crud.sync.inc';

function _collarchive_crud_page() {
  $form = drupal_get_form('_collarchive_crud_form');
  return $form;
}

function _collarchive_crud_form($form, &$form_state) {
  $form = array();
  $vars = _coll_ef_variables();
  $form['import'] =
    array(
      '#type' => 'fieldset',
      '#title' => '匯入後設資料',
      '#collapsible' => false,
    );

  $form['import']['import_file'] =
    array(
      '#title' => '',
      '#type' => 'managed_file',
      '#description' => '限xlsx與csv檔。請確認你的資料格式正確，建議若為xlsx檔，請將各欄的型態設為文字（text）。',
      '#default_value' => '',
      '#upload_validators' => array(
        'file_validate_extensions' => array('xlsx csv'),
      ),
      '#upload_location' => $vars['path']['source'],
      '#process' => array('_collarchive_crud_import_file_element_process')
    );
  $form['import']['button_import'] =
    array(
      '#type' => 'submit',
      '#value' => t('Import'),
    );

  $minid = db_query("SELECT nid FROM {node} ORDER BY nid")->fetchField();
  $maxid = db_query("SELECT nid FROM {node} ORDER BY nid DESC")->fetchField();
  $form['export']['archive'] =
    array(
      '#type' => 'fieldset',
      '#title' => '匯出後設資料--供修改用',
      '#collapsible' => false,
      '#description' => '',
    );
  $form['export']['archive']['force_text'] =
    array(
      '#type' => 'checkbox',
      '#title' => '強制匯出文字欄位',
      '#default_value' => 1,
      '#description' => '如果excel無法正確讀取，請取消勾選，並在會入時自行設定各欄型態為文字',
        );
  $desc = t('[indetifier]-[identifier], min: @minid, max: @maxid',
            array('@minid' => $minid, '@maxid' => $maxid));
  $form['export']['archive']['nid_range'] =
    array(
      '#type' => 'textfield',
      '#title' => '識別號範圍',
      '#default_value' => $minid.'-'.$maxid,
      '#size' => 20,
      '#description' => $desc
    );
  $form['export']['archive']['button_export_archive'] = array(
        '#type' => 'submit',
        '#value' => '匯出',
      );
  $form['export']['sync'] =
    array(
      '#type' => 'fieldset',
      '#title' => '同步至公眾系統',
      '#collapsible' => false,
      '#description' => t('新增的數位檔會自動同步，但後設資料需到公眾系統手動匯入'),
    );
  $public_system_url = variable_get('public_system_url',  null);
  $public_system_www_root = variable_get('public_system_www_root',  null);
  if(!$public_system_www_root || !$public_system_url) {
    $form['export']['sync']['sync_update_file'] =
      array(
        '#markup' => t('please set public_system_www_root first (in <a href="/admin/config/coll/settings">/admin/config/coll/settings</a>)'),
      );
  } else {
    $form['export']['sync']['sync_update_file'] =
      array(
        '#type' => 'checkbox',
        '#title' => '更新所有數位檔',
        '#default_value' => 0,
        '#description' => '勾選本項可以確保所有公眾系統數位檔都是最新的，但會花很久時間',

      );
    $form['export']['sync']['button_sync'] = array(
          '#type' => 'submit',
          '#value' => t('同步'),
        );
  }
  return $form;
}

function _collarchive_crud_import_file_element_process($element, &$form_state, $form) {
  $element = file_managed_file_process($element, $form_state, $form);
  $element['upload_button']['#access'] = FALSE;
  return $element;
}

function _collarchive_crud_form_submit($form, &$form_state) {
  $values = $form_state['values'];
  switch($form_state['clicked_button']['#value']) {
    case $values['button_import']:
      $file = file_load($values['import_file']);
      $uploaded = file_save($file);
      if ($uploaded == TRUE) {
        $file_path = $file->uri;
        _collarchive_crud_process_import($file_path);
      } else {
        drupal_set_message(t('The file could not be uploaded. Please contact the site administrator.'), 'error');
        return;
      }
      break;
    case $values['button_export_archive']:
      _collarchive_crud_process_export_archive($values);
      break;
    case $values['button_sync']:
      $vars = _coll_ef_variables();
      $path = $vars['path'];
      $public_system_www_root = variable_get('public_system_www_root',  null);
      $options['digifolder'] = $public_system_www_root.'/sites/318_public/files/digicoll/public';
      $options['archivefolder'] = $public_system_www_root.'/sites/318_public/files/digicoll/archive';
      $options['metafolder'] = $path['export'];
      $options['withfile'] = $values['sync_update_file'];
      _collarchive_crud_process_export_public($options);
      break;
  }
  return;
}
