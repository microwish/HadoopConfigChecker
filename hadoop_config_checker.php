<?php
require_once './misc.php';

error_reporting(E_ALL);

date_default_timezone_set('Asia/Shanghai');

$def_file_map = array();
$file_conf_map = array();

$root_dir = dirname(__FILE__);
$def_dir = "$root_dir/definition";
$spec_dir = "$root_dir/specification";

if (!init_conf_map()) exit("init_conf_map failed\n");
if (!parse_conf_files()) exit("parse_conf_files failed\n");
$conclusion = check_conf_items();

$YmdHis = date('YmdHis');
$result_file = "$root_dir/conclusion.$YmdHis";
if (file_put_contents($result_file, implode("\n", $conclusion)) === FALSE) {
    echo "file_put_contents[$result_file] failed\n";
}

exit("Check done. Please review the conclusion file\n");
