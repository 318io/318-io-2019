<?php

// Value or Default
function vod($check, $default = '')
{
  return (isset($check) && !empty($check))? $check : $default;
}

function node_value_structure($value, $type) {
    switch($type) {
        case 'text':
            $ret = !empty($value) ? Drupal7::field_set_data_text($value) : [];
            break;
        case 'longtext':
            $ret = !empty($value) ? Drupal7::field_set_data_textlong($value) : [];
            break;
        case 'term_ref':
            $ret = !empty($value) ? Drupal7::field_set_data_termref_id($value) : [];
            break;
        case 'term_refs':
            $ret = !empty($value) ? Drupal7::field_set_data_termref_ids($value) : [];
            break;
    }
    return $ret;
}

// keyword 若找不到，會自動新增
function prepare_set_import_help_keyword($keyword) {
    if(empty($keyword)) return [];
    $keywords = explode(';', $keyword);
    $tids = [];
    foreach($keywords as $keyword) {
        $vid = Drupal7::taxonomy_get_vocabulary_by_name('tag')->vid;
        $ret = Drupal7::taxonomy_get_tid_by_name($keyword, $vid);
        if(empty($ret)) $tids[] = Drupal7::simple_create_taxonomy_term($keyword, $vid);
        else            $tids[] = $ret[0];
    }
    return $tids;
}

function prepare_set_import_help_license($license) {
    $vid = Drupal7::taxonomy_get_vocabulary_by_name('license')->vid;
    $ret = Drupal7::taxonomy_get_tid_by_name($license, $vid); // return array
    if(!empty($ret)) return $ret[0];
    else             return NULL;
}

// public 為必填，若寫錯或為空時，預設是 「否」
function prepare_set_import_help_public($public) {
    $vid = Drupal7::taxonomy_get_vocabulary_by_name('boolean')->vid;
    $ret = Drupal7::taxonomy_get_tid_by_name($public, $vid); // return array
    if(empty($ret)) $ret = Drupal7::taxonomy_get_tid_by_name('否', $vid);
    return $ret[0];
}

function prepare_set_import($data) {
    $vars = [];
    $vars['title']        = $data['title'];
    $vars['description']  = node_value_structure($data['description'], 'longtext');
    $vars['release_date'] = node_value_structure(strtotime($data['release_date']), 'text');
    $vars['publisher']    = node_value_structure($data['publisher'], 'text');
    $vars['keyword']      = node_value_structure(prepare_set_import_help_keyword($data['keyword']), 'term_refs');
    $vars['license_note'] = node_value_structure($data['license_note'], 'text');
    $vars['license']      = node_value_structure(prepare_set_import_help_license($data['license']), 'term_ref');
    $vars['location']     = node_value_structure($data['location'], 'text');
    $vars['public']       = node_value_structure(prepare_set_import_help_public($data['public']), 'term_ref');
    $vars['public_note']  = node_value_structure($data['public_note'], 'text');
    return $vars;
}

// $collections must be array of collection nids, [ 123, 456, 789,.... ]
function create_set($data, $collections) {

    $_data = prepare_set_import($data);

    $vars = array(
        'title' => $_data['title'],
        'comment' => 1,
        'status'  => 1,
        'promote' => 0,
        'fields' => array(
          'field_description'   => $_data['description'],
          'field_release_date'  => $_data['release_date'],
          'field_publisher'     => $_data['publisher'],
          'field_keyword'       => $_data['keyword'],
          'field_license_note'  => $_data['license_note'],
          'field_license'       => $_data['license'],
          'field_location'      => $_data['location'],
          'field_public'        => $_data['public'],
          'field_public_note'   => $_data['public_note'],
        )
      );
    
      $node = Drupal7::node_create('set', $vars);

      //print_r($node);
    
      //node_submit($node);
      node_save($node); // get nid

      //echo $node->nid . "\n";
      $node->field_identifier = [ 'und' => [['value' => $node->nid]]];
      $node->field_collections =  [ 'und' => [['value' => drupal_json_encode($collections)]]]; 
      node_save($node);
}

function import_set($source_file, $collections = []) {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Ods();
    $spreadsheet = $reader->load($source_file);
  
    //echo $spreadsheet->getSheetCount();
  
    $worksheet = $spreadsheet->getActiveSheet();
  
    // Get the highest row and column numbers referenced in the worksheet
    $highestRow = $worksheet->getHighestRow(); // e.g. 10
    $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5
    $alphabet = range('A', 'Z');
  
    for ($row = 2; $row <= $highestRow; ++$row) {
      //if($row == 1 ) continue;
      $skip = false;
      $data = [];
      for ($col_i = 1; $col_i <= $highestColumnIndex; ++$col_i) {
        if($skip == true) {$skip = false; $data=[]; break; }
        $col = $alphabet[$col_i - 1]; // A, B , C, ... 
        $value = trim($worksheet->getCellByColumnAndRow($col_i, $row)->getValue());
        switch($col) {
            case 'A': // 標題
              $data['title'] = $value;
              break;
            case 'B': // 描述
              $data['description'] = $value;
              break;
            case 'C': // 發布日期
              $data['release_date'] = $value;                
              break;
            case 'D': // 發布者
              $data['publisher'] = $value;
              break;
            case 'E': // 關鍵字
              $data['keyword'] = $value;
              break;
            case 'F': // 姓名標示值
              $data['license_note'] = $value;
              break;
            case 'G': // 授權條款
              $data['license'] = $value;
              break;
            case 'H': // 地點
              $data['location'] = $value;
              break;
            case 'I': // 公開與否
              $data['public'] = $value;
              break;
            case 'J': // 公開與否註記
              $data['public_note'] = $value;
              break;
          }
      }
  
      if(!empty($data)) {
          try {
              //print_r($data);
              create_set($data, $collections);
          } catch(Exception $e) {
              // do nothing;
              //echo "import row {$row}, got Exception [\n";
              //print_r($data);
              //echo $e->getMessage();
              //echo "]---------------------------------\n";
              watchdog('editcol::import_set', $e->getMessage());
          }            
      } else {
          //echo "skipping row {$row}\n";
      }
    }

    // $node = new_empty_node('', 'collection', Array('repository_id' => $repository_id));  // defined in easier.drupal.php

}