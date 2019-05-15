<?php

function _collexport_admin_settings_export_field_options() {
    $fields = field_read_fields(array('entity_type' => 'node', 'bundle' => 'collection'));
    foreach($fields as $name => $field) {
      //if($name == 'field_mediainfo') continue; // never index mediainfo
  
      $label = _expsearch_get_field_label($name);
  
      $ftype = $field['type'];

      if($ftype == 'text_with_summary' || $ftype == 'text_long' || $ftype == 'text') $options[$name] = $label ;
      if($ftype == 'taxonomy_term_reference') $options['taxo_'.$name] = $label;
    }
    return $options;
  }
  

function _collexport_admin_settings_form($form, &$form_state){
    $options = _collexport_admin_settings_export_field_options();
    $export_columns = variable_get('export_columns', array_keys($options));
    $form['export_fields'] = array(
                                  '#type' => 'checkboxes',
                                  '#title' => t('Fileds for collection exporting.'),
                                  '#options' => $options,
                                  '#default_value' => $export_columns,
                                );
    
    $form['submit'] =
      array(
        '#type' => 'submit',
        '#value' => t('Save'),
      );
    return $form;
  
}


function _collexport_admin_settings_form_submit($form, &$form_state) {
    $export_columns = array();
    foreach($form_state['values']['export_fields'] as $key => $value) {
      if($value) $export_columns[] = $value;
    }
    variable_set('export_columns', $export_columns);
    $msg = 'configuration saved! to do exporting, please visit <a href="/admin/config/coll/export">/admin/config/coll/export</a>)';
    drupal_set_message($msg);
  }
  

//---------------------------------------------------------------------

function _collexport_csv_head() {

    $export_columns = variable_get('export_columns', false);

    $ret = [];
    if($export_columns) {
        $options = _collexport_admin_settings_export_field_options();
        foreach($export_columns as $f) {
            $ret[] = $options[$f];
        }
    }
    return $ret;
}

function _collexport_csv_row($node) {
    $export_columns = variable_get('export_columns', array());

    if(empty($export_columns)) {
      drupal_set_message("_collexport_csv_row(): no exporting setting found.");
      return array();
    }
  
    $taxo_pattern = '/^taxo_(.*)/';
    $row = [];

    foreach($export_columns as $column) {
        $match = preg_match($taxo_pattern, $column, $matches);
        if($match) {
          $tax = "";
          if(!empty($node->{$matches[1]}) && is_array($node->{$matches[1]}['und'])) {
            foreach($node->{$matches[1]} ['und'] as $t) {
              $term = taxonomy_term_load($t['tid']);
              if($tax == "") $tax = $term->name;
              else           $tax = $tax . ", " . $term->name;
            }
            $row[] = $tax;
          } else {
            $row[] = '';
          }
        } else {
          if(!empty($node->{$column})) {
            $row[] = $node->{$column}['und'][0]['value'];
          } else {
            $row[] = '';
          }                       
        }
    }

    return $row;    
}

function _collexport_export_form($form, &$form_state) {
    $export_columns = variable_get('export_columns', null);

    if(!$export_columns) {
      drupal_set_message('please set Fileds for exporting', 'warning');
      drupal_goto('admin/config/coll/collexport_settings');
    }
  
    $form['submit'] =
      array(
        '#type' => 'submit',
        '#value' => t('匯出'),
      );
    return $form;
}

function _collexport_export_form_submit($form, &$form_state) {

    $rows = [];

    $nids = _node_id_array();
    foreach($nids as $nid) {
      $rows[] = _collexport_csv_row(node_load($nid));
    }

    $head = _collexport_csv_head();

    $time_s = date('Y-m-d-H:m:s', time());

    wg_export_csv($head, $rows, "collections {$time_s}.csv");
}

function aaa() {

    $rows = [];

    $nids = _node_id_array();
    foreach($nids as $nid) {
      $rows[] = _collexport_csv_row(node_load($nid));
    }

    $head = _collexport_csv_head();

    $time_s = date('Y-m-d-H:m:s', time());

    wg_save_csv($head, $rows, "collections {$time_s}.csv");

}

function drush_coll_export() {

    $nodes = node_load_multiple(_node_id_array());

    $rows = [];
    foreach($nodes as $node) {
        $rows[] = _collexport_csv_row($node);
    }

    $head = _collexport_csv_head();

    wg_save_csv($head, $rows, 'aaa.csv');

}