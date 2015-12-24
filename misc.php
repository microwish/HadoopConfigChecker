<?php
function get_file_ext($filename)
{
    if (($p = strrpos($filename, '.')) == FALSE) return '';
    return substr($filename, $p + 1);
}

function init_conf_map()
{
    $def_file_map = &$GLOBALS['def_file_map'];
    $def_dir = &$GLOBALS['def_dir'];

    if (($def_files = scandir($def_dir)) === FALSE) {
        echo "scandir[$def_dir] failed\n";
        return FALSE;
    }

    foreach ($def_files as $def_file) {
        if ($def_file === '.' || $def_file === '..') continue;
        $p = "$def_dir/$def_file";
        $temp = file($p, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($temp === FALSE) {
            echo "file[$p] failed\n";
            return FALSE;
        }
        foreach ($temp as $line) {
            $temp2 = explode(':', $line);
            if (count($temp2) !== 2) {
                echo "invalid def lien[$lien]\n";
                return FALSE;
            }
            if (!isset($def_file_map[trim($temp2[0])])) {
                $def_file_map[trim($temp2[0])] = explode(',', trim($temp2[1]));
            }
        }
    }

    echo "def_file_map:\n";
    print_r($def_file_map);

    return TRUE;
}

function parse_conf_file($conf_path)
{
    $data = file_get_contents($conf_path);
    if ($data === FALSE || strlen($data) == 0) {
        echo "configuration file[$conf_path] is empty\n";
        return FALSE;
    }

    $parser = xml_parser_create();

    if (!xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0)) {
        echo "xml_parser_set_option failed\n";
        xml_parser_free($parser);
        return FALSE;
    }

    $result = array();
    $index = array();

    if (xml_parse_into_struct($parser, $data, $result, $index) === 0) {
        echo "xml_parse_into_struct failed\n";
        xml_parser_free($parser);
        return FALSE;
    }

    xml_parser_free($parser);

    if (count($index['name']) !== count($index['value'])) {
        echo "index parsed out is invalid\n";
        return FALSE;
    }

    $name_index = $index['name'];
    $value_index = $index['value'];
    $conf = array();

    for ($i = 0, $l = count($name_index); $i < $l; $i++) {
        $ni = $name_index[$i];
        $vi = $value_index[$i];
        $conf[trim($result[$ni]['value'])] = trim($result[$vi]['value']);
    }

    return $conf;
}

function parse_conf_files()
{
    $def_file_map = &$GLOBALS['def_file_map'];
    $file_conf_map = &$GLOBALS['file_conf_map'];

    foreach ($def_file_map as $files) {
        foreach ($files as $p) {
            if (!isset($file_conf_map[$p])) {
                $conf = parse_conf_file($p);
                if ($conf === FALSE) {
                    echo "parse_conf_files failed when parsing $p";
                    return FALSE;
                }
                $file_conf_map[$p] = $conf;
            }
        }
    }

    echo "file_conf_map:\n";
    print_r($file_conf_map);

    return TRUE;
}

function check_conf_items()
{
    $def_file_map = &$GLOBALS['def_file_map'];
    $file_conf_map = &$GLOBALS['file_conf_map'];
    $spec_dir = &$GLOBALS['spec_dir'];

    $conclusion = array();

    foreach ($def_file_map as $def => $files) {
        $spec_file = "$spec_dir/$def.specif";
        $kvs = file($spec_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($kvs === FALSE) {
            echo "file[$spec_file] failed\n";
            return FALSE;
        }
        foreach ($kvs as $kv) {
            $temp = explode('=', $kv);
            $k = trim($temp[0]);
            $v = trim($temp[1]);

            $existing = FALSE;
            $correct = FALSE;
            foreach ($files as $file) {
                $conf = $file_conf_map[$file];
                if (array_key_exists($k, $conf)) {
                    $existing = TRUE;
                    if ($conf[$k] == $v) {
                        $correct = TRUE;
                    }
                }
            }
            if (!$existing) {
                $conclusion[] = "configuration[$k] non-existing";
            } else if (!$correct) {
                $conclusion[] = "configuration[$k] misconfigured: expected[$v]";
            }
        }
    }

    return $conclusion;
}
