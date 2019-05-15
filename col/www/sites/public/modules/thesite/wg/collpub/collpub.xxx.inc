<?php
function _collarchive_drush_xxx()
{
  _timport();
  //collapi_build_datas();
  //_expsearch_build_ft();
  //_dt_switch_to_maintenance('網站維護中');
  //dnotice('aaa');
  //_dt_switch_to_online();
  //_xx_xx_list();
}

function _timport()
{
  $uri = 'public://tmp/tr.csv';
  $cnt = 0;
  if (($handle = fopen($uri, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $cnt++;
      if($cnt == 1) continue;
      $tid = $data[0];
      $n = $data[2];
      if($tid){
        $term = taxonomy_term_load($tid);
        $term->field_name_en = ['und'=>[['value'=>$n]]];
        taxonomy_term_save($term);
      }
      dnotice($tid."\t".$term->name);
      //if($cnt > 10) break;
    }
    fclose($handle);
  }
}
