<?php

define('CLAIM_DB',     '318_claim');    // remenber to modify the settings.php of this site
define('CLAIM_AUTHOR', 'claim_author');
define('CLAIM_COLL',   'claim_coll');
define('ONLINE_CLAIM_COLL', 'online_claim_coll');

function _create_table($table_name, $schema) {
  db_drop_table($table_name);
  db_create_table($table_name, $schema);
}
