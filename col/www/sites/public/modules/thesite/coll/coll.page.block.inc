<?php

function _coll_block_searchbox() {
  $count = db_query('SELECT count(*) from {node} where type=:type', array(':type'=>'collection'))->fetchField();
  $lase_updated = db_query('SELECT MAX(`created`) from {node} where type=:type', array(':type'=>'collection'))->fetchField();
  $ss = '<p style="text-align:center">'.$count.', 最後更新於 Last updated: '.date('Y/m/d', $lase_updated).'</p>';
  $r = '<form class="search-form" action="search">
       <input class="searchbox form-text" type="text" name="qs" id="qs"/>
       <input class="searchbox-submit" type="submit" name="su" value=">"/>
       </form>'.$ss;
  return $r;
}
