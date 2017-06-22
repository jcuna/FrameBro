<?php

// This file contains general use functions.

/**
 * @param $string
 * @return mixed
 */
function siftString($string) {
    // Additional Swedish filters
    $string = str_replace(array("ä", "Ä"), "a", $string);
    $string = str_replace(array("å", "Å"), "a", $string);
    $string = str_replace(array("ö", "Ö"), "o", $string);

    // Remove any character that is not alphanumeric, white-space, or a hyphen 
    $string = preg_replace("/[^a-z0-9\s\-]/i", "", $string);
    // Replace multiple instances of white-space with a single space
    $string = preg_replace("/\s\s+/", " ", $string);
    // Replace all spaces with hyphens
    $string = preg_replace("/\s/", "-", $string);
    // Replace multiple hyphens with a single hyphen
    $string = preg_replace("/\-\-+/", "-", $string);
    // Remove leading and trailing hyphens
    $string = trim($string, "-");
    // Lowercase the string
    $string = strtolower($string);

    return $string;
}

// Takes a time in epoch timing and converts it into human time
/**
 * @param $ptime
 * @return string
 */
function humanTiming($ptime) {

    $estimate_time = time() - $ptime;

    if ($estimate_time < 1) {
        return 'Just now';
    }

    $conditions = [
        12 * 30 * 24 * 60 * 60  =>  'year',
        30 * 24 * 60 * 60       =>  'month',
        24 * 60 * 60            =>  'day',
        60 * 60                 =>  'hour',
        60                      =>  'minute',
        1                       =>  'second'
    ];

    foreach ($conditions as $secs => $str) {
        $d = $estimate_time / $secs;

        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}

/**
 * add an increasing number at the end of the filename if a file with that name already exists.
 * @param $filename
 * @param $dir
 */
function unique_file_name(&$filename, $dir) {

    $filename = preg_replace("/[^.\w]/",'_',$filename);
    $filename = preg_replace("/__+/",'_',$filename);

    preg_match("/^(.*)\.(\w{2,4})$/",$filename,$f);

    if (file_exists($dir . $filename)) {
        $num = 1;
        while (file_exists($dir . $filename)) {

            preg_match("/^(.*)\.(\w{2,4})$/", $filename, $f);
            preg_match("/(\d+)$/", $f[1], $n);

            if (isset($n[1])) {
                $x = $n[1];
                $num = $n[1] + 1;
                $num .= '.';
                $filename = preg_replace("/$x\./",$num,$filename);
            } else {
                $filename = $f[1] . $num . '.' . $f[2];
            }
        }
    }
}

/**
 * converts human sizes to machine bytes
 * @param $val
 * @return int|string
 */
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
            break;
        case 'm':
            $val *= 1024;
            break;
        case 'k':
            $val *= 1024;
    }

    return $val;
}


/**
 * @param string $lang
 * @return array
 */
function getCountries($lang = 'en') {

    $mem = new \App\Core\Cache\Cache();

    if ($list = $mem->get('countries'. $lang)) {
        return $list;
    }

    $index = ($lang === 'en') ? 1 : 0;
    $csv = array_map('str_getcsv', file(ABSOLUTE_PATH . '/app/Core/Libraries/countries.csv'));
    unset($csv[0]);

    foreach ($csv as $country) {
        $list[$country[3]] = $country[$index];
    }

    $mem->add('countries' . $lang, $list, 40320);
    return $list;
}

/**
 * @param $countryCode
 * @param string $lang
 * @return mixed
 */
function getCountryByCode($countryCode, $lang = 'eng') {

    return getCountries($lang)[$countryCode];
}