<?php

// export for set

function prepare_set_csv($csvfile) {
    $fp = fopen($csvfile, 'w');
    $head = ['識別號', '標題', '描述', '發布日期', '發布者', '關鍵字', '姓名標示值', '授權條款', '地點', 'collections', '列表圖片'];
    DT::fputcsv($fp, $head);
    fclose($fp);
}


function prepare_set_exporting_row($node) {
    $identifier   = $node->nid;
    $title        = $node->title;
    $description  = !empty($node->field_description)? $node->field_description['und'][0]['value'] : "";
    $release_date = "";
    if(!empty($node->field_release_date)) {
        $release_date = date('Y/m/d', $node->field_release_date['und'][0]['value']);
    }
    $publisher = !empty($node->field_publisher)? $node->field_publisher['und'][0]['value'] : "";
    $keyword = "";
    if(!empty($node->field_keyword)) {
        foreach($node->field_keyword['und'] as $k) {
          $tid = $k['tid'];
          $term = taxonomy_term_load($tid);
          if(empty($keyword)) $keyword = $term->name;
          else                $keyword = "{$keyword};{$term->name}";
        }
    }
    $license_note = !empty($node->field_license_note) ? $node->field_license_note['und'][0]['value'] : "";
    $license = "";
    if(!empty($node->field_license)) {
        $tid = $node->field_license['und'][0]['tid'];
        $term = taxonomy_term_load($tid);
        $license = $term->name;
    }
    $location = !empty($node->field_location)? $node->field_location['und'][0]['value'] : "";
    $collections = !empty($node->field_collections)? $node->field_collections['und'][0]['value'] : "";

    $title_image = !empty($node->field_title_image)? $node->field_title_image['und'][0]['value']: "";

    return [$identifier, $title, $description, $release_date, $publisher, $keyword, $license_note, $license, $location, $collections, $title_image];
}
