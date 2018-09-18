<?php
/**
 * This file is a part of the phpMussel package.
 * Homepage: https://phpmussel.github.io/
 *
 * PHPMUSSEL COPYRIGHT 2013 AND BEYOND BY THE PHPMUSSEL TEAM.
 *
 * Authors:
 * @see PEOPLE.md
 *
 * License: GNU/GPLv2
 * @see LICENSE.txt
 *
 * This file: Functions file (last modified: 2018.07.13).
 */

/**
 * Extends compatibility with phpMussel to PHP 5.4.x by introducing some simple
 * polyfills for functions introduced with newer versions of PHP.
 */
if (substr(PHP_VERSION, 0, 4) === '5.4.') {
    require $phpMussel['Vault'] . 'php5.4.x.php';
}

/**
 * Registers plugin closures/functions to their intended hooks.
 *
 * @param string $what The name of the closure/function to execute.
 * @param string $where Where to execute it (the designated "plugin hook").
 * @return bool Execution failed(false)/succeeded(true).
 */
$phpMussel['Register_Hook'] = function ($what, $where) use (&$phpMussel) {
    if (!isset($phpMussel['MusselPlugins']['hooks'], $phpMussel['MusselPlugins']['closures']) || !$what || !$where) {
        return false;
    }
    if (!isset($phpMussel['MusselPlugins']['hooks'][$where])) {
        $phpMussel['MusselPlugins']['hooks'][$where] = [];
    }
    $phpMussel['MusselPlugins']['hooks'][$where][] = $what;
    if (!function_exists($what) && isset($GLOBALS[$what]) && is_object($GLOBALS[$what])) {
        $phpMussel['MusselPlugins']['closures'][] = $what;
    }
    return true;
};

/**
 * Executes plugin closures/functions.
 *
 * @param string $HookID Where to execute it (the designated "plugin hook").
 * @return bool Execution failed(false)/succeeded(true).
 */
$phpMussel['Execute_Hook'] = function ($HookID) use (&$phpMussel) {
    if (!isset($phpMussel['MusselPlugins']['hooks'][$HookID])) {
        return false;
    }
    foreach ($phpMussel['MusselPlugins']['hooks'][$HookID] as $Registered) {
        if (isset($GLOBALS[$Registered]) && is_object($GLOBALS[$Registered])) {
            $GLOBALS[$Registered]();
        } elseif (function_exists($Registered)) {
            call_user_func($Registered);
        }
    }
    return true;
};

/**
 * Replaces encapsulated substrings within an input string with the value of
 * elements within an input array, whose keys correspond to the substrings.
 * Accepts two input parameters: An input array (1), and an input string (2).
 *
 * @param array $Needle The input array (the needle[/s]).
 * @param string $Haystack The input string (the haystack).
 * @return string The resultant string.
 */
$phpMussel['ParseVars'] = function ($Needle, $Haystack) {
    if (!is_array($Needle) || empty($Haystack)) {
        return '';
    }
    array_walk($Needle, function ($Value, $Key) use (&$Haystack) {
        if (!is_array($Value)) {
            $Haystack = str_replace('{' . $Key . '}', $Value, $Haystack);
        }
    });
    return $Haystack;
};

/**
 * Implodes multidimensional arrays.
 *
 * @param array $ar The array to be imploded.
 * @param string|array $j An optional "needle" or "joiner" to use for imploding
 *      the array. If a numeric array is used, an element of the array
 *      corresponding to the recursion depth will be used as the needle or
 *      joiner.
 * @param int $i Used by the function when calling itself recursively, for the
 *      purpose of tracking recursion depth (shouldn't be used outside the
 *      function).
 * @param bool $e Optional; When set to false, empty elements will be ignored.
 * @return string The imploded array.
 */
$phpMussel['implode_md'] = function ($ar, $j = '', $i = 0, $e = true) use (&$phpMussel) {
    if (!is_array($ar)) {
        return $ar;
    }
    $c = count($ar);
    if (!$c || is_array($i)) {
        return false;
    }
    if (is_array($j)) {
        if (!$x = $j[$i]) {
            return false;
        }
    } else {
        $x = $j;
    }
    $out = '';
    while ($c > 0) {
        $key = key($ar);
        if (is_array($ar[$key])) {
            $i++;
            $ar[$key] = $phpMussel['implode_md']($ar[$key], $j, $i);
            $i--;
        }
        if (!$out) {
            $out = $ar[$key];
        } elseif (!(!$e && empty($ar[$key]))) {
            $out .= $x . $ar[$key];
        }
        next($ar);
        $c--;
    }
    return $out;
};

/**
 * A function for comparing substrings with hex values.
 *
 * @param string $str The source/raw string to be compared.
 * @param int $st Optional; At what point in the source/raw string should the
 *      comparing begin? The first character of the string starts at "0".
 * @param int $l Optional; For how many bytes of the string, starting from
 *      `$st`, should the comparing occur? Defaults to the total length of the
 *      string.
 * @param string $x The hexadecimal value to compare the string against.
 * @param bool $p Optional; When set to false, the function will return true if
 *      the hexadecimal value can be matched to at least some part of the
 *      substring being compared; When set to true, the function will return
 *      true if the hexadecimal value is an exact match to the entirety of the
 *      substring being compared (optional).
 * @return bool The results of the comparison (true if matched, false if not
 *      matched).
 */
$phpMussel['substr_compare_hex'] = function ($str = '', $st = 0, $l = 0, $x = 0, $p = false) {
    if (!$l) {
        $l = strlen($str);
    }
    if (!$x || !$l) {
        return false;
    }
    for ($str = substr($str, $st, $l), $y = '', $i = 0; $i < $l; $i++) {
        $z = dechex(ord(substr($str, $i, 1)));
        $y .= (strlen($z) === 1) ? $z = '0' . $z : $z;
    }
    return !$p ? (substr_count($y, strtolower($x)) > 0) : ($y === strtolower($x));
};

/**
 * Does some simple decoding work on strings.
 *
 * @param string $str The string to be decoded.
 * @return string The decoded string.
 */
$phpMussel['prescan_decode'] = function ($str) use (&$phpMussel) {
    $nstr = html_entity_decode(urldecode(str_ireplace('&amp;#', '&#', str_ireplace('&amp;amp;', '&amp;', $str))));
    if ($nstr !== $str) {
        $nstr = $phpMussel['prescan_decode']($nstr);
    }
    return $nstr;
};

/**
 * Some simple obfuscation for potentially blocked functions; We need this to
 * avoid triggering false positives for some potentially overzealous
 * server-based security solutions that would usually flag this file as
 * malicious when they detect it containing the names of suspect functions.
 *
 * @param string $n An alias for the function that we want to call.
 * @param string $str Some data to parse to the function being called.
 * @return string The parsed data and/or decoded string (if $str is empty, the
 *      the resolved alias will be returned instead).
 */
$phpMussel['Function'] = function ($n, $str = '') {
    static $x = 'abcdefghilnorstxz12346_';
    $fList = [
        'GZ' =>
            $x[6] . $x[16] . $x[8] . $x[10] . $x[5] . $x[9] . $x[0] . $x[14] . $x[4],
        'R13' =>
            $x[13] . $x[14] . $x[12] . $x[22] . $x[12] . $x[11] . $x[14] . $x[17] . $x[19],
        'B64' =>
            $x[1] . $x[0] . $x[13] . $x[4] . $x[21] . $x[20] . $x[22] . $x[3] . $x[4] . $x[2] . $x[11] . $x[3] . $x[4],
        'HEX' =>
            $x[7] . $x[4] . $x[15] . $x[18] . $x[1] . $x[8] . $x[10]
    ];
    if (!isset($fList[$n])) {
        return '';
    }
    if (!$str || !function_exists($fList[$n])) {
        return $fList[$n];
    }
    try {
        $Return = $fList[$n]($str);
    } catch (\Exception $e) {
        $Return = '';
    }
    return $Return;
};

/**
 * Does some more complex decoding and normalisation work on strings.
 *
 * @param string $str The string to be decoded/normalised.
 * @param bool $html If true, "style" and "script" tags will be stripped from
 *      the input string (optional; defaults to false).
 * @param bool $decode If false, the input string will be normalised, but not
 *      decoded; If true, the input string will be normalised *and* decoded.
 *      Optional; Defaults to false.
 * @return string The decoded/normalised string.
 */
$phpMussel['prescan_normalise'] = function ($str, $html = false, $decode = false) use (&$phpMussel) {
    $ostr = '';
    if ($decode) {
        $ostr .= $str;
        while (true) {
            if (function_exists($phpMussel['Function']('GZ'))) {
                if ($c = preg_match_all(
                    '/(' . $phpMussel['Function']('GZ') . '\s*\(\s*["\'])(.{1,4096})(,\d)?(["\']\s*\))/i',
                $str, $matches)) {
                    for ($i = 0; $c > $i; $i++) {
                        $str = str_ireplace(
                            $matches[0][$i],
                            '"' . $phpMussel['Function']('GZ', $phpMussel['substrbl']($phpMussel['substraf']($matches[0][$i], $matches[1][$i]), $matches[4][$i])) . '"',
                            $str
                        );
                    }
                    continue;
                }
            }
            if ($c = preg_match_all(
                '/(' . $phpMussel['Function']('B64') . '|decode_base64|base64\.b64decode|atob|Base64\.decode64)(\s*' .
                '\(\s*["\'\`])([\da-z+\/]{4})*([\da-z+\/]{4}|[\da-z+\/]{3}=|[\da-z+\/]{2}==)(["\'\`]' .
                '\s*\))/i',
            $str, $matches)) {
                for ($i = 0; $c > $i; $i++) {
                    $str = str_ireplace(
                        $matches[0][$i],
                        '"' . $phpMussel['Function']('B64', $phpMussel['substrbl']($phpMussel['substraf']($matches[0][$i], $matches[1][$i] . $matches[2][$i]), $matches[5][$i])) . '"',
                        $str
                    );
                }
                continue;
            }
            if ($c = preg_match_all(
                '/(' . $phpMussel['Function']('R13') . '\s*\(\s*["\'])([^\'"\(\)]{1,4096})(["\']\s*\))/i',
            $str, $matches)) {
                for ($i = 0; $c > $i; $i++) {
                    $str = str_ireplace(
                        $matches[0][$i],
                        '"' . $phpMussel['Function']('R13', $phpMussel['substrbl']($phpMussel['substraf']($matches[0][$i], $matches[1][$i]), $matches[3][$i])) . '"',
                        $str
                    );
                }
                continue;
            }
            if ($c = preg_match_all(
                '/(' . $phpMussel['Function']('HEX') . '\s*\(\s*["\'])([\da-f]{1,4096})(["\']\s*\))/i',
            $str, $matches )) {
                for ($i = 0; $c > $i; $i++) {
                    $str = str_ireplace(
                        $matches[0][$i],
                        '"' . $phpMussel['HexSafe']($phpMussel['substrbl']($phpMussel['substraf']($matches[0][$i], $matches[1][$i]), $matches[3][$i])) . '"',
                        $str
                    );
                }
                continue;
            }
            if ($c = preg_match_all(
                '/([Uu][Nn][Pp][Aa][Cc][Kk]\s*\(\s*["\']\s*H\*\s*["\']\s*,\s*["\'])([\da-fA-F]{1,4096})(["\']\s*\))/',
            $str, $matches)) {
                for ($i = 0; $c > $i; $i++) {
                    $str = str_replace($matches[0][$i], '"' . $phpMussel['HexSafe']($phpMussel['substrbl']($phpMussel['substraf']($matches[0][$i], $matches[1][$i]), $matches[3][$i])) . '"', $str);
                }
                continue;
            }
            break;
        }
    }
    $str = preg_replace('/[^\x21-\x7e]/', '', strtolower($phpMussel['prescan_decode']($str . $ostr)));
    unset($ostr);
    if ($html) {
        $str = preg_replace([
            '@<script[^>]*?>.*?</script>@si',
            '@<[\/\!]*?[^<>]*?>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        ], '', $str);
    }
    return trim($str);
};

/**
 * Gets substring from haystack prior to the first occurrence of needle.
 *
 * @param string $h The haystack.
 * @param string $n The needle.
 * @return string The substring.
 */
$phpMussel['substrbf'] = function ($h, $n) {
    return !$n ? '' : substr($h, 0, strpos($h, $n));
};

/**
 * Gets substring from haystack after the first occurrence of needle.
 *
 * @param string $h The haystack.
 * @param string $n The needle.
 * @return string The substring.
 */
$phpMussel['substraf'] = function ($h, $n) {
    return !$n ? '' : substr($h, strpos($h, $n) + strlen($n));
};

/**
 * Gets substring from haystack prior to the last occurrence of needle.
 *
 * @param string $h The haystack.
 * @param string $n The needle.
 * @return string The substring.
 */
$phpMussel['substrbl'] = function ($h, $n) {
    return !$n ? '' : substr($h, 0, strrpos($h, $n));
};

/**
 * Gets substring from haystack after the last occurrence of needle.
 *
 * @param string $h The haystack.
 * @param string $n The needle.
 * @return string The substring.
 */
$phpMussel['substral'] = function ($h, $n) {
    return !$n ? '' : substr($h, strrpos($h, $n) + strlen($n));
};

/**
 * This function reads files and returns the contents of those files.
 *
 * @param string $File Path and filename of the file to read.
 * @param int $s Number of blocks to read from the file (optional; can be
 *      manually specified, but it's best to just ignore it and let the
 *      function work it out for itself).
 * @param bool $PreChecked When false, checks that the file exists and is
 *      writable. Defaults to false.
 * @param int $Blocks The total size of a single block in kilobytes (optional;
 *      defaults to 128, i.e., 128KB or 131072 bytes). This can be modified by
 *      developers as per their individual needs. Generally, a smaller value
 *      will increase stability but decrease performance, whereas a larger
 *      value will increase performance but decrease stability.
 * @return string|bool Content of the file returned by the function (or false
 *      on failure).
 */
$phpMussel['ReadFile'] = function ($File, $Size = 0, $PreChecked = false, $Blocks = 128) {
    if (!$PreChecked && (!is_file($File) || !is_readable($File))) {
        return false;
    }
    $Blocksize = $Blocks * 1024;
    $Filesize = filesize($File);
    if (!$Size) {
        $Size = ($Filesize && $Blocksize) ? ceil($Filesize / $Blocksize) : 0;
    }
    $Data = '';
    if ($Size > 0) {
        $Handle = fopen($File, 'rb');
        $r = 0;
        while ($r < $Size) {
            $Data .= fread($Handle, $Blocksize);
            $r++;
        }
        fclose($Handle);
    }
    return $Data ?: false;
};

/**
 * A very simple wrapper for file() that checks for the existence of files
 * before attempting to read them, in order to avoid warnings about
 * non-existent files.
 *
 * @param string $Filename Refer to the description for file().
 * @param int $Flags Refer to the description for file().
 * @param array $Context Refer to the description for file().
 * @return array|bool Same as with file(), but won't trigger warnings.
 */
$phpMussel['ReadFileAsArray'] = function ($Filename, $Flags = 0, $Context = false) {
    if (!is_readable($Filename)) {
        return false;
    }
    if (!$Context) {
        return !$Flags ? file($Filename) : file($Filename, $Flags);
    }
    return file($Filename, $Flags, $Context);
};

/**
 * Deletes expired cache entries and regenerates cache files.
 *
 * @param string $Delete Forcibly delete a specific cache entry (optional).
 * @return bool Operation succeeded (true) or failed (false).
 */
$phpMussel['CleanCache'] = function ($Delete = '') use (&$phpMussel) {
    if (!empty($phpMussel['memCache']['CacheCleaned'])) {
        return true;
    }
    $phpMussel['memCache']['CacheCleaned'] = true;
    $CacheFiles = [];
    $FileIndex = $phpMussel['cachePath'] . 'index.dat';
    if (!is_readable($FileIndex)) {
        return false;
    }
    $FileDataOld = $FileData = $phpMussel['ReadFile']($FileIndex);
    if (strpos($FileData, ';') !== false) {
        $FileData = explode(';', $FileData);
        foreach ($FileData as &$ThisData) {
            if (strpos($ThisData, ':') === false) {
                $ThisData = '';
                continue;
            }
            $ThisData = explode(':', $ThisData, 3);
            if (($Delete && $Delete === $ThisData[0]) || ($ThisData[1] > 0 && $phpMussel['Time'] > $ThisData[1])) {
                $FileKey = bin2hex(substr($ThisData[0], 0, 1));
                if (!isset($CacheFiles[$FileKey])) {
                    $CacheFiles[$FileKey] = !is_readable(
                        $phpMussel['cachePath'] . $FileKey . '.tmp'
                    ) ? '' : $phpMussel['ReadFile']($phpMussel['cachePath'] . $FileKey . '.tmp', 0, true);
                }
                while (strpos($CacheFiles[$FileKey], $ThisData[0] . ':') !== false) {
                    $CacheFiles[$FileKey] = str_ireplace($ThisData[0] . ':' . $phpMussel['substrbf'](
                        $phpMussel['substraf']($CacheFiles[$FileKey], $ThisData[0] . ':'), ';'
                    ) . ';', '', $CacheFiles[$FileKey]);
                }
                $ThisData = '';
                continue;
            }
            $ThisData = $ThisData[0] . ':' . $ThisData[1];
        }
        $FileData = str_replace(';;', ';', implode(';', array_filter($FileData)) . ';');
        if ($FileDataOld !== $FileData) {
            $Handle = fopen($FileIndex, 'w');
            fwrite($Handle, $FileData);
            fclose($Handle);
        }
    }
    foreach ($CacheFiles as $CacheEntryKey => $CacheEntryValue) {
        if (strlen($CacheEntryValue) < 2) {
            if (file_exists($phpMussel['cachePath'] . $CacheEntryKey . '.tmp')) {
                unlink($phpMussel['cachePath'] . $CacheEntryKey . '.tmp');
            }
            continue;
        }
        $Handle = fopen($phpMussel['cachePath'] . $CacheEntryKey . '.tmp', 'w');
        fwrite($Handle, $CacheEntryValue);
        fclose($Handle);
    }
    return true;
};

/**
 * Retrieves cache entries.
 *
 * @param string|array $Entry The name of the cache entry/entries to retrieve;
 *      Can be a string to specify a single entry, or an array of strings to
 *      specify multiple entries.
 * @return string|array Contents of the cache entry/entries.
 */
$phpMussel['FetchCache'] = function ($Entry = '') use (&$phpMussel) {
    $phpMussel['CleanCache']();
    if (!$Entry) {
        return '';
    }
    if (is_array($Entry)) {
        $Out = [];
        array_walk($Entry, function ($Value, $Key) use (&$phpMussel, &$Out) {
            $Out[$Key] = $phpMussel['FetchCache']($Value);
        });
        return $Out;
    }
    $File = $phpMussel['cachePath'] . bin2hex(substr($Entry, 0, 1)) . '.tmp';
    if (!is_readable($File) || !$FileData = $phpMussel['ReadFile']($File, 0, true)) {
        return '';
    }
    if (!$Item = strpos($FileData, $Entry . ':') !== false ? $Entry . ':' . $phpMussel['substrbf'](
        $phpMussel['substraf']($FileData, $Entry . ':'), ';'
    ) . ';' : '') {
        return '';
    }
    $Expiry = $phpMussel['substrbf']($phpMussel['substraf']($Item, $Entry . ':'), ':');
    if ($Expiry > 0 && $phpMussel['Time'] > $Expiry) {
        while (substr_count($FileData, $Entry . ':')) {
            $FileData = str_ireplace($Item, '', $FileData);
        }
        $Handle = fopen($File, 'w');
        fwrite($Handle, $FileData);
        fclose($Handle);
        return '';
    }
    if (!$ItemData = $phpMussel['substrbf']($phpMussel['substraf']($Item, $Entry . ':' . $Expiry . ':'), ';')) {
        return '';
    }
    return $phpMussel['Function']('GZ', $phpMussel['HexSafe']($ItemData)) ?: '';
};

/**
 * Creates cache entry and saves it to the cache.
 *
 * @param string $Entry Name of the cache entry to create.
 * @param int $Expiry Unix time until the cache entry expires.
 * @param string $ItemData Contents of the cache entry.
 * @return bool This should always return true, unless something goes wrong.
 */
$phpMussel['SaveCache'] = function ($Entry = '', $Expiry = 0, $ItemData = '') use (&$phpMussel) {
    $phpMussel['CleanCache']();
    if (!$Entry || !$ItemData) {
        return false;
    }
    if (!$Expiry) {
        $Expiry = $phpMussel['Time'];
    }
    $File = $phpMussel['cachePath'] . bin2hex($Entry[0]) . '.tmp';
    $Data = $phpMussel['ReadFile']($File) ?: '';
    while (substr_count($Data, $Entry . ':')) {
        $Data = str_ireplace($Entry . ':' . $phpMussel['substrbf']($phpMussel['substraf']($Data, $Entry . ':'), ';') . ';', '', $Data);
    }
    $Data .= $Entry . ':' . $Expiry . ':' . bin2hex(gzdeflate($ItemData,9)) . ';';
    $Handle = fopen($File, 'w');
    fwrite($Handle, $Data);
    fclose($Handle);
    $IndexFile = $phpMussel['cachePath'] . 'index.dat';
    $IndexNewData = $IndexData = $phpMussel['ReadFile']($IndexFile) ?: '';
    while (substr_count($IndexNewData, $Entry . ':')) {
        $IndexNewData = str_ireplace($Entry . ':' . $phpMussel['substrbf']($phpMussel['substraf']($IndexNewData, $Entry . ':'), ';') . ';', '', $IndexNewData);
    }
    $IndexNewData .= $Entry . ':' . $Expiry . ';';
    if ($IndexNewData !== $IndexData) {
        $IndexHandle = fopen($IndexFile, 'w');
        fwrite($IndexHandle, $IndexNewData);
        fclose($IndexHandle);
    }
    return true;
};

/** Reads and prepares cached hash data. */
$phpMussel['PrepareHashCache'] = function () use (&$phpMussel) {
    if ($phpMussel['HashCache']['Data'] = (
        $phpMussel['Config']['general']['scan_cache_expiry'] > 0
    ) ? $phpMussel['FetchCache']('HashCache') : '') {
        $phpMussel['HashCache']['Data'] = explode(';', $phpMussel['HashCache']['Data']);
        $Build = [];
        foreach ($phpMussel['HashCache']['Data'] as $CacheItem) {
            if (strpos($CacheItem, ':') !== false) {
                $CacheItem = explode(':', $CacheItem, 4);
                if ($CacheItem[1] > $phpMussel['Time']) {
                    $Build[$CacheItem[0]] = $CacheItem;
                }
            }
        }
        $phpMussel['HashCache']['Data'] = $Build;
    }
};

/**
 * Quarantines file uploads by using a key generated from your quarantine key
 * to bitshift the input string (the file uploads), appending a header with an
 * explanation of what the bitshifted data is, along with an MD5 hash checksum
 * of its non-quarantined counterpart, and then saves it all to a QFU file,
 * storing these QFU files in your quarantine directory.
 *
 * This isn't hardcore encryption, but it should be sufficient to prevent
 * accidental execution of quarantined files and to allow safe handling of
 * those files, which is the whole point of quarantining them in the first
 * place. Improvements might be made in the future.
 *
 * @param string $In The input string (the file upload / source data).
 * @param string $Key Your quarantine key.
 * @param string $IP Data origin (usually, the IP address of the uploader).
 * @param string $ID The QFU filename to use (calculated beforehand).
 * @return bool This should always return true, unless something goes wrong.
 */
$phpMussel['Quarantine'] = function ($In, $Key, $IP, $ID) use (&$phpMussel) {
    if (!$In || !$Key || !$IP || !$ID || !function_exists('gzdeflate') || (
        strlen($Key) < 128 &&
        !$Key = $phpMussel['HexSafe'](hash('sha512', $Key) . hash('whirlpool', $Key))
    )) {
        return false;
    }
    if ($phpMussel['Config']['legal']['pseudonymise_ip_addresses']) {
        $IP = $phpMussel['Pseudonymise-IP']($IP);
    }
    $k = strlen($Key);
    $FileSize = strlen($In);
    $Head = "\xa1phpMussel\x21" . $phpMussel['HexSafe'](md5($In)) . pack('l*', $FileSize) . "\x01";
    $In = gzdeflate($In, 9);
    $Out = '';
    $i = 0;
    while ($i < $FileSize) {
        for ($j = 0; $j < $k; $j++, $i++) {
            if (strlen($Out) >= $FileSize) {
                break 2;
            }
            $L = substr($In, $i, 1);
            $R = substr($Key, $j, 1);
            $Out .= ($L === false ? "\x00" : $L) ^ ($R === false ? "\x00" : $R);
        }
    }
    $Out =
        "\x2f\x3d\x3d\x20phpMussel\x20Quarantined\x20File\x20Upload\x20\x3d" .
        "\x3d\x5c\n\x7c\x20Time\x2fDate\x20Uploaded\x3a\x20" .
        str_pad($phpMussel['Time'], 18, ' ') .
        "\x7c\n\x7c\x20Uploaded\x20From\x3a\x20" . str_pad($IP, 22, ' ') .
        "\x20\x7c\n\x5c" . str_repeat("\x3d", 39) . "\x2f\n\n\n" . $Head . $Out;
    $UsedMemory = $phpMussel['MemoryUse']($phpMussel['qfuPath']);
    $UsedMemory['Size'] += strlen($Out);
    $UsedMemory['Count']++;
    if ($DeductBytes = $phpMussel['ReadBytes']($phpMussel['Config']['general']['quarantine_max_usage'])) {
        $DeductBytes = $UsedMemory['Size'] - $DeductBytes;
        $DeductBytes = ($DeductBytes > 0) ? $DeductBytes : 0;
    }
    if ($DeductFiles = $phpMussel['Config']['general']['quarantine_max_files']) {
        $DeductFiles = $UsedMemory['Count'] - $DeductFiles;
        $DeductFiles = ($DeductFiles > 0) ? $DeductFiles : 0;
    }
    if ($DeductBytes > 0 || $DeductFiles > 0) {
        $UsedMemory = $phpMussel['MemoryUse']($phpMussel['qfuPath'], $DeductBytes, $DeductFiles);
    }
    $Handle = fopen($phpMussel['qfuPath'] . $ID . '.qfu', 'a');
    fwrite($Handle, $Out);
    fclose($Handle);
    if (!$phpMussel['EOF']) {
        $phpMussel['Stats-Increment']('Web-Quarantined', 1);
    }
    return true;
};

/**
 * Calculates the total memory used by a directory, and optionally enforces
 * memory usage and number of files limits on that directory. Should be
 * regarded as part of the phpMussel quarantine functionality.
 *
 * @param string $Path The path of the directory to be checked.
 * @param int $Delete How many bytes to delete from the target directory; Omit
 *      or set to 0 to avoid deleting files on the basis of total bytes.
 * @param int $DeleteFiles How many files to delete from the target directory;
        Omit or set to 0 to avoid deleting files.
 * @return array Contains two integer elements: `Size`: The actual, total
 *      memory used by the target directory. `Count`: The total number of files
 *      found in the target directory by the time of closure exit.
 */
$phpMussel['MemoryUse'] = function ($Path, $Delete = 0, $DeleteFiles = 0) {
    $Offset = strlen($Path);
    $Files = [];
    $List = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Path), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($List as $Item => $List) {
        $File = str_replace("\\", '/', substr($Item, $Offset));
        if ($File && preg_match('~\.qfu$~i', $Item) && is_file($Item) && !is_link($Item) && is_readable($Item)) {
            $Files[$File] = filemtime($Item);
        }
    }
    unset($Item, $List, $Offset);
    $Arr = ['Size' => 0, 'Count' => 0];
    asort($Files, SORT_NUMERIC);
    foreach ($Files as $File => $Modified) {
        $File = $Path . $File;
        $Size = filesize($File);
        if (($Delete > 0 || $DeleteFiles > 0) && unlink($File)) {
            $DeleteFiles--;
            $Delete -= $Size;
            continue;
        }
        $Arr['Size'] += $Size;
        $Arr['Count']++;
    }
    return $Arr;
};

/**
 * Checks if $Needle (string) matches (is equal or identical to) $Haystack
 * (string), or a specific substring of $Haystack, to within a specific
 * threshold of the levenshtein distance between the $Needle and the $Haystack
 * or the $Haystack substring specified.
 *
 * This function is useful for expressing the differences between two strings
 * as an integer value and for then determining whether a specific value as per
 * those differences is met.
 *
 * @param string $Needle The needle (will be matched against the $Haystack, or,
 *      if substring positions are specified, against the $Haystack substring
 *      specified).
 * @param string $Haystack The haystack (will be matched against the $Needle).
 *      Note that for the purposes of calculating the levenshtein distance, it
 *      doesn't matter which string is a $Needle and which is a $Haystack (the
 *      value should be the same if the two were reversed). However, when
 *      specifying substring positions, those substring positions are applied
 *      to the $Haystack, and not the $Needle. Note, too, that if the $Needle
 *      length is greater than the $Haystack length (after having applied the
 *      substring positions to the $Haystack), $Needle and $Haystack will be
 *      switched.
 * @param int $pos_A The initial position of the $Haystack to use for the
 *      substring, if using a substring (optional; defaults to `0`; `0` is the
 *      beginning of the $Haystack).
 * @param int $pos_Z The final position of the $Haystack to use for the
 *      substring, if using a substring (optional; defaults to `0`; `0` will
 *      instruct the function to continue to the end of the $Haystack, and
 *      thus, if both $pos_A and $pos_Z are `0`, the entire $Haystack will be
 *      used).
 * @param int $min The threshold minimum (the minimum levenshtein distance
 *      required in order for the two strings to be considered a match).
 *      Optional; Defaults to `0`. If `0` or less is specified, there is no
 *      minimum, and so, any and all strings should always match, as long as
 *      the levenshtein distance doesn't surpass the threshold maximum.
 * @param int $max The threshold maximum (the maximum levenshtein distance
 *      allowed for the two strings to be considered a match). Optional;
 *      Defaults to `-1`. If exactly `-1` is specified, there is no maximum,
 *      and so, any and all strings should always match, as long as the
 *      threshold minimum is met.
 * @param bool $bool Specifies to the function whether to return the
 *      levenshtein distance of the two strings (as an integer) or to return
 *      the results of the match (as a boolean; true for match success, false
 *      for match failure). Optional; Defaults to true. If true is specified,
 *      the function will return a boolean value (the results of the match),
 *      and if false is specified, the levenshtein distance will be returned.
 * @param bool $case Specifies to the function whether to treat the two strings
 *      as case-sensitive (when true is specified) or case-insensitive (when
 *      false is specified) when calculating the levenshtein distance.
 *      Optional; Defaults to false.
 * @param int $cost_ins The cost to apply for character/byte insertions for
 *      when calculating the levenshtein distance. Optional; Defaults to 1.
 * @param int $cost_rep The cost to apply for character/byte replacements for
 *      when calculating the levenshtein distance. Optional; Defaults to 1.
 * @param int $cost_del The cost to apply for character/byte deletions for when
 *      calculating the levenshtein distance. Optional; Defaults to 1.
 * @return bool|int The function will return either a boolean or an integer,
 *      depending on the state of $bool (but will also return false whenever an
 *      error occurs).
 */
$phpMussel['lv_match'] = function ($Needle, $Haystack, $pos_A = 0, $pos_Z = 0, $min = 0, $max = -1, $bool = true, $case = false, $cost_ins = 1, $cost_rep = 1, $cost_del = 1) {
    if (!function_exists('levenshtein') || is_array($Needle) || is_array($Haystack)) {
        return false;
    }
    $nlen = strlen($Needle);
    $pos_A = (int)$pos_A;
    $pos_Z = (int)$pos_Z;
    $min = (int)$min;
    $max = (int)$max;
    if ($pos_A !== 0 || $pos_Z !== 0) {
        $Haystack = (
            $pos_Z === 0
        ) ? substr($Haystack, $pos_A) : substr($Haystack, $pos_A, $pos_Z);
    }
    $hlen = strlen($Haystack);
    if ($nlen < 1 || $hlen < 1) {
        return $bool ? false : 0;
    }
    if ($nlen > $hlen) {
        $x = [$Needle, $nlen, $Haystack, $hlen];
        $Haystack = $x[0];
        $hlen = $x[1];
        $Needle = $x[2];
        $nlen = $x[3];
    }
    if ($cost_ins === 1 && $cost_rep === 1 && $cost_del === 1) {
        $lv = $case ? levenshtein(
            $Haystack, $Needle
        ) : levenshtein(
            strtolower($Haystack), strtolower($Needle)
        );
    } else {
        $lv = $case ? levenshtein(
            $Haystack, $Needle, $cost_ins, $cost_rep, $cost_del
        ) : levenshtein(
            strtolower($Haystack), strtolower($Needle), $cost_ins, $cost_rep, $cost_del
        );
    }
    return $bool ? (($min === 0 || $lv >= $min) && ($max === -1 || $lv <= $max)) : $lv;
};

/**
 * Returns the high and low nibbles corresponding to the first byte of the
 * input string.
 *
 * @param string $Input The input string.
 * @return array Contains two elements, both standard decimal integers; The
 *      first is the high nibble of the input string, and the second is the low
 *      nibble of the input string.
 */
$phpMussel['split_nibble'] = function ($Input) {
    $Input = bin2hex($Input);
    return [hexdec(substr($Input, 0, 1)), hexdec(substr($Input, 1, 1))];
};

/**
 * Returns a string representing the binary bits of its input, whereby each
 * byte of the output is either one or zero.
 * Output can be reversed with `$phpMussel['implode_bits']()`.
 *
 * @param string $Input The input string (see closure description above).
 * @return string The output string (see closure description above).
 */
$phpMussel['explode_bits'] = function ($Input) {
    $Out = '';
    $Len = strlen($Input);
    for ($Byte = 0; $Byte < $Len; $Byte++) {
        $Out .= str_pad(decbin(ord($Input[$Byte])), 8, '0', STR_PAD_LEFT);
    }
    return $Out;
};

/**
 * The reverse of `$phpMussel['explode_bits']()`.
 *
 * @param string $Input The input string (see closure description above).
 * @return string The output string (see closure description above).
 */
$phpMussel['implode_bits'] = function ($Input) {
    $Chunks = str_split($Input, 8);
    $Count = count($Chunks);
    for ($Out = '', $Chunk = 0; $Chunk < $Count; $Chunk++) {
        $Out .= chr(bindec($Chunks[$Chunk]));
    }
    return $Out;
};

/**
 * Expands phpMussel detection shorthand to complete identifiers, makes some
 * determinations based on those identifiers against the package
 * configuration (e.g., whether specific signatures should be weighted or
 * ignored based on those identifiers), and returns a complete signature name
 * containing all relevant identifiers.
 *
 * Originally, this function was created to allow phpMussel to partially
 * compress its signatures without jeopardising speed, performance or
 * efficiency, because by allowing phpMussel to partially compress its
 * signatures, the total signature file footprint could be reduced, thus
 * allowing the inclusion of a greater number of signatures without causing
 * excessive footprint bloat. Its purpose has expanded since then though.
 *
 * @param string $VN The signature name WITH identifiers compressed (i.e.,
 *      the shorthand version of the signature name).
 * @return string The signature name WITHOUT identifiers compressed (i.e., the
 *      identifiers have been decompressed/expanded), or the input verbatim.
 */
$phpMussel['vn_shorthand'] = function ($VN) use (&$phpMussel) {

    /** Determine whether the signature is weighted. */
    $phpMussel['memCache']['weighted'] = false;

    /** Determine whether the signature should be ignored due to package configuration. */
    $phpMussel['memCache']['ignoreme'] = false;

    /** Byte 0 confirms whether the signature name uses shorthand. */
    if ($VN[0] !== "\x1a") {
        return $VN;
    }

    /** Check whether shorthand data has been fetched. If it hasn't, fetch it. */
    if (!isset($phpMussel['shorthand.yaml'])) {
        if (!file_exists($phpMussel['Vault'] . 'shorthand.yaml') || !is_readable($phpMussel['Vault'] . 'shorthand.yaml')) {
            return $VN;
        }
        $phpMussel['shorthand.yaml'] = [];
        $phpMussel['YAML']($phpMussel['ReadFile']($phpMussel['Vault'] . 'shorthand.yaml'), $phpMussel['shorthand.yaml']);
    }

    /** Will be populated by the signature name. */
    $Out = '';

    /** Byte 1 contains vendor name and signature metadata information. */
    $Nibbles = $phpMussel['split_nibble']($VN[1]);

    /** Populate vendor name. */
    if (
        !empty($phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]]) &&
        is_array($phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]]) &&
        !empty($phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]][$Nibbles[1]]) &&
        is_string($phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]][$Nibbles[1]])
    ) {
        $SkipMeta = true;
        $Out .= $phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]][$Nibbles[1]] . '-';
    } elseif (
        !empty($phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]]) &&
        is_string($phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]])
    ) {
        $Out .= $phpMussel['shorthand.yaml']['Vendor Shorthand'][$Nibbles[0]] . '-';
    }

    /** Populate weight options. */
    if ((
        !empty($phpMussel['shorthand.yaml']['Vendor Weight Options'][$Nibbles[0]][$Nibbles[1]]) &&
        $phpMussel['shorthand.yaml']['Vendor Weight Options'][$Nibbles[0]][$Nibbles[1]] === 'Weighted'
    ) || (
        !empty($phpMussel['shorthand.yaml']['Vendor Weight Options'][$Nibbles[0]]) &&
        $phpMussel['shorthand.yaml']['Vendor Weight Options'][$Nibbles[0]] === 'Weighted'
    )) {
        $phpMussel['memCache']['weighted'] = true;
    }

    /** Populate signature metadata information. */
    if (empty($SkipMeta) && !empty($phpMussel['shorthand.yaml']['Metadata Shorthand'][$Nibbles[1]])) {
        $Out .= $phpMussel['shorthand.yaml']['Metadata Shorthand'][$Nibbles[1]] . '.';
    }

    /** Byte 2 contains vector information. */
    $Nibbles = $phpMussel['split_nibble']($VN[2]);

    /** Populate vector information. */
    if (!empty($phpMussel['shorthand.yaml']['Vector Shorthand'][$Nibbles[0]][$Nibbles[1]])) {
        $Out .= $phpMussel['shorthand.yaml']['Vector Shorthand'][$Nibbles[0]][$Nibbles[1]] . '.';
    }

    /** Byte 3 contains malware type information. */
    $Nibbles = $phpMussel['split_nibble']($VN[3]);

    /** Populate malware type information. */
    if (!empty($phpMussel['shorthand.yaml']['Malware Type Shorthand'][$Nibbles[0]][$Nibbles[1]])) {
        $Out .= $phpMussel['shorthand.yaml']['Malware Type Shorthand'][$Nibbles[0]][$Nibbles[1]] . '.';
    }

    /** Populate ignore options. */
    if (!empty($phpMussel['shorthand.yaml']['Malware Type Ignore Options'][$Nibbles[0]][$Nibbles[1]])) {
        $IgnoreOption = $phpMussel['shorthand.yaml']['Malware Type Ignore Options'][$Nibbles[0]][$Nibbles[1]];
        if (isset($phpMussel['Config']['signatures'][$IgnoreOption]) && !$phpMussel['Config']['signatures'][$IgnoreOption]) {
            $phpMussel['memCache']['ignoreme'] = true;
        }
    }

    /** Return the signature name and exit the closure. */
    return $Out . substr($VN, 4);

};

/**
 * Used for performing lookups to the Google Safe Browsing API (v4).
 * @link https://developers.google.com/safe-browsing/v4/lookup-api
 *
 * @param array $urls An array of the URLs to lookup.
 * @param array $URLsNoLookup An optional array of URLs to NOT lookup.
 * @param array $DomainsNoLookup An optional array of domains to NOT lookup.
 * @return int The results of the lookup. 200 if AT LEAST ONE of the queried
 *      URLs are listed on any of Google Safe Browsing lists; 204 if NONE of
 *      the queried URLs are listed on any of Google Safe Browsing lists; 400
 *      if the request is malformed; 401 if the API key is missing or isn't
 *      authorised; 503 if the service is unavailable (e.g., if it's been
 *      throttled); 999 if something unexpected occurs (such as, for example,
 *      if a programmatic error is encountered).
 */
$phpMussel['SafeBrowseLookup'] = function ($urls, $URLsNoLookup = [], $DomainsNoLookup = []) use (&$phpMussel) {
    if (empty($phpMussel['Config']['urlscanner']['google_api_key'])) {
        return 401;
    }
    /** Count and prepare the URLs. */
    if (!$c = count($urls)) {
        return 400;
    }
    for ($i = 0; $i < $c; $i++) {
        $Domain = (strpos($urls[$i], '/') !== false) ? $phpMussel['substrbf']($urls[$i], '/') : $urls[$i];
        if (!empty($URLsNoLookup[$urls[$i]]) || !empty($DomainsNoLookup[$Domain])) {
            unset($urls[$i]);
            continue;
        }
        $urls[$i] = ['url' => $urls[$i]];
    }
    sort($urls);
    /** After we've prepared the URLs, we prepare our JSON array. */
    $arr = json_encode([
        'client' => [
            'clientId' => 'phpMussel',
            'clientVersion' => $phpMussel['ScriptVersion']
        ],
        'threatInfo' => [
            'threatTypes' => [
                'THREAT_TYPE_UNSPECIFIED',
                'MALWARE',
                'SOCIAL_ENGINEERING',
                'UNWANTED_SOFTWARE',
                'POTENTIALLY_HARMFUL_APPLICATION'
            ],
            'platformTypes' => ['ANY_PLATFORM'],
            'threatEntryTypes' => ['URL'],
            'threatEntries' => $urls
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    /** Fetch the cache entry for Google Safe Browsing, if it doesn't already exist. */
    if (!isset($phpMussel['memCache']['urlscanner_google'])) {
        $phpMussel['memCache']['urlscanner_google'] = $phpMussel['FetchCache']('urlscanner_google');
    }
    /** Generate new cache expiry time. */
    $newExpiry = $phpMussel['Time'] + $phpMussel['Config']['urlscanner']['cache_time'];
    /** Generate a reference for the cache entry for this lookup. */
    $cacheRef = md5($arr) . ':' . $c . ':' . strlen($arr) . ':';
    /** This will contain the lookup response. */
    $Response = '';
    /** Check if this lookup has already been performed. */
    while (strpos($phpMussel['memCache']['urlscanner_google'], $cacheRef) !== false) {
        $Response = $phpMussel['substrbf']($phpMussel['substral']($phpMussel['memCache']['urlscanner_google'], $cacheRef), ';');
        /** Safety mechanism. */
        if (!$Response || strpos($phpMussel['memCache']['urlscanner_google'], $cacheRef . $Response . ';') === false) {
            $Response = '';
            break;
        }
        $expiry = $phpMussel['substrbf']($Response, ':');
        if ($expiry > $phpMussel['Time']) {
            $Response = $phpMussel['substraf']($Response, ':');
            break;
        }
        $phpMussel['memCache']['urlscanner_google'] =
            str_ireplace($cacheRef . $Response . ';', '', $phpMussel['memCache']['urlscanner_google']);
        $Response = '';
    }
    /** If this lookup has already been performed, return the results without repeating it. */
    if ($Response) {
        /** Update the cache entry for Google Safe Browsing. */
        $newExpiry = $phpMussel['SaveCache']('urlscanner_google', $newExpiry, $phpMussel['memCache']['urlscanner_google']);
        if ($Response === '200') {
            /** Potentially harmful URL detected. */
            return 200;
        } elseif ($Response === '204') {
            /** Potentially harmful URL *NOT* detected. */
            return 204;
        } elseif ($Response === '400') {
            /** Bad/malformed request. */
            return 400;
        } elseif ($Response === '401') {
            /** Unauthorised (possibly a bad API key). */
            return 401;
        } elseif ($Response === '503') {
            /** Service unavailable. */
            return 503;
        }
        /** Something bad/unexpected happened. */
        return 999;
    }

    /** Prepare the URL to use with cURL. */
    $uri =
        'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=' .
        $phpMussel['Config']['urlscanner']['google_api_key'];

    /** cURL stuff here. */
    $Request = curl_init($uri);
    curl_setopt($Request, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($Request, CURLOPT_HEADER, false);
    curl_setopt($Request, CURLOPT_POST, true);
    /** Ensure it knows we're sending JSON data. */
    curl_setopt($Request, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
    /** The Google Safe Browsing API requires HTTPS+SSL (there's no way around this). */
    curl_setopt($Request, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    curl_setopt($Request, CURLOPT_RETURNTRANSFER, true);
    /*
     * Setting "CURLOPT_SSL_VERIFYPEER" to false can be somewhat risky due to man-in-the-middle attacks, but lookups
     * seemed to always fail when it was set to true during testing, so, for the sake of this actually working at all,
     * I'm setting it as false, but we should try to fix this in the future at some point.
     */
    curl_setopt($Request, CURLOPT_SSL_VERIFYPEER, false);
    /* We don't want to leave the client waiting for *too* long. */
    curl_setopt($Request, CURLOPT_TIMEOUT, $phpMussel['Timeout']);
    curl_setopt($Request, CURLOPT_USERAGENT, $phpMussel['ScriptUA']);
    curl_setopt($Request, CURLOPT_POSTFIELDS, $arr);

    /** Execute and get the response. */
    $Response = curl_exec($Request);
    $phpMussel['LookupCount']++;

    /** Check for errors and print to the screen if there were any. */
    if (!$Response) {
        throw new \Exception(curl_error($Request));
    }

    /** Close the cURL session. */
    curl_close($Request);

    if (substr_count($Response, '"matches":')) {
        /** Potentially harmful URL detected. */
        $returnVal = 200;
    } else {
        /** Potentially harmful URL *NOT* detected. */
        $returnVal = 204;
    }

    /** Update the cache entry for Google Safe Browsing. */
    $phpMussel['memCache']['urlscanner_google'] .= $cacheRef . ':' . $newExpiry . ':' . $returnVal . ';';
    $newExpiry = $phpMussel['SaveCache']('urlscanner_google', $newExpiry, $phpMussel['memCache']['urlscanner_google']);

    return $returnVal;
};

/**
 * Constructs a list of files contained within a pharable file (in this
 * context, a pharable file is defined as a file of any the following formats:
 * TAR, ZIP, PHAR) and returns that list as a string, entries delimited by a
 * linefeed (\x0A) and preceeded by an integer representing the depth of the
 * entry (in relation to where it exists within the tree of the pharable file).
 *
 * @param string $PharFile The pharable file to analyse.
 * @param int $PharDepth An offset for the depth of entries.
 * @return string The constructed list (as per described above).
 */
$phpMussel['BuildPharList'] = function ($PharFile, $PharDepth = 0) use (&$phpMussel) {
    $PharDepth++;
    $Out = '';
    $PharDir = scandir('phar://' . $PharFile);
    foreach ($PharDir as $ThisPhar) {
        if (is_dir('phar://' . $PharFile . '/' . $ThisPhar)) {
            $ThisPhar = $phpMussel['BuildPharList']($PharFile . '/' . $ThisPhar, $PharDepth);
        } else {
            $ThisPhar = $PharDepth . ' ' . $PharFile . '/' . $ThisPhar;
        }
        $Out .= $ThisPhar . "\n";
    }
    return $Out;
};

/**
 * Checks whether signature length is confined within an acceptable limit.
 *
 * @param int $Length
 * @return bool
 */
$phpMussel['ConfineLength'] = function ($Length) {
    return ($Length < 4 || $Length > 1024);
};

/**
 * Codeblock for detection triggers (appends detection information). Called
 * from within the data handler (treat as private).
 */
$phpMussel['Detected'] = function (&$heur, &$lnap, &$VN, &$ofn, &$ofnSafe, &$out, &$flagged, &$md5, &$str_len) use (&$phpMussel) {
    if (!$flagged) {
        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
        $flagged = true;
    }
    $heur['detections']++;
    $phpMussel['memCache']['detections_count']++;
    if ($phpMussel['memCache']['weighted']) {
        $heur['weight']++;
        $heur['cli'] .= $lnap . sprintf(
            $phpMussel['lang']['_exclamation_final'],
            sprintf($phpMussel['lang']['detected'], $VN)
        ) . "\n";
        $heur['web'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            sprintf($phpMussel['lang']['detected'], $VN) . ' (' . $ofnSafe . ')'
        );
        return;
    }
    $out .= $lnap . sprintf(
        $phpMussel['lang']['_exclamation_final'],
        sprintf($phpMussel['lang']['detected'], $VN)
    ) . "\n";
    $phpMussel['whyflagged'] .= sprintf(
        $phpMussel['lang']['_exclamation'],
        sprintf($phpMussel['lang']['detected'], $VN) . ' (' . $ofnSafe . ')'
    );
};

/**
 * Confines a string boundary as per rules specified by parameters.
 *
 * @param string $Data The string.
 * @param string|int $Initial The start of the boundary or string initial offset value.
 * @param string|int $Terminal The end of the boundary or string terminal offset value.
 * @param array $SectionOffsets Section offset values.
 */
$phpMussel['DataConfineByOffsets'] = function (&$Data, &$Initial, &$Terminal, &$SectionOffsets) {
    if ($Initial === '*' && $Terminal === '*') {
        return;
    }
    if (substr($Initial, 0, 2) === 'SE') {
        $SectionNum = (int)substr($Initial, 2);
        $Initial = '*';
        $Terminal = '*';
        if (isset($SectionOffsets[$SectionNum][0])) {
            $Data = substr($Data, $SectionOffsets[$SectionNum][0] * 2);
        }
        if (isset($SectionOffsets[$SectionNum][1])) {
            $Data = substr($Data, 0, $SectionOffsets[$SectionNum][1] * 2);
        }
    } elseif (substr($Initial, 0, 2) === 'SL') {
        $Remainder = strlen($Initial) > 3 && substr($Initial, 2, 1) === '+' ? (substr($Initial, 3) ?: 0) : 0;
        $Initial = '*';
        $Final = count($SectionOffsets);
        if ($Final > 0 && isset($SectionOffsets[$Final - 1][0])) {
            $Data = substr($Data, ($SectionOffsets[$Final - 1][0] + $Remainder) * 2);
        }
        if ($Terminal !== '*' && $Terminal !== 'Z') {
            $Data = substr($Data, 0, $Terminal * 2);
            $Terminal = '*';
        }
    } elseif (substr($Initial, 0, 1) === 'S') {
        if (($PlusPos = strpos($Initial, '+')) !== false) {
            $SectionNum = substr($Initial, 1, $PlusPos - 1) ?: 0;
            $Remainder = substr($Initial, $PlusPos + 1) ?: 0;
        } else {
            $SectionNum = substr($Initial, 1) ?: 0;
            $Remainder = 0;
        }
        $Initial = '*';
        if (isset($SectionOffsets[$SectionNum][0])) {
            $Data = substr($Data, ($SectionOffsets[$SectionNum][0] + $Remainder) * 2);
        }
        if ($Terminal !== '*' && $Terminal !== 'Z') {
            $Data = substr($Data, 0, $Terminal * 2);
            $Terminal = '*';
        }
    } else {
        if ($Initial !== '*' && $Initial !== 'A') {
            $Data = substr($Data, $Initial * 2);
            $Initial = '*';
        }
        if ($Terminal !== '*' && $Terminal !== 'Z') {
            $Data = substr($Data, 0, $Terminal * 2);
            $Terminal = '*';
        }
    }
};

/**
 * Responsible for handling any data fed to it from the recursor. It shouldn't
 * be called manually nor from any other contexts. It takes the data given to
 * it from the recursor and checks that data against the various signatures of
 * phpMussel, before returning the results of those checks back to the
 * recursor.
 *
 * @param string $str Raw binary data to be checked, supplied by the parent
 *      closure (generally, the contents of files to being scanned).
 * @param int $dpt Represents the current depth of recursion from which the
 *      closure has been called, used for determining how far to indent any
 *      entries generated for logging and for the display of scan results in
 *      CLI.
 * @param string $ofn Represents the "original filename" of the file being
 *      scanned (in this context, referring to the name supplied by the upload
 *      client or CLI operator, as opposed to the temporary filename assigned
 *      by the server or anything else).
 * @return array|bool Returns an array containing the results of the scan as
 *      both an integer (the first element) and as human-readable text (the
 *      second element), or returns false if any problems occur preventing the
 *      data handler from completing its normal process.
 */
$phpMussel['DataHandler'] = function ($str = '', $dpt = 0, $ofn = '') use (&$phpMussel) {
    /** If the memory cache isn't set at this point, something has gone very wrong. */
    if (!isset($phpMussel['memCache'])) {
        throw new \Exception(
            (!isset($phpMussel['lang']['required_variables_not_defined'])) ?
            '[phpMussel] Required variables aren\'t defined: Can\'t continue.' :
            '[phpMussel] ' . $phpMussel['lang']['required_variables_not_defined']
        );
    }

    /** Identifies whether the scan target has been flagged for any reason yet. */
    $flagged = false;

    /** Increment scan depth. */
    $dpt++;
    /** Controls indenting relating to scan depth for normal logging and for CLI-mode scanning. */
    $lnap = str_pad('> ', ($dpt + 1), '-', STR_PAD_LEFT);

    /** Output variable (for when the output is a string). */
    $out = '';

    /** There's no point bothering to scan zero-byte files. */
    if (!$str_len = strlen($str)) {
        return [1, ''];
    }

    $md5 = md5($str);
    $sha = sha1($str);
    $crc = hash('crc32b', $str);
    /** $fourcc: First four bytes of the scan target in hexadecimal notation. */
    $fourcc = strtolower(bin2hex(substr($str, 0, 4)));
    /** $twocc: First two bytes of the scan target in hexadecimal notation. */
    $twocc = substr($fourcc, 0, 4);
    /**
     * $CoExMeta: Contains metadata pertaining to the scan target, intended to
     * be used by the "complex extended" signatures.
     */
    $CoExMeta =
        '$ofn:' . $ofn . ';md5($ofn):' . md5($ofn) . ';$dpt:' . $dpt .
        ';$str_len:' . $str_len . ';$md5:' . $md5 . ';$sha:' . $sha .
        ';$crc:' . $crc . ';$fourcc:' . $fourcc . ';$twocc:' . $twocc . ';';

    /** Indicates whether a signature is considered a "weighted" signature. */
    $phpMussel['memCache']['weighted'] = false;

    /** Variables used for weighted signatures and for heuristic analysis. */
    $heur = ['detections' => 0, 'weight' => 0, 'cli' => '', 'web' => ''];

    /** Scan target has no name? That's a little suspicious. */
    if (!$ofn) {
        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ":\n";
        $phpMussel['memCache']['detections_count']++;
        $out .= $lnap . sprintf(
            $phpMussel['lang']['_exclamation_final'],
            $phpMussel['lang']['scan_missing_filename']
        ) . "\n";
        $phpMussel['whyflagged'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            $phpMussel['lang']['scan_missing_filename']
        );
        return [2, $out];
    }
    /** URL-encoded version of the scan target name. */
    $ofnSafe = urlencode($ofn);

    /** Generate cache ID. */
    $phpMussel['HashCacheData'] = $md5 . md5($ofn);

    /** Register object scanned. */
    if (isset($phpMussel['cli_args'][1]) && $phpMussel['cli_args'][1] == 'cli_scan') {
        $phpMussel['Stats-Increment']('CLI-Scanned', 1);
    } else {
        $phpMussel['Stats-Increment']($phpMussel['EOF'] ? 'API-Scanned' : 'Web-Scanned', 1);
    }

    /**
     * Check for the existence of a cache entry corresponding to the file
     * being scanned, and if it exists, use it instead of scanning the file.
     */
    if (isset($phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']])) {
        if (!$phpMussel['EOF']) {
            $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
        }
        if (!empty($phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']][2])) {
            $phpMussel['memCache']['detections_count']++;
            $out .= $phpMussel['HexSafe']($phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']][2]);
            if (!empty($phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']][3])) {
                $phpMussel['whyflagged'] .= $phpMussel['HexSafe']($phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']][3]);
            }
        }

        /** Set debug values, if this has been enabled. */
        if (isset($phpMussel['DebugArr'])) {
            $phpMussel['DebugArrKey'] = count($phpMussel['DebugArr']);
            $phpMussel['DebugArr'][$phpMussel['DebugArrKey']] = [
                'Filename' => $ofn,
                'FromCache' => true,
                'Depth' => $dpt,
                'Size' => $str_len,
                'MD5' => $md5,
                'SHA1' => $sha,
                'CRC32B' => $crc,
                '2CC' => $twocc,
                '4CC' => $fourcc,
                'ScanPhase' => $phpMussel['memCache']['phase'],
                'Container' => $phpMussel['memCache']['container'],
                'Results' => !$out ? 1 : 2,
                'Output' => $out
            ];
        }

        /** Object not flagged. */
        if (!$out) {
            return [1, ''];
        }
        /** Register object flagged. */
        if (isset($phpMussel['cli_args'][1]) && $phpMussel['cli_args'][1] == 'cli_scan') {
            $phpMussel['Stats-Increment']('CLI-Flagged', 1);
        } else {
            $phpMussel['Stats-Increment']($phpMussel['EOF'] ? 'API-Flagged' : 'Web-Blocked', 1);
        }
        /** Object flagged. */
        return [2, $out];
    }

    /** Indicates whether we're in CLI-mode. */
    $climode = ($phpMussel['Mussel_sapi'] && $phpMussel['Mussel_PHP']) ? 1 : 0;

    if (
        $phpMussel['Config']['attack_specific']['scannable_threshold'] > 0 &&
        $str_len > $phpMussel['ReadBytes']($phpMussel['Config']['attack_specific']['scannable_threshold'])
    ) {
        $str_len = $phpMussel['ReadBytes']($phpMussel['Config']['attack_specific']['scannable_threshold']);
        $str = substr($str, 0, $str_len);
        $str_cut = 1;
    } else {
        $str_cut = 0;
    }

    /** Indicates whether we need to decode the contents of the scan target. */
    $decode_or_not = (
        (
            $phpMussel['Config']['attack_specific']['decode_threshold'] > 0 &&
            $str_len > $phpMussel['ReadBytes']($phpMussel['Config']['attack_specific']['decode_threshold'])
        ) ||
        $str_len < 16
    ) ? 0 : 1;
    /** Indicates whether the scan target is greater than 1KB (can sometimes save time for coex). */
    $len_kb = ($str_len > 1024) ? 1 : 0;
    /** Indicates whether the scan target is greater than half of 1MB (can sometimes save time for coex). */
    $len_hmb = ($str_len > 524288) ? 1 : 0;
    /** Indicates whether the scan target is greater than 1MB (can sometimes save time for coex). */
    $len_mb = ($str_len > 1048576) ? 1 : 0;
    /** Indicates whether the scan target is greater than half of 1GB (can sometimes save time for coex). */
    $len_hgb = ($str_len > 536870912) ? 1 : 0;
    /** Indicates which phase of the scan process we're currently at. */
    $phase = $phpMussel['memCache']['phase'];
    /** Indicates whether the scan target is a part of a container (and if so, which type of container). */
    $container = $phpMussel['memCache']['container'];
    /** Indicates whether the scan target possesses the PDF magic number. */
    $pdf_magic = ($fourcc == '25504446');

    /** Corresponds to the "detect_adware" configuration directive. */
    $detect_adware = $phpMussel['Config']['signatures']['detect_adware'] ? 1 : 0;
    /** Corresponds to the "detect_encryption" configuration directive. */
    $detect_encryption = $phpMussel['Config']['signatures']['detect_encryption'] ? 1 : 0;
    /** Corresponds to the "detect_joke_hoax" configuration directive. */
    $detect_joke_hoax = $phpMussel['Config']['signatures']['detect_joke_hoax'] ? 1 : 0;
    /** Corresponds to the "detect_pua_pup" configuration directive. */
    $detect_pua_pup = $phpMussel['Config']['signatures']['detect_pua_pup'] ? 1 : 0;
    /** Corresponds to the "detect_packer_packed" configuration directive. */
    $detect_packer_packed = $phpMussel['Config']['signatures']['detect_packer_packed'] ? 1 : 0;
    /** Corresponds to the "detect_shell" configuration directive. */
    $detect_shell = $phpMussel['Config']['signatures']['detect_shell'] ? 1 : 0;
    /** Corresponds to the "detect_deface" configuration directive. */
    $detect_deface = $phpMussel['Config']['signatures']['detect_deface'] ? 1 : 0;

    list($xt, $xts, $gzxt, $gzxts) = $phpMussel['FetchExt']($ofn);
    $CoExMeta .= '$xt:' . $xt . ';$xts:' . $xts . ';';

    /** Input ($str) as hexadecimal data. */
    $str_hex = bin2hex($str);
    $str_hex_len = $str_len * 2;

    /** Input ($str) normalised. */
    $str_norm = $phpMussel['prescan_normalise']($str, false, $decode_or_not);
    $str_norm_len = strlen($str_norm);

    /** Normalised input ($str_norm) as hexadecimal data. */
    $str_hex_norm = bin2hex($str_norm);
    $str_hex_norm_len = $str_norm_len * 2;

    /** Input ($str) normalised for HTML. */
    $str_html = $phpMussel['prescan_normalise']($str, true, $decode_or_not);
    $str_html_len = strlen($str_html);

    /** HTML normalised input ($str_html) as hexadecimal data. */
    $str_hex_html = bin2hex($str_html);
    $str_hex_html_len = $str_html_len * 2;

    /** Look for potential Linux/ELF indicators. */
    $is_elf = (
        $fourcc === '7f454c46' ||
        $xt === 'elf'
    );

    /** Look for potential graphics/image indicators. */
    $is_graphics = empty($str) ? false : $phpMussel['Indicator-Image']($xt, substr($str_hex, 0, 32));

    /** Look for potential HTML indicators. */
    $is_html = (
        substr_count(',asp*,dht*,hta,htm*,jsp*,php*,sht*,', ',' . $xts . ',') ||
        substr_count(',eml,hta,', ',' . $xt . ',') ||
        preg_match(
            '/3c(?:21646f6374797065|6120|626f6479|68656164|68746d6c|696672616d65|' .
            '696d67|6f626a656374|736372697074|7461626c65|7469746c65)/i',
            $str_hex_norm
        ) ||
        preg_match(
            '/(?:626f6479|68656164|68746d6c|736372697074|7461626c65|7469746c65)3e/i',
            $str_hex_norm
        )
    );

    /** Look for potential email indicators. */
    $is_email = (
        substr_count(',htm*,ema*,', ',' . $xts . ',') ||
        $xt === 'eml' ||
        preg_match(
            '/0a(?:436f6e74656e742d54797065|44617465|46726f6d|4d6573736167652d4944|4d' .
            '494d452d56657273696f6e|5265706c792d546f|52657475726e2d50617468|53656e646' .
            '572|5375626a656374|546f|582d4d61696c6572)3a20/i',
            $str_hex
        ) ||
        preg_match('/0a2d2d.{32}(?:2d2d)?(?:0d)?0a/i', $str_hex)
    );

    /** Look for potential Mach-O indicators. */
    $is_macho = (
        $fourcc === 'cafebabe' ||
        $fourcc === 'cafed00d' ||
        $fourcc === 'cefaedfe' ||
        $fourcc === 'cffaedfe' ||
        $fourcc === 'feedface' ||
        $fourcc === 'feedfacf'
    );

    /** Look for potential PDF indicators. */
    $is_pdf = ($pdf_magic || $xt === 'pdf');

    /** Look for potential Shockwave/SWF indicators. */
    $is_swf = (
        substr_count(',435753,465753,5a5753,', ',' . substr($str_hex, 0, 6) . ',') ||
        substr_count(',swf,swt,', ',' . $xt . ',')
    );

    /** "Infectable"? Used by ClamAV General and ClamAV ASCII signatures. */
    $infectable = true;

    /** "Asciiable"? Used by all ASCII signatures. */
    $asciiable = (bool)$str_hex_norm_len;

    /**
     * Attempt to confirm/guess whether the data being handled is from an OLE
     * file and whether we'll need to parse it through the OLE signatures.
     */
    $is_ole = (
        !empty($phpMussel['memCache']['file_is_ole']) && (
            $str_hex_len < 49152 ||
            substr_count(',ole,xml,rels,', ',' . $xt . ',')
        )
    );

    /** Worked by the switch file. */
    $fileswitch = 'unassigned';
    if (!isset($phpMussel['memCache']['switch.dat'])) {
        $phpMussel['memCache']['switch.dat'] = $phpMussel['ReadFileAsArray']($phpMussel['sigPath'] . 'switch.dat', FILE_IGNORE_NEW_LINES);
    }
    if (!$phpMussel['memCache']['switch.dat']) {
        $phpMussel['memCache']['scan_errors']++;
        if (!$phpMussel['Config']['signatures']['fail_silently']) {
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ":\n";
            }
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                $phpMussel['lang']['scan_signature_file_missing'] . ' (switch.dat)'
            );
            return [-3, $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                $phpMussel['lang']['scan_signature_file_missing'] . ' (switch.dat)'
            ) . "\n"];
        }
    }
    foreach ($phpMussel['memCache']['switch.dat'] as $ThisRule) {
        $Switch = (strpos($ThisRule, ';') === false) ? $ThisRule : $phpMussel['substral']($ThisRule, ';');
        if (strpos($Switch, '=') === false) {
            continue;
        }
        $Switch = explode('=', preg_replace('/[^\x20-\xff]/', '', $Switch));
        if (empty($Switch[0])) {
            continue;
        }
        if (empty($Switch[1])) {
            $Switch[1] = false;
        }
        $theSwitch = $Switch[0];
        $ThisRule = (strpos($ThisRule, ';') === false) ? [$ThisRule] : explode(';', $phpMussel['substrbl']($ThisRule, ';'));
        foreach ($ThisRule as $Fragment) {
            $Fragment = (strpos($Fragment, ':') === false) ? false : $phpMussel['SplitSigParts']($Fragment, 7);
            if (empty($Fragment[0])) {
                continue 2;
            }
            if ($Fragment[0] === 'LV') {
                if (!isset($Fragment[1]) || substr($Fragment[1], 0, 1) !== '$') {
                    continue 2;
                }
                $lv_haystack = substr($Fragment[1],1);
                if (!isset($$lv_haystack) || is_array($$lv_haystack)) {
                    continue 2;
                }
                $lv_haystack = $$lv_haystack;
                if ($climode) {
                    $lv_haystack = $phpMussel['substral']($phpMussel['substral']($lv_haystack, '/'), "\\");
                }
                $lv_needle = isset($Fragment[2]) ? $Fragment[2] : '';
                $pos_A = isset($Fragment[3]) ? $Fragment[3] : 0;
                $pos_Z = isset($Fragment[4]) ? $Fragment[4] : 0;
                $lv_min = isset($Fragment[5]) ? $Fragment[5] : 0;
                $lv_max = isset($Fragment[6]) ? $Fragment[6] : -1;
                if (!$phpMussel['lv_match']($lv_needle, $lv_haystack, $pos_A, $pos_Z, $lv_min, $lv_max)) {
                    continue 2;
                }
            } elseif (isset($Fragment[2])) {
                if (isset($Fragment[3])) {
                    if ($Fragment[2] === 'A') {
                        if (
                            !substr_count(',FD,FD-RX,FD-NORM,FD-NORM-RX,', ',' . $Fragment[0] . ',') || (
                                $Fragment[0] === 'FD' &&
                                !substr_count("\x01" . substr($str_hex, 0, $Fragment[3] * 2), "\x01" . $Fragment[1])
                            ) || (
                                $Fragment[0] === 'FD-RX' &&
                                !preg_match('/\A(?:' . $Fragment[1] . ')/i', substr($str_hex, 0, $Fragment[3] * 2))
                            ) || (
                                $Fragment[0] === 'FD-NORM' &&
                                !substr_count("\x01" . substr($str_hex_norm, 0, $Fragment[3] * 2), "\x01" . $Fragment[1])
                            ) || (
                                $Fragment[0] === 'FD-NORM-RX' &&
                                !preg_match('/\A(?:' . $Fragment[1] . ')/i', substr($str_hex_norm, 0, $Fragment[3] * 2))
                            )
                        ) {
                            continue 2;
                        }
                    } elseif (
                        !substr_count(',FD,FD-RX,FD-NORM,FD-NORM-RX,', ',' . $Fragment[0] . ',') || (
                            $Fragment[0] === 'FD' &&
                            !substr_count(substr($str_hex, $Fragment[2] * 2, $Fragment[3] * 2), $Fragment[1])
                        ) || (
                            $Fragment[0] === 'FD-RX' &&
                            !preg_match('/(?:' . $Fragment[1] . ')/i', substr($str_hex, $Fragment[2] * 2, $Fragment[3]*2))
                        ) || (
                            $Fragment[0] === 'FD-NORM' &&
                            !substr_count(substr($str_hex_norm, $Fragment[2] * 2, $Fragment[3] * 2), $Fragment[1])
                        ) || (
                            $Fragment[0] === 'FD-NORM-RX' &&
                            !preg_match('/(?:' . $Fragment[1] . ')/i', substr($str_hex_norm, $Fragment[2] * 2, $Fragment[3]*2))
                        )
                    ) {
                        continue 2;
                    }
                } else {
                    if ($Fragment[2] === 'A') {
                        if (
                            !substr_count(',FN,FD,FD-RX,FD-NORM,FD-NORM-RX,', ',' . $Fragment[0] . ',') || (
                                $Fragment[0] === 'FN' &&
                                !preg_match('/\A(?:' . $Fragment[1] . ')/i', $ofn)
                            ) || (
                                $Fragment[0] === 'FD' &&
                                !substr_count("\x01" . $str_hex, "\x01" . $Fragment[1])
                            ) || (
                                $Fragment[0] === 'FD-RX' &&
                                !preg_match('/\A(?:' . $Fragment[1] . ')/i', $str_hex)
                            ) || (
                                $Fragment[0] === 'FD-NORM' &&
                                !substr_count("\x01" . $str_hex_norm, "\x01" . $Fragment[1])
                            ) || (
                                $Fragment[0] === 'FD-NORM-RX' &&
                                !preg_match('/\A(?:' . $Fragment[1] . ')/i', $str_hex_norm)
                            )
                        ) {
                            continue 2;
                        }
                    } elseif (
                        !substr_count(',FD,FD-RX,FD-NORM,FD-NORM-RX,', ',' . $Fragment[0] . ',') || (
                            $Fragment[0] === 'FD' &&
                            !substr_count(substr($str_hex, $Fragment[2] * 2), $Fragment[1])
                        ) || (
                            $Fragment[0] === 'FD-RX' &&
                            !preg_match('/(?:' . $Fragment[1] . ')/i', substr($str_hex, $Fragment[2] * 2))
                        ) || (
                            $Fragment[0] === 'FD-NORM' &&
                            !substr_count(substr($str_hex_norm, $Fragment[2] * 2), $Fragment[1])
                        ) || (
                            $Fragment[0] === 'FD-NORM-RX' &&
                            !preg_match('/(?:' . $Fragment[1] . ')/i', substr($str_hex_norm, $Fragment[2] * 2))
                        )
                    ) {
                        continue 2;
                    }
                }
            } elseif (
                ($Fragment[0] === 'FN' && !preg_match('/(?:' . $Fragment[1] . ')/i', $ofn)) ||
                ($Fragment[0] === 'FS-MIN' && $str_len < $Fragment[1]) ||
                ($Fragment[0] === 'FS-MAX' && $str_len > $Fragment[1]) ||
                ($Fragment[0] === 'FD' && !substr_count($str_hex, $Fragment[1])) ||
                ($Fragment[0] === 'FD-RX' && !preg_match('/(?:' . $Fragment[1] . ')/i', $str_hex)) ||
                ($Fragment[0] === 'FD-NORM' && !substr_count($str_hex_norm, $Fragment[1])) ||
                ($Fragment[0] === 'FD-NORM-RX' && !preg_match('/(?:' . $Fragment[1] . ')/i', $str_hex_norm))
            ) {
                continue 2;
            } elseif (substr($Fragment[0], 0, 1) === '$') {
                $vf = substr($Fragment[0], 1);
                if (!isset($$vf) || is_array($$vf) || $$vf != $Fragment[1]) {
                    continue 2;
                }
            } elseif (substr($Fragment[0], 0, 2) === '!$') {
                $vf = substr($Fragment[0], 2);
                if (!isset($$vf) || is_array($$vf) || $$vf == $Fragment[1]) {
                    continue 2;
                }
            } elseif (!substr_count(',FN,FS-MIN,FS-MAX,FD,FD-RX,FD-NORM,FD-NORM-RX,', ',' . $Fragment[0] . ',')) {
                continue 2;
            }
        }
        if (count($Switch) > 1) {
            if ($Switch[1] === 'true') {
                $$theSwitch = true;
                continue;
            }
            if ($Switch[1] === 'false') {
                $$theSwitch = false;
                continue;
            }
            $$theSwitch = $Switch[1];
        } else {
            if (!isset($$theSwitch)) {
                $$theSwitch = true;
                continue;
            }
            $$theSwitch = (!$$theSwitch);
        }
    }
    unset($theSwitch, $Switch, $ThisRule);

    /** Section offsets. */
    $SectionOffsets = [];

    /** Confirmation of whether or not the file is a valid PE file. */
    $is_pe = false;

    /** Number of PE sections in the file. */
    $NumOfSections = 0;

    $PEFileDescription =
    $PEFileVersion =
    $PEProductName =
    $PEProductVersion =
    $PECopyright =
    $PEOriginalFilename =
    $PECompanyName = '';
    if (
        !empty($phpMussel['memCache']['PE_Sectional']) ||
        !empty($phpMussel['memCache']['PE_Extended']) ||
        $phpMussel['Config']['attack_specific']['corrupted_exe']
    ) {
        $PEArr = [];
        $PEArr['SectionArr'] = [];
        if ($twocc === '4d5a') {
            $PEArr['Offset'] = $phpMussel['UnpackSafe']('S', substr($str, 60, 4));
            $PEArr['Offset'] = $PEArr['Offset'][1];
            while (true) {
                $PEArr['DoScan'] = true;
                if ($PEArr['Offset'] < 1 || $PEArr['Offset'] > 16384 || $PEArr['Offset'] > $str_len) {
                    $PEArr['DoScan'] = false;
                    break;
                }
                $PEArr['Magic'] = substr($str, $PEArr['Offset'], 2);
                if ($PEArr['Magic']!=='PE') {
                    $PEArr['DoScan'] = false;
                    break;
                }
                $PEArr['Proc'] = $phpMussel['UnpackSafe']('S', substr($str, $PEArr['Offset'] + 4, 2));
                $PEArr['Proc'] = $PEArr['Proc'][1];
                if ($PEArr['Proc'] != 0x14c && $PEArr['Proc'] != 0x8664) {
                    $PEArr['DoScan'] = false;
                    break;
                }
                $PEArr['NumOfSections'] = $phpMussel['UnpackSafe']('S', substr($str, $PEArr['Offset'] + 6, 2));
                $NumOfSections = $PEArr['NumOfSections'] = $PEArr['NumOfSections'][1];
                $CoExMeta .= 'PE_Offset:' . $PEArr['Offset'] . ';PE_Proc:' . $PEArr['Proc'] . ';NumOfSections:' . $NumOfSections . ';';
                if ($NumOfSections < 1 || $NumOfSections > 40) {
                    $PEArr['DoScan'] = false;
                }
                break;
            }
            if (!$PEArr['DoScan']) {
                if ($phpMussel['Config']['attack_specific']['corrupted_exe']) {
                    if (!$flagged) {
                        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                        $flagged = true;
                    }
                    $heur['detections']++;
                    $phpMussel['memCache']['detections_count']++;
                    $out .= $lnap . sprintf(
                        $phpMussel['lang']['_exclamation_final'],
                        $phpMussel['lang']['corrupted']
                    ) . "\n";
                    $phpMussel['whyflagged'] .= sprintf(
                        $phpMussel['lang']['_exclamation'],
                        $phpMussel['lang']['corrupted'] . ' (' . $ofnSafe . ')'
                    );
                }
            } else {
                $is_pe = true;
                $asciiable = false;
                $PEArr['OptHdrSize'] = $phpMussel['UnpackSafe']('S', substr($str, $PEArr['Offset'] + 20, 2));
                $PEArr['OptHdrSize'] = $PEArr['OptHdrSize'][1];
                for ($PEArr['k'] = 0; $PEArr['k'] < $NumOfSections; $PEArr['k']++) {
                    $PEArr['SectionArr'][$PEArr['k']] = [];
                    $PEArr['SectionArr'][$PEArr['k']]['SectionHead'] =
                        substr($str, $PEArr['Offset'] + 24 + $PEArr['OptHdrSize'] + ($PEArr['k'] * 40), $NumOfSections * 40);
                    $PEArr['SectionArr'][$PEArr['k']]['SectionName'] =
                        str_ireplace("\x00", '', substr($PEArr['SectionArr'][$PEArr['k']]['SectionHead'], 0, 8));
                    $PEArr['SectionArr'][$PEArr['k']]['VirtualSize'] =
                        $phpMussel['UnpackSafe']('S', substr($PEArr['SectionArr'][$PEArr['k']]['SectionHead'], 8, 4));
                    $PEArr['SectionArr'][$PEArr['k']]['VirtualSize'] =
                        $PEArr['SectionArr'][$PEArr['k']]['VirtualSize'][1];
                    $PEArr['SectionArr'][$PEArr['k']]['VirtualAddress'] =
                        $phpMussel['UnpackSafe']('S', substr($PEArr['SectionArr'][$PEArr['k']]['SectionHead'], 12, 4));
                    $PEArr['SectionArr'][$PEArr['k']]['VirtualAddress'] =
                        $PEArr['SectionArr'][$PEArr['k']]['VirtualAddress'][1];
                    $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData'] =
                        $phpMussel['UnpackSafe']('S', substr($PEArr['SectionArr'][$PEArr['k']]['SectionHead'], 16, 4));
                    $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData'] =
                        $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData'][1];
                    $PEArr['SectionArr'][$PEArr['k']]['PointerToRawData'] =
                        $phpMussel['UnpackSafe']('S', substr($PEArr['SectionArr'][$PEArr['k']]['SectionHead'], 20, 4));
                    $PEArr['SectionArr'][$PEArr['k']]['PointerToRawData'] =
                        $PEArr['SectionArr'][$PEArr['k']]['PointerToRawData'][1];
                    $PEArr['SectionArr'][$PEArr['k']]['SectionData'] = substr(
                        $str,
                        $PEArr['SectionArr'][$PEArr['k']]['PointerToRawData'],
                        $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData']
                    );
                    $SectionOffsets[$PEArr['k']] = [
                        $PEArr['SectionArr'][$PEArr['k']]['PointerToRawData'],
                        $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData']
                    ];
                    $PEArr['SectionArr'][$PEArr['k']]['MD5'] =
                        md5($PEArr['SectionArr'][$PEArr['k']]['SectionData']);
                    $phpMussel['PEData'] .=
                        $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData'] . ':' .
                        $PEArr['SectionArr'][$PEArr['k']]['MD5'] . ':' . $ofn . '-' .
                        $PEArr['SectionArr'][$PEArr['k']]['SectionName'] . "\n";
                    $CoExMeta .=
                        'SectionName:' . $PEArr['SectionArr'][$PEArr['k']]['SectionName'] .
                        ';VirtualSize:' . $PEArr['SectionArr'][$PEArr['k']]['VirtualSize'] .
                        ';VirtualAddress:' . $PEArr['SectionArr'][$PEArr['k']]['VirtualAddress'] .
                        ';SizeOfRawData:' . $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData'] .
                        ';MD5:' . $PEArr['SectionArr'][$PEArr['k']]['MD5'] . ';';
                    $PEArr['SectionArr'][$PEArr['k']] =
                        $PEArr['SectionArr'][$PEArr['k']]['SizeOfRawData'] . ':' .
                        $PEArr['SectionArr'][$PEArr['k']]['MD5'] . ':';
                }
                if (substr_count($str, "V\x00a\x00r\x00F\x00i\x00l\x00e\x00I\x00n\x00f\x00o\x00\x00\x00\x00\x00\x24")) {
                    $PEArr['FINFO'] = $phpMussel['substral']($str, "V\x00a\x00r\x00F\x00i\x00l\x00e\x00I\x00n\x00f\x00o\x00\x00\x00\x00\x00\x24");
                    if (substr_count($PEArr['FINFO'], "F\x00i\x00l\x00e\x00D\x00e\x00s\x00c\x00r\x00i\x00p\x00t\x00i\x00o\x00n\x00\x00\x00")) {
                        $PEFileDescription = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "F\x00i\x00l\x00e\x00D\x00e\x00s\x00c\x00r\x00i\x00p\x00t\x00i\x00o\x00n\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    if (substr_count($PEArr['FINFO'], "F\x00i\x00l\x00e\x00V\x00e\x00r\x00s\x00i\x00o\x00n\x00\x00\x00")) {
                        $PEFileVersion = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "F\x00i\x00l\x00e\x00V\x00e\x00r\x00s\x00i\x00o\x00n\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    if (substr_count($PEArr['FINFO'], "P\x00r\x00o\x00d\x00u\x00c\x00t\x00N\x00a\x00m\x00e\x00\x00\x00")) {
                        $PEProductName = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "P\x00r\x00o\x00d\x00u\x00c\x00t\x00N\x00a\x00m\x00e\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    if (substr_count($PEArr['FINFO'], "P\x00r\x00o\x00d\x00u\x00c\x00t\x00V\x00e\x00r\x00s\x00i\x00o\x00n\x00\x00\x00")) {
                        $PEProductVersion = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "P\x00r\x00o\x00d\x00u\x00c\x00t\x00V\x00e\x00r\x00s\x00i\x00o\x00n\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    if (substr_count($PEArr['FINFO'], "L\x00e\x00g\x00a\x00l\x00C\x00o\x00p\x00y\x00r\x00i\x00g\x00h\x00t\x00\x00\x00")) {
                        $PECopyright = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "L\x00e\x00g\x00a\x00l\x00C\x00o\x00p\x00y\x00r\x00i\x00g\x00h\x00t\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    if (substr_count($PEArr['FINFO'], "O\x00r\x00i\x00g\x00i\x00n\x00a\x00l\x00F\x00i\x00l\x00e\x00n\x00a\x00m\x00e\x00\x00\x00")) {
                        $PEOriginalFilename = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "O\x00r\x00i\x00g\x00i\x00n\x00a\x00l\x00F\x00i\x00l\x00e\x00n\x00a\x00m\x00e\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    if (substr_count($PEArr['FINFO'], "C\x00o\x00m\x00p\x00a\x00n\x00y\x00N\x00a\x00m\x00e\x00\x00\x00")) {
                        $PECompanyName = trim(str_ireplace("\x00", '', $phpMussel['substrbf']($phpMussel['substral']($PEArr['FINFO'], "C\x00o\x00m\x00p\x00a\x00n\x00y\x00N\x00a\x00m\x00e\x00\x00\x00"), "\x00\x00\x00")));
                    }
                    $PEArr['FINFO'] = [];
                    foreach ([
                        'PEFileDescription',
                        'PEFileVersion',
                        'PEProductName',
                        'PEProductVersion',
                        'PECopyright',
                        'PEOriginalFilename',
                        'PECompanyName'
                    ] as $PEPart) {
                        if (!empty($$PEPart)) {
                            $PEArr['FINFO'][] = '$' . $PEPart . ':' . md5($$PEPart) . ':' . strlen($$PEPart) . ':';
                        }
                    }
                }
            }
        }
    }

    /** Look for potential indicators of not being HTML. */
    $is_not_html = (!$is_html && ($is_macho || $is_elf || $is_pe));

    /** Look for potential indicators of not being PHP. */
    $is_not_php = ((
        !substr_count(',phar,', ',' . $xt . ',') &&
        !substr_count(',php*,', ',' . $xts . ',') &&
        !substr_count(',phar,', ',' . $gzxt . ',') &&
        !substr_count(',php*,', ',' . $gzxts . ',') &&
        !substr_count($str_hex_norm, '3c3f706870')
    ) || $is_pe);

    /** Set debug values, if this has been enabled. */
    if (isset($phpMussel['DebugArr'])) {
        $phpMussel['DebugArrKey'] = count($phpMussel['DebugArr']);
        $phpMussel['DebugArr'][$phpMussel['DebugArrKey']] = [
            'Filename' => $ofn,
            'FromCache' => false,
            'Depth' => $dpt,
            'Size' => $str_len,
            'MD5' => $md5,
            'SHA1' => $sha,
            'CRC32B' => $crc,
            '2CC' => $twocc,
            '4CC' => $fourcc,
            'ScanPhase' => $phase,
            'Container' => $container,
            'FileSwitch' => $fileswitch,
            'Is_ELF' => $is_elf,
            'Is_Graphics' => $is_graphics,
            'Is_HTML' => $is_html,
            'Is_Email' => $is_email,
            'Is_MachO' => $is_macho,
            'Is_PDF' => $is_pdf,
            'Is_SWF' => $is_swf,
            'Is_PE' => $is_pe,
            'Is_Not_HTML' => $is_not_html,
            'Is_Not_PHP' => $is_not_php
        ];
        if ($is_pe) {
            $phpMussel['DebugArr'][$phpMussel['DebugArrKey']] += [
                'NumOfSections' => $NumOfSections,
                'PEFileDescription' => $PEFileDescription,
                'PEFileVersion' => $PEFileVersion,
                'PEProductName' => $PEProductName,
                'PEProductVersion' => $PEProductVersion,
                'PECopyright' => $PECopyright,
                'PEOriginalFilename' => $PEOriginalFilename,
                'PECompanyName' => $PECompanyName
            ];
        }
    }

    /** Plugin hook: "during_scan". */
    $phpMussel['Execute_Hook']('during_scan');

    /** Begin URL scanner. */
    if (
        isset($phpMussel['memCache']['URL_Scanner']) ||
        !empty($phpMussel['Config']['urlscanner']['lookup_hphosts']) ||
        !empty($phpMussel['Config']['urlscanner']['google_api_key'])
    ) {
        $phpMussel['LookupCount'] = 0;
        $URLScanner = [
            'FixedSource' => preg_replace('~(data|f(ile|tps?)|https?|sftp):~i', "\x01\\1:", str_replace("\\", '/', $str_norm)) . "\x01",
            'DomainsNoLookup' => [],
            'DomainsCount' => 0,
            'Domains' => [],
            'DomainPartsNoLookup' => [],
            'DomainParts' => [],
            'Queries' => [],
            'URLsNoLookup' => [],
            'URLsCount' => 0,
            'URLs' => [],
            'URLPartsNoLookup' => [],
            'URLParts' => [],
            'TLDs' => [],
            'Iterable' => 0,
            'Matches' => []
        ];
        $URLScanner['c'] = preg_match_all(
            '~(?:data|f(?:ile|tps?)|https?|sftp)://(?:www\d{0,3}\.)?([\da-z.-]{1,512})[^\da-z.-]~i',
            $URLScanner['FixedSource'],
            $URLScanner['Matches']
        );

        //print_r( $URLScanner );

        for ($URLScanner['i'] = 0; $URLScanner['c'] > $URLScanner['i']; $URLScanner['i']++) {
            $URLScanner['DomainParts'][$URLScanner['Iterable']] = $URLScanner['Matches'][2][$URLScanner['i']];
            if (substr_count($URLScanner['DomainParts'][$URLScanner['Iterable']], '.')) {
                $URLScanner['TLDs'][$URLScanner['Iterable']] =
                    'TLD:' . $phpMussel['substral']($URLScanner['DomainParts'][$URLScanner['Iterable']], '.') . ':';
            }
            $URLScanner['This'] =
                md5($URLScanner['Matches'][2][$URLScanner['i']]) . ':' .
                strlen($URLScanner['Matches'][2][$URLScanner['i']]) . ':';
            $URLScanner['Domains'][$URLScanner['Iterable']] = 'DOMAIN:' . $URLScanner['This'];
            $URLScanner['DomainsNoLookup'][$URLScanner['Iterable']] = 'DOMAIN-NOLOOKUP:' . $URLScanner['This'];
            $URLScanner['Iterable']++;
        }
        $URLScanner['DomainsNoLookup'] = array_unique($URLScanner['DomainsNoLookup']);
        $URLScanner['Domains'] = array_unique($URLScanner['Domains']);
        $URLScanner['DomainParts'] = array_unique($URLScanner['DomainParts']);
        $URLScanner['TLDs'] = array_unique($URLScanner['TLDs']);
        sort($URLScanner['DomainsNoLookup']);
        sort($URLScanner['Domains']);
        sort($URLScanner['DomainParts']);
        sort($URLScanner['TLDs']);
        $URLScanner['Iterable'] = 0;
        $URLScanner['Matches'] = '';
        $URLScanner['c'] = preg_match_all(
            '~(?:data|f(ile|tps?)|https?|sftp)://(?:www\d{0,3}\.)?([!#$&-;=?@-\[\]_a-z\~]{1,4096})[^!#$&-;=?@-\[\]_a-z\~]~i',
            $URLScanner['FixedSource'],
            $URLScanner['Matches']
        );
        for ($URLScanner['i'] = 0; $URLScanner['c'] > $URLScanner['i']; $URLScanner['i']++) {
            $URLScanner['This'] =
                md5($URLScanner['Matches'][2][$URLScanner['i']]) . ':' .
                strlen($URLScanner['Matches'][2][$URLScanner['i']]) . ':';
            $URLScanner['URLsNoLookup'][$URLScanner['Iterable']] = 'URL-NOLOOKUP:' . $URLScanner['This'];
            $URLScanner['URLParts'][$URLScanner['Iterable']] = $URLScanner['Matches'][2][$URLScanner['i']];
            $URLScanner['URLs'][$URLScanner['Iterable']] = 'URL:' . $URLScanner['This'];
            $URLScanner['Iterable']++;
            if (preg_match('/[^\da-z.-]$/i', $URLScanner['Matches'][2][$URLScanner['i']])) {
                $URLScanner['x'] =
                    preg_replace('/[^\da-z.-]+$/i', '', $URLScanner['Matches'][2][$URLScanner['i']]);
                $URLScanner['This'] = md5($URLScanner['x']) . ':' . strlen($URLScanner['x']) . ':';
                $URLScanner['URLsNoLookup'][$URLScanner['Iterable']] = 'URL-NOLOOKUP:' . $URLScanner['This'];
                $URLScanner['URLParts'][$URLScanner['Iterable']] = $URLScanner['x'];
                $URLScanner['URLs'][$URLScanner['Iterable']] = 'URL:' . $URLScanner['This'];
                $URLScanner['Iterable']++;
            }
            if (substr_count($URLScanner['Matches'][2][$URLScanner['i']], '?')) {
                $URLScanner['x'] = $phpMussel['substrbf']($URLScanner['Matches'][2][$URLScanner['i']], '?');
                $URLScanner['This'] = md5($URLScanner['x']) . ':' . strlen($URLScanner['x']) . ':';
                $URLScanner['URLsNoLookup'][$URLScanner['Iterable']] = 'URL-NOLOOKUP:' . $URLScanner['This'];
                $URLScanner['URLParts'][$URLScanner['Iterable']] = $URLScanner['x'];
                $URLScanner['URLs'][$URLScanner['Iterable']] = 'URL:' . $URLScanner['This'];
                $URLScanner['x'] = $phpMussel['substraf']($URLScanner['Matches'][2][$URLScanner['i']], '?');
                $URLScanner['Queries'][$URLScanner['Iterable']] =
                    'QUERY:' . md5($URLScanner['x']) . ':' .
                    strlen($URLScanner['x']) . ':';
                $URLScanner['Iterable']++;
            }
        }
        $URLScanner['Matches'] = '';
        $URLScanner['URLsNoLookup'] = array_unique($URLScanner['URLsNoLookup']);
        $URLScanner['URLs'] = array_unique($URLScanner['URLs']);
        $URLScanner['URLParts'] = array_unique($URLScanner['URLParts']);
        $URLScanner['Queries'] = array_unique($URLScanner['Queries']);
        sort($URLScanner['URLsNoLookup']);
        sort($URLScanner['URLs']);
        sort($URLScanner['URLParts']);
        sort($URLScanner['Queries']);
    }

    /** Process non-mappable signatures. */
    foreach ([
        ['General_Command_Detections', 0],
        ['Hash', 1],
        ['PE_Sectional', 2],
        ['PE_Extended', 3],
        ['URL_Scanner', 4],
        ['Complex_Extended', 5]
    ] as $ThisConf) {
        $SigFiles = isset($phpMussel['memCache'][$ThisConf[0]]) ? explode(',', $phpMussel['memCache'][$ThisConf[0]]) : [];
        foreach ($SigFiles as $SigFile) {
            if (!$SigFile) {
                continue;
            }
            if (!isset($phpMussel['memCache'][$SigFile])) {
                $phpMussel['memCache'][$SigFile] = $phpMussel['ReadFile']($phpMussel['sigPath'] . $SigFile);
            }
            if (!$phpMussel['memCache'][$SigFile]) {
                $phpMussel['memCache']['scan_errors']++;
                if (!$phpMussel['Config']['signatures']['fail_silently']) {
                    if (!$flagged) {
                        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ":\n";
                    }
                    $phpMussel['whyflagged'] .= sprintf(
                        $phpMussel['lang']['_exclamation'],
                        $phpMussel['lang']['scan_signature_file_missing'] . ' (' . $SigFile . ')'
                    );
                    return [-3, $lnap . sprintf(
                        $phpMussel['lang']['_exclamation_final'],
                        $phpMussel['lang']['scan_signature_file_missing'] . ' (' . $SigFile . ')'
                    ) . "\n"];
                }
            } elseif ($ThisConf[1] === 0) {
                if (substr($phpMussel['memCache'][$SigFile], 0, 9) === 'phpMussel') {
                    $phpMussel['memCache'][$SigFile] = substr($phpMussel['memCache'][$SigFile], 11, -1);
                }
                $ArrayCSV = explode(',', $phpMussel['memCache'][$SigFile]);
                foreach ($ArrayCSV as $ItemCSV) {
                    if (substr_count($str_hex_norm, $ItemCSV)) {
                        if (!$flagged) {
                            $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                            $flagged = true;
                        }
                        $heur['detections']++;
                        $phpMussel['memCache']['detections_count']++;
                        $out .= $lnap . sprintf(
                            $phpMussel['lang']['_exclamation'],
                            $phpMussel['lang']['scan_command_injection']
                        ) . "\n";
                        $phpMussel['whyflagged'] .= sprintf(
                            $phpMussel['lang']['_exclamation'],
                            $phpMussel['lang']['scan_command_injection'] . ', \'' . $phpMussel['HexSafe']($ItemCSV) . '\' (' . $ofnSafe . ')'
                        );
                    }
                }
                unset($ItemCSV, $ArrayCSV);
            } elseif ($ThisConf[1] === 1) {
                if (substr_count($phpMussel['memCache'][$SigFile], $md5 . ':' . $str_len . ':')) {
                    $xsig = $phpMussel['substraf']($phpMussel['memCache'][$SigFile], $md5 . ':' . $str_len . ':');
                    if (substr_count($xsig, "\n")) {
                        $xsig = $phpMussel['substrbf']($xsig, "\n");
                    }
                    $xsig = $phpMussel['vn_shorthand']($xsig);
                    if (
                        !substr_count($phpMussel['memCache']['greylist'], ',' . $xsig . ',') &&
                        empty($phpMussel['memCache']['ignoreme'])
                    ) {
                        $phpMussel['Detected']($heur, $lnap, $xsig, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                    }
                }
            } elseif ($ThisConf[1] === 2) {
                for ($PEArr['k'] = 0; $PEArr['k'] < $NumOfSections; $PEArr['k']++) {
                    if (substr_count($phpMussel['memCache'][$SigFile], $PEArr['SectionArr'][$PEArr['k']])) {
                        $xsig = $phpMussel['substraf']($phpMussel['memCache'][$SigFile], $PEArr['SectionArr'][$PEArr['k']]);
                        if (substr_count($xsig, "\n")) {
                            $xsig = $phpMussel['substrbf']($xsig, "\n");
                        }
                        $xsig = $phpMussel['vn_shorthand']($xsig);
                        if (
                            !substr_count($phpMussel['memCache']['greylist'], ',' . $xsig . ',') &&
                            empty($phpMussel['memCache']['ignoreme'])
                        ) {
                            $phpMussel['Detected']($heur, $lnap, $xsig, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                        }
                    }
                }
            } elseif ($ThisConf[1] === 3) {
                if (!empty($PEArr['FINFO'])) {
                    foreach ($PEArr['FINFO'] as $PEArr['ThisPart']) {
                        if (substr_count($phpMussel['memCache'][$SigFile], $PEArr['ThisPart'])) {
                            $xsig = $phpMussel['substraf']($phpMussel['memCache'][$SigFile], $PEArr['ThisPart']);
                            if (substr_count($xsig, "\n")) {
                                $xsig = $phpMussel['substrbf']($xsig, "\n");
                            }
                            $xsig = $phpMussel['vn_shorthand']($xsig);
                            if (
                                !substr_count($phpMussel['memCache']['greylist'], ',' . $xsig . ',') &&
                                empty($phpMussel['memCache']['ignoreme'])
                            ) {
                                $phpMussel['Detected']($heur, $lnap, $xsig, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                            }
                        }
                    }
                }
            } elseif ($ThisConf[1] === 4) {
                foreach ([$URLScanner['DomainsNoLookup'], $URLScanner['URLsNoLookup']] as $URLScanner['ThisArr']) {
                    foreach ($URLScanner['ThisArr'] as $URLScanner['This']) {
                        if (substr_count($phpMussel['memCache'][$SigFile], $URLScanner['This'])) {
                            $xsig = $phpMussel['substraf']($phpMussel['memCache'][$SigFile], $URLScanner['This']);
                            if (substr_count($xsig, "\n")) {
                                $xsig = $phpMussel['substrbf']($xsig, "\n");
                            }
                            if (substr($URLScanner['This'], 0, 15) === 'DOMAIN-NOLOOKUP') {
                                $URLScanner['DomainPartsNoLookup'][$xsig] = true;
                            } else {
                                $URLScanner['URLPartsNoLookup'][$xsig] = true;
                            }
                        }
                    }
                }
                foreach ([
                    $URLScanner['TLDs'],
                    $URLScanner['Domains'],
                    $URLScanner['URLs'],
                    $URLScanner['Queries']
                ] as $URLScanner['ThisArr']) {
                    foreach ($URLScanner['ThisArr'] as $URLScanner['This']) {
                        if (substr_count($phpMussel['memCache'][$SigFile], $URLScanner['This'])) {
                            $xsig = $phpMussel['substraf']($phpMussel['memCache'][$SigFile], $URLScanner['This']);
                            if (substr_count($xsig, "\n")) {
                                $xsig = $phpMussel['substrbf']($xsig, "\n");
                            }
                            if (
                                ($xsig = $phpMussel['vn_shorthand']($xsig)) &&
                                !substr_count($phpMussel['memCache']['greylist'], ',' . $xsig . ',') &&
                                empty($phpMussel['memCache']['ignoreme'])
                            ) {
                                $phpMussel['Detected']($heur, $lnap, $xsig, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                            }
                        }
                    }
                }
            } elseif ($ThisConf[1] === 5) {
                $coexi = 0;
                $SigName = '';
                while (true) {
                    $coexi++;
                    if (
                        $coexi === 1 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$md5:' . $md5 . ';')
                    ) {
                        $xsig = explode("\n" . '$md5:' . $md5 . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 2 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$str_len:' . $str_len . ';')
                    ) {
                        $xsig = explode("\n" . '$str_len:' . $str_len . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 3 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$fourcc:' . $fourcc . ';')
                    ) {
                        $xsig = explode("\n" . '$fourcc:' . $fourcc . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 4 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$twocc:' . $twocc . ';')
                    ) {
                        $xsig = explode("\n" . '$twocc:' . $twocc . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 5 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$phase:' . $phase . ';')
                    ) {
                        $xsig = explode("\n" . '$phase:' . $phase . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 6 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$xt:' . $xt . ';')
                    ) {
                        $xsig = explode("\n" . '$xt:' . $xt . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 7 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$sha:' . $sha . ';')
                    ) {
                        $xsig = explode("\n" . '$sha:' . $sha . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 8 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$crc:' . $crc . ';')
                    ) {
                        $xsig = explode("\n" . '$crc:' . $crc . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 9 &&
                        $is_html &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_html:1;')
                    ) {
                        $xsig = explode("\n" . '$is_html:1;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 9 &&
                        !$is_html &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_html:0;')
                    ) {
                        $xsig = explode("\n" . '$is_html:0;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 10 &&
                        $is_graphics &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_graphics:1;')
                    ) {
                        $xsig = explode("\n" . '$is_graphics:1;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 10 &&
                        !$is_graphics &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_graphics:0;')
                    ) {
                        $xsig = explode("\n" . '$is_graphics:0;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 11 &&
                        $is_pe &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_pe:1;')
                    ) {
                        $xsig = explode("\n" . '$is_pe:1;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 11 &&
                        !$is_pe &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_pe:0;')
                    ) {
                        $xsig = explode("\n" . '$is_pe:0;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 12 &&
                        $is_macho &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_macho:1;')
                    ) {
                        $xsig = explode("\n" . '$is_macho:1;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 12 &&
                        !$is_macho &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$is_macho:0;')
                    ) {
                        $xsig = explode("\n" . '$is_macho:0;', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 13 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$xts:' . $xts . ';')
                    ) {
                        $xsig = explode("\n" . '$xts:' . $xts . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 14 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$NumOfSections:' . $NumOfSections . ';')
                    ) {
                        $xsig = explode("\n" . '$NumOfSections:' . $NumOfSections . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif (
                        $coexi === 15 &&
                        substr_count($phpMussel['memCache'][$SigFile], "\n" . '$container:' . $container . ';')
                    ) {
                        $xsig = explode("\n" . '$container:' . $container . ';', $phpMussel['memCache'][$SigFile]);
                    } elseif ($coexi > 15) {
                        break;
                    } else {
                        $xsig = [];
                    }
                    $xc = count($xsig);
                    if (isset($xsig[0])) {
                        $xsig[0] = '';
                    }
                    if ($xc > 0) {
                        for ($xi = 1; $xi < $xc; $xi++) {
                            if (substr_count($xsig[$xi], "\n")) {
                                $xsig[$xi] = $phpMussel['substrbf']($xsig[$xi], "\n");
                            }
                            if (substr_count($xsig[$xi], ';')) {
                                if (!substr_count($xsig[$xi], ':')) {
                                    continue;
                                }
                                $SigName = $phpMussel['vn_shorthand']($phpMussel['substral']($xsig[$xi], ';'));
                                $xsig[$xi] = explode(';', $phpMussel['substrbl']($xsig[$xi], ';'));
                                $sxc = count($xsig[$xi]);
                            } else {
                                $SigName = $phpMussel['vn_shorthand']($xsig[$xi]);
                                $xsig[$xi] = '';
                                $sxc = 0;
                            }
                            if ($sxc > 0) {
                                for ($sxi = 0; $sxi < $sxc; $sxi++) {
                                    $xsig[$xi][$sxi] = $phpMussel['SplitSigParts']($xsig[$xi][$sxi], 7);
                                    if ($xsig[$xi][$sxi][0] === 'LV') {
                                        if (!isset($xsig[$xi][$sxi][1]) || substr($xsig[$xi][$sxi][1], 0, 1) !== '$') {
                                            continue 2;
                                        }
                                        $lv_haystack = substr($xsig[$xi][$sxi][1], 1);
                                        if (!isset($$lv_haystack) || is_array($$lv_haystack)) {
                                            continue 2;
                                        }
                                        $lv_haystack = $$lv_haystack;
                                        if ($climode) {
                                            $lv_haystack = $phpMussel['substral']($phpMussel['substral']($lv_haystack, '/'), "\\");
                                        }
                                        $lv_needle = (isset($xsig[$xi][$sxi][2])) ? $xsig[$xi][$sxi][2] : '';
                                        $pos_A = (isset($xsig[$xi][$sxi][3])) ? $xsig[$xi][$sxi][3] : 0;
                                        $pos_Z = (isset($xsig[$xi][$sxi][4])) ? $xsig[$xi][$sxi][4] : 0;
                                        $lv_min = (isset($xsig[$xi][$sxi][5])) ? $xsig[$xi][$sxi][5] : 0;
                                        $lv_max = (isset($xsig[$xi][$sxi][6])) ? $xsig[$xi][$sxi][6] : -1;
                                        if (!$phpMussel['lv_match']($lv_needle, $lv_haystack, $pos_A, $pos_Z, $lv_min, $lv_max)) {
                                            continue 2;
                                        }
                                    } elseif (isset($xsig[$xi][$sxi][2])) {
                                        if (isset($xsig[$xi][$sxi][3])) {
                                            if ($xsig[$xi][$sxi][2] == 'A') {
                                                if (
                                                    !substr_count(',FD,FD-RX,FD-NORM,FD-NORM-RX,META,', ',' . $xsig[$xi][$sxi][0] . ',') || (
                                                        $xsig[$xi][$sxi][0] == 'FD' &&
                                                        !substr_count("\x01" . substr($str_hex, 0, $xsig[$xi][$sxi][3] * 2), "\x01" . $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-RX' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($str_hex, 0, $xsig[$xi][$sxi][3] * 2))
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM' &&
                                                        !substr_count("\x01" . substr($str_hex_norm, 0, $xsig[$xi][$sxi][3] * 2), "\x01" . $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM-RX' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($str_hex_norm, 0, $xsig[$xi][$sxi][3] * 2))
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'META' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($CoExMeta, 0, $xsig[$xi][$sxi][3] * 2))
                                                    )
                                                ) {
                                                    continue 2;
                                                }
                                            } else {
                                                if (
                                                    !substr_count(',FD,FD-RX,FD-NORM,FD-NORM-RX,META,', ',' . $xsig[$xi][$sxi][0] . ',') || (
                                                        $xsig[$xi][$sxi][0] == 'FD' &&
                                                        !substr_count(substr($str_hex, $xsig[$xi][$sxi][2] * 2, $xsig[$xi][$sxi][3] * 2), $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-RX' &&
                                                        !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($str_hex, $xsig[$xi][$sxi][2] * 2, $xsig[$xi][$sxi][3] * 2))
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM' &&
                                                        !substr_count(substr($str_hex_norm, $xsig[$xi][$sxi][2] * 2, $xsig[$xi][$sxi][3] * 2), $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM-RX' &&
                                                        !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($str_hex_norm, $xsig[$xi][$sxi][2] * 2, $xsig[$xi][$sxi][3] * 2))
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'META' &&
                                                        !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($CoExMeta, $xsig[$xi][$sxi][2] * 2, $xsig[$xi][$sxi][3] * 2))
                                                    )
                                                ) {
                                                    continue 2;
                                                }
                                            }
                                        } else {
                                            if ($xsig[$xi][$sxi][2] == 'A') {
                                                if (
                                                    !substr_count(',FN,FD,FD-RX,FD-NORM,FD-NORM-RX,META,', ',' . $xsig[$xi][$sxi][0] . ',') || (
                                                        $xsig[$xi][$sxi][0] == 'FN' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', $ofn)
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD' &&
                                                        !substr_count("\x01" . $str_hex, "\x01" . $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-RX' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', $str_hex)
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM' &&
                                                        !substr_count("\x01" . $str_hex_norm, "\x01" . $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM-RX' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', $str_hex_norm)
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'META' &&
                                                        !preg_match('/\A(?:' . $xsig[$xi][$sxi][1] . ')/i', $CoExMeta)
                                                    )
                                                ) {
                                                    continue 2;
                                                }
                                            } else {
                                                if (
                                                    !substr_count(',FD,FD-RX,FD-NORM,FD-NORM-RX,META,', ',' . $xsig[$xi][$sxi][0] . ',') || (
                                                        $xsig[$xi][$sxi][0] == 'FD' &&
                                                        !substr_count(substr($str_hex, $xsig[$xi][$sxi][2] * 2), $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-RX' &&
                                                        !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($str_hex, $xsig[$xi][$sxi][2] * 2))
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM' &&
                                                        !substr_count(substr($str_hex_norm, $xsig[$xi][$sxi][2] * 2), $xsig[$xi][$sxi][1])
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'FD-NORM-RX' &&
                                                        !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($str_hex_norm, $xsig[$xi][$sxi][2] * 2))
                                                    ) || (
                                                        $xsig[$xi][$sxi][0] == 'META' &&
                                                        !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', substr($CoExMeta, $xsig[$xi][$sxi][2] * 2))
                                                    )
                                                ) {
                                                    continue 2;
                                                }
                                            }
                                        }
                                    } else {
                                        if (
                                            (
                                                $xsig[$xi][$sxi][0] == 'FN' &&
                                                !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', $ofn)
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'FS-MIN' &&
                                                $str_len < $xsig[$xi][$sxi][1]
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'FS-MAX' &&
                                                $str_len > $xsig[$xi][$sxi][1]
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'FD' &&
                                                !substr_count($str_hex, $xsig[$xi][$sxi][1])
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'FD-RX' &&
                                                !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', $str_hex)
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'FD-NORM' &&
                                                !substr_count($str_hex_norm, $xsig[$xi][$sxi][1])
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'FD-NORM-RX' &&
                                                !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', $str_hex_norm)
                                            ) || (
                                                $xsig[$xi][$sxi][0] == 'META' &&
                                                !preg_match('/(?:' . $xsig[$xi][$sxi][1] . ')/i', $CoExMeta)
                                            )
                                        ) {
                                            continue 2;
                                        }
                                        if (substr($xsig[$xi][$sxi][0], 0, 1) == '$') {
                                            $vf = substr($xsig[$xi][$sxi][0], 1);
                                            if (!isset($$vf) || is_array($$vf) || $$vf != $xsig[$xi][$sxi][1]) {
                                                continue 2;
                                            }
                                        } elseif (substr($xsig[$xi][$sxi][0], 0, 2) == '!$') {
                                            $vf = substr($xsig[$xi][$sxi][0], 2);
                                            if (!isset($$vf) || is_array($$vf) || $$vf == $xsig[$xi][$sxi][1]) {
                                                continue 2;
                                            }
                                        } elseif (!substr_count(',FN,FS-MIN,FS-MAX,FD,FD-RX,FD-NORM,FD-NORM-RX,META,', ',' . $xsig[$xi][$sxi][0] . ',')) {
                                            continue 2;
                                        }
                                    }
                                }
                            }
                            if (
                                $SigName &&
                                !substr_count($phpMussel['memCache']['greylist'], ',' . $SigName . ',') &&
                                empty($phpMussel['memCache']['ignoreme'])
                            ) {
                                $phpMussel['Detected']($heur, $lnap, $SigName, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                            }
                            $xsig[$xi] = '';
                        }
                    }
                }
                $sxi = $sxc = $SigName = $xi = $xc = $xsig = $coexi = '';
            }
        }
    }

    /** Process mappable signatures. */
    foreach ([
        ['Filename', 'str_hex', 'str_hex_len', 2],
        ['Standard', 'str_hex', 'str_hex_len', 0],
        ['Normalised', 'str_hex_norm', 'str_hex_norm_len', 0],
        ['HTML', 'str_hex_html', 'str_hex_html_len', 0],
        ['Standard_RegEx', 'str_hex', 'str_hex_len', 1],
        ['Normalised_RegEx', 'str_hex_norm', 'str_hex_norm_len', 1],
        ['HTML_RegEx', 'str_hex_html', 'str_hex_html_len', 1]
    ] as $ThisConf) {
        $DataSource = $ThisConf[1];
        $DataSourceLen = $ThisConf[2];
        $SigFiles = isset($phpMussel['memCache'][$ThisConf[0]]) ? explode(',', $phpMussel['memCache'][$ThisConf[0]]) : [];
        foreach ($SigFiles as $SigFile) {
            if (!$SigFile) {
                continue;
            }
            if (!isset($phpMussel['memCache'][$SigFile])) {
                $phpMussel['memCache'][$SigFile] = $phpMussel['ReadFileAsArray']($phpMussel['sigPath'] . $SigFile, FILE_IGNORE_NEW_LINES);
            }
            if (!$phpMussel['memCache'][$SigFile]) {
                $phpMussel['memCache']['scan_errors']++;
                if (!$phpMussel['Config']['signatures']['fail_silently']) {
                    if (!$flagged) {
                        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ":\n";
                    }
                    $phpMussel['whyflagged'] .= sprintf(
                        $phpMussel['lang']['_exclamation'],
                        $phpMussel['lang']['scan_signature_file_missing'] . ' (' . $SigFile . ')'
                    );
                    return [-3, $lnap . sprintf(
                        $phpMussel['lang']['_exclamation_final'],
                        $phpMussel['lang']['scan_signature_file_missing'] . ' (' . $SigFile . ')'
                    ) . "\n"];
                }
            } else {
                $NumSigs = count($phpMussel['memCache'][$SigFile]);
                for ($SigNum = 0; $SigNum < $NumSigs; $SigNum++) {
                    if (!$ThisSig = $phpMussel['memCache'][$SigFile][$SigNum]) {
                        continue;
                    }
                    if (substr($ThisSig, 0, 1) == '>') {
                        $ThisSig = explode('>', $ThisSig, 4);
                        if (!isset($ThisSig[1], $ThisSig[2], $ThisSig[3])) {
                            break;
                        }
                        $ThisSig[3] = (int)$ThisSig[3];
                        if ($ThisSig[1] == 'FN') {
                            if (!preg_match('/(?:' . $ThisSig[2] . ')/i', $ofn)) {
                                if ($ThisSig[3] <= $SigNum) {
                                    break;
                                }
                                $SigNum = $ThisSig[3] - 1;
                            }
                        } elseif ($ThisSig[1] == 'FS-MIN') {
                            if ($str_len < $ThisSig[2]) {
                                if ($ThisSig[3] <= $SigNum) {
                                    break;
                                }
                                $SigNum = $ThisSig[3] - 1;
                            }
                        } elseif ($ThisSig[1] == 'FS-MAX') {
                            if ($str_len > $ThisSig[2]) {
                                if ($ThisSig[3] <= $SigNum) {
                                    break;
                                }
                                $SigNum = $ThisSig[3] - 1;
                            }
                        } elseif ($ThisSig[1] == 'FD') {
                            if (!substr_count($$DataSource, $ThisSig[2])) {
                                if ($ThisSig[3] <= $SigNum) {
                                    break;
                                }
                                $SigNum = $ThisSig[3] - 1;
                            }
                        } elseif ($ThisSig[1] == 'FD-RX') {
                            if (!preg_match('/(?:' . $ThisSig[2] . ')/i', $$DataSource)) {
                                if ($ThisSig[3] <= $SigNum) {
                                    break;
                                }
                                $SigNum = $ThisSig[3] - 1;
                            }
                        } elseif (substr($ThisSig[1], 0, 1) == '$') {
                            $vf = substr($ThisSig[1], 1);
                            if (isset($$vf) && !is_array($$vf)) {
                                if ($$vf != $ThisSig[2]) {
                                    if ($ThisSig[3] <= $SigNum) {
                                        break;
                                    }
                                    $SigNum = $ThisSig[3] - 1;
                                }
                                continue;
                            }
                            if ($ThisSig[3] <= $SigNum) {
                                break;
                            }
                            $SigNum = $ThisSig[3] - 1;
                        } elseif (substr($ThisSig[1], 0, 2) == '!$') {
                            $vf = substr($ThisSig[1], 2);
                            if (isset($$vf) && !is_array($$vf)) {
                                if ($$vf == $ThisSig[2]) {
                                    if ($ThisSig[3] <= $SigNum) {
                                        break;
                                    }
                                    $SigNum = $ThisSig[3] - 1;
                                }
                                continue;
                            }
                            if ($ThisSig[3] <= $SigNum) {
                                break;
                            }
                            $SigNum = $ThisSig[3] - 1;
                        } else {
                            break;
                        }
                        continue;
                    }
                    if (strpos($ThisSig, ':') !== false) {
                        $VN = $phpMussel['SplitSigParts']($ThisSig);
                        if ($ThisConf[3] === 0) {
                            $ThisSig = preg_split('/[^\da-f>]+/i', $VN[1], -1, PREG_SPLIT_NO_EMPTY);
                            $ThisSig = ($ThisSig === false ? '' : implode('', $ThisSig));
                            $ThisSigLen = strlen($ThisSig);
                            if ($phpMussel['ConfineLength']($ThisSigLen)) {
                                continue;
                            }
                            $xstrf = isset($VN[2]) ? $VN[2] : '*';
                            $xstrt = isset($VN[3]) ? $VN[3] : '*';
                            $VN = $phpMussel['vn_shorthand']($VN[0]);
                            $VNLC = strtolower($VN);
                            if (($is_not_php && (
                                    substr_count($VNLC, '-php') || substr_count($VNLC, '.php')
                            )) || ($is_not_html && (
                                    substr_count($VNLC, '-htm') || substr_count($VNLC, '.htm')
                            )) || $$DataSourceLen < $ThisSigLen) {
                                continue;
                            }
                            if (
                                !substr_count($phpMussel['memCache']['greylist'], ',' . $VN . ',') &&
                                empty($phpMussel['memCache']['ignoreme'])
                            ) {
                                $ThisSig = strpos($ThisSig, '>') !== false ? explode('>', $ThisSig) : [$ThisSig];
                                $ThisSigCount = count($ThisSig);
                                $ThisString = $$DataSource;
                                $phpMussel['DataConfineByOffsets']($ThisString, $xstrf, $xstrt, $SectionOffsets);
                                if ($xstrf === 'A') {
                                    $ThisString = "\x01" . $ThisString;
                                    $ThisSig[0] = "\x01" . $ThisSig[0];
                                }
                                if ($xstrt === 'Z') {
                                    $ThisString .= "\x01";
                                    $ThisSig[$ThisSigCount - 1] .= "\x01";
                                }
                                for ($ThisSigi = 0; $ThisSigi < $ThisSigCount; $ThisSigi++) {
                                    if (strpos($ThisString, $ThisSig[$ThisSigi]) === false) {
                                        continue 2;
                                    }
                                    if ($ThisSigCount > 1 && strpos($ThisString, $ThisSig[$ThisSigi]) !== false) {
                                        $ThisString = $phpMussel['substraf']($ThisString, $ThisSig[$ThisSigi]);
                                    }
                                }
                                $phpMussel['Detected']($heur, $lnap, $VN, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                            }
                        } elseif ($ThisConf[3] === 1) {
                            $ThisSig = preg_split('/[\x00-\x1f]+/', $VN[1], -1, PREG_SPLIT_NO_EMPTY);
                            $ThisSig = ($ThisSig === false) ? '' : implode('', $ThisSig);
                            $ThisSigLen = strlen($ThisSig);
                            if ($phpMussel['ConfineLength']($ThisSigLen)) {
                                continue;
                            }
                            $xstrf = isset($VN[2]) ? $VN[2] : '*';
                            $xstrt = isset($VN[3]) ? $VN[3] : '*';
                            $VN = $phpMussel['vn_shorthand']($VN[0]);
                            $VNLC = strtolower($VN);
                            if (($is_not_php && (
                                substr_count($VNLC, '-php') || substr_count($VNLC, '.php')
                            )) || ($is_not_html && (
                                substr_count($VNLC, '-htm') || substr_count($VNLC, '.htm')
                            ))) {
                                continue;
                            }
                            if (
                                !substr_count($phpMussel['memCache']['greylist'], ',' . $VN . ',') &&
                                empty($phpMussel['memCache']['ignoreme'])
                            ) {
                                $ThisString = $$DataSource;
                                $phpMussel['DataConfineByOffsets']($ThisString, $xstrf, $xstrt, $SectionOffsets);
                                if ($xstrf === 'A') {
                                    if ($xstrt === 'Z') {
                                        if (!preg_match('/\A(?:' . $ThisSig . ')$/i', $ThisString)) {
                                            continue;
                                        }
                                    } elseif (!preg_match('/\A(?:' . $ThisSig . ')/i', $ThisString)) {
                                        continue;
                                    }
                                } else {
                                    if ($xstrt === 'Z') {
                                        if (!preg_match('/(?:' . $ThisSig . ')$/i', $ThisString)) {
                                            continue;
                                        }
                                    } elseif (!preg_match('/(?:' . $ThisSig . ')/i', $ThisString)) {
                                        continue;
                                    }
                                }
                                $phpMussel['Detected']($heur, $lnap, $VN, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                            }
                        } elseif ($ThisConf[3] === 2) {
                            $ThisSig = preg_split('/[\x00-\x1f]+/', $VN[1], -1, PREG_SPLIT_NO_EMPTY);
                            $ThisSig = ($ThisSig === false) ? '' : implode('', $ThisSig);
                            $VN = $phpMussel['vn_shorthand']($VN[0]);
                            if (
                                $ThisSig &&
                                !substr_count($phpMussel['memCache']['greylist'], ',' . $VN . ',') &&
                                empty($phpMussel['memCache']['ignoreme'])
                            ) {
                                if (preg_match('/(?:' . $ThisSig . ')/i', $ofn)) {
                                    $phpMussel['Detected']($heur, $lnap, $VN, $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /** Perform API lookups for domains. */
    if (isset($URLScanner) && !$out) {

        $URLScanner['DomainsCount'] = count($URLScanner['DomainParts']);

        /** Codeblock for performing hpHosts API lookups. */
        if ($phpMussel['Config']['urlscanner']['lookup_hphosts'] && $URLScanner['DomainsCount']) {
            /** Fetch the cache entry for hpHosts, if it doesn't already exist. */
            if (!isset($phpMussel['memCache']['urlscanner_domains'])) {
                $phpMussel['memCache']['urlscanner_domains'] = $phpMussel['FetchCache']('urlscanner_domains');
            }
            $URLScanner['y'] = $phpMussel['Time'] + $phpMussel['Config']['urlscanner']['cache_time'];
            $URLScanner['ScriptIdentEncoded'] = urlencode($phpMussel['ScriptIdent']);
            $URLScanner['classes'] = [
                'EMD' => "\x1a\x82\x10\x1bXXX",
                'EXP' => "\x1a\x82\x10\x16XXX",
                'GRM' => "\x1a\x82\x10\x32XXX",
                'HFS' => "\x1a\x82\x10\x32XXX",
                'PHA' => "\x1a\x82\x10\x32XXX",
                'PSH' => "\x1a\x82\x10\x31XXX"
            ];
            for ($i = 0; $i < $URLScanner['DomainsCount']; $i++) {
                if (!empty($URLScanner['DomainPartsNoLookup'][$URLScanner['DomainParts'][$i]])) {
                    continue;
                }
                if (
                    $phpMussel['Config']['urlscanner']['maximum_api_lookups'] > 0 &&
                    $phpMussel['LookupCount'] > $phpMussel['Config']['urlscanner']['maximum_api_lookups']
                ) {
                    if ($phpMussel['Config']['urlscanner']['maximum_api_lookups_response']) {
                        if (!$flagged) {
                            $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                            $flagged = true;
                        }
                        $out .= $lnap . sprintf(
                            $phpMussel['lang']['_exclamation_final'],
                            $phpMussel['lang']['too_many_urls']
                        ) . "\n";
                        $phpMussel['whyflagged'] .= sprintf(
                            $phpMussel['lang']['_exclamation'],
                            $phpMussel['lang']['too_many_urls'] . ' (' . $ofnSafe . ')'
                        );
                    }
                    break;
                }
                $URLScanner['This'] = md5($URLScanner['DomainParts'][$i]) . ':' . strlen($URLScanner['DomainParts'][$i]) . ':';
                while (substr_count($phpMussel['memCache']['urlscanner_domains'], $URLScanner['This'])) {
                    $URLScanner['Class'] =
                        $phpMussel['substrbf']($phpMussel['substral']($phpMussel['memCache']['urlscanner_domains'], $URLScanner['This']), ';');
                    if (!substr_count($phpMussel['memCache']['urlscanner_domains'], $URLScanner['This'] . ':' . $URLScanner['Class'] . ';')) {
                        break;
                    }
                    $URLScanner['Expiry'] = (int)$phpMussel['substrbf']($URLScanner['Class'], ':');
                    if ($URLScanner['Expiry'] > $phpMussel['Time']) {
                        $URLScanner['Class'] = $phpMussel['substraf']($URLScanner['Class'], ':');
                        if (!$URLScanner['Class']) {
                            continue 2;
                        }
                        $URLScanner['Class'] = $phpMussel['vn_shorthand']($URLScanner['Class']);
                        $phpMussel['Detected']($heur, $lnap, $URLScanner['Class'], $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                    }
                    $phpMussel['memCache']['urlscanner_domains'] =
                        str_ireplace($URLScanner['This'] . $URLScanner['Class'] . ';', '', $phpMussel['memCache']['urlscanner_domains']);
                }
                $URLScanner['req'] =
                    'v=' . $URLScanner['ScriptIdentEncoded'] .
                    '&s=' . $URLScanner['DomainParts'][$i] .
                    '&class=true';
                $URLScanner['req_result'] = $phpMussel['Request'](
                    'http://verify.hosts-file.net/?' . $URLScanner['req'],
                    ['v' => $URLScanner['ScriptIdentEncoded'], 's' => $URLScanner['DomainParts'][$i], 'Class' => true],
                    12
                );
                $phpMussel['LookupCount']++;
                if (substr($URLScanner['req_result'], 0, 6) == "Listed") {
                    $URLScanner['Class'] = substr($URLScanner['req_result'], 7, 3);
                    $URLScanner['Class'] = isset($URLScanner['classes'][$URLScanner['Class']]) ?
                        $URLScanner['classes'][$URLScanner['Class']] : "\x1a\x82\x10\x3fXXX";
                    $phpMussel['memCache']['urlscanner_domains'] .=
                        $URLScanner['This'] .
                        $URLScanner['y'] . ':' .
                        $URLScanner['Class'] . ';';
                    $URLScanner['Class'] = $phpMussel['vn_shorthand']($URLScanner['Class']);
                    $phpMussel['Detected']($heur, $lnap, $URLScanner['Class'], $ofn, $ofnSafe, $out, $flagged, $md5, $str_len);
                }
                $phpMussel['memCache']['urlscanner_domains'] .= $URLScanner['Domains'][$i] . $URLScanner['y'] . ':;';
            }
            $phpMussel['SaveCache']('urlscanner_domains', $URLScanner['y'], $phpMussel['memCache']['urlscanner_domains']);
        }

        $URLScanner['URLsCount'] = count($URLScanner['URLParts']);

        /** Codeblock for performing Google Safe Browsing API lookups. */
        if ($phpMussel['Config']['urlscanner']['google_api_key'] && $URLScanner['URLsCount']) {
            $URLScanner['URLsChunked'] = (
                $URLScanner['URLsCount'] > 500
            ) ? array_chunk($URLScanner['URLParts'], 500) : [$URLScanner['URLParts']];
            $URLScanner['URLChunks'] = count($URLScanner['URLsChunked']);
            for ($i = 0; $i < $URLScanner['URLChunks']; $i++) {
                if (
                    $phpMussel['Config']['urlscanner']['maximum_api_lookups'] > 0 &&
                    $phpMussel['LookupCount'] > $phpMussel['Config']['urlscanner']['maximum_api_lookups']
                ) {
                    if ($phpMussel['Config']['urlscanner']['maximum_api_lookups_response']) {
                        if (!$flagged) {
                            $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                            $flagged = true;
                        }
                        $out .= $lnap . sprintf(
                            $phpMussel['lang']['_exclamation_final'],
                            $phpMussel['lang']['too_many_urls']
                        ) . "\n";
                        $phpMussel['whyflagged'] .= sprintf(
                            $phpMussel['lang']['_exclamation'],
                            $phpMussel['lang']['too_many_urls'] . ' (' . $ofnSafe . ')'
                        );
                    }
                    break;
                }
                try {
                    $URLScanner['SafeBrowseLookup'] = $phpMussel['SafeBrowseLookup'](
                        $URLScanner['URLsChunked'][$i],
                        $URLScanner['URLPartsNoLookup'],
                        $URLScanner['DomainPartsNoLookup']
                    );
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
                if ($URLScanner['SafeBrowseLookup'] !== 204) {
                    if (!$flagged) {
                        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                        $flagged = true;
                    }
                    $URLScanner['langRef'] = 'SafeBrowseLookup_' . $URLScanner['SafeBrowseLookup'];
                    if (empty($phpMussel['lang'][$URLScanner['langRef']])) {
                        $URLScanner['langRef'] = 'SafeBrowseLookup_999';
                    }
                    $out .= $lnap . sprintf(
                        $phpMussel['lang']['_exclamation_final'],
                        $phpMussel['lang'][$URLScanner['langRef']]
                    ) . "\n";
                    $phpMussel['whyflagged'] .= sprintf(
                        $phpMussel['lang']['_exclamation'],
                        $phpMussel['lang'][$URLScanner['langRef']] . ' (' . $ofnSafe . ')'
                    );
                }
            }
        }

    }

    /** URL scanner data cleanup. */
    unset($URLScanner);

    /** PHP chameleon attack detection. */
    if ($phpMussel['Config']['attack_specific']['chameleon_from_php']) {
        if (!(
            substr_count(',cvd,inc,md,phar,pzp,tpl,txt,tzt,', ',' . $xt . ',') ||
            substr_count(',php*,', ',' . $xts . ',') ||
            substr_count(',cvd,inc,md,phar,pzp,tpl,txt,tzt,', ',' . $gzxt . ',') ||
            substr_count(',php*,', ',' . $gzxts . ',') ||
            substr_count(',' . $phpMussel['Config']['attack_specific']['archive_file_extensions'] . ',', ',' . $xts . ',') ||
            substr_count(',' . $phpMussel['Config']['attack_specific']['archive_file_extensions'] . ',', ',' . $gzxts . ',') ||
            substr_count(',' . $phpMussel['Config']['attack_specific']['archive_file_extensions'] . ',', ',' . $xt . ',') ||
            substr_count(',' . $phpMussel['Config']['attack_specific']['archive_file_extensions'] . ',', ',' . $gzxt . ',')
        ) && substr_count($str_hex_norm,'3c3f706870')) {
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                $flagged = true;
            }
            $heur['detections']++;
            $phpMussel['memCache']['detections_count']++;
            $out .= $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'PHP')
            ) . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'PHP') . ' (' . $ofnSafe . ')'
            );
        }
    }

    /** Executable chameleon attack detection. */
    if ($phpMussel['Config']['attack_specific']['chameleon_from_exe']) {
        if (substr_count(',acm,ax,com,cpl,dll,drv,exe,ocx,rs,scr,sys,', ',' . $xt . ',')) {
            if ($twocc !== '4d5a') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'EXE')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'EXE') . ' (' . $ofnSafe . ')'
                );
            }
        } elseif ($twocc === '4d5a') {
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                $flagged = true;
            }
            $heur['detections']++;
            $phpMussel['memCache']['detections_count']++;
            $out .= $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'EXE')
            ) . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'EXE') . ' (' . $ofnSafe . ')'
            );
        }
        if ($xt === 'elf') {
            if ($fourcc !== '7f454c46') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'ELF')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'ELF') . ' (' . $ofnSafe . ')'
                );
            }
        } elseif ($fourcc === '7f454c46') {
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                $flagged = true;
            }
            $heur['detections']++;
            $phpMussel['memCache']['detections_count']++;
            $out .= $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'ELF')
            ) . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'ELF') . ' (' . $ofnSafe . ')'
            );
        }
        if ($xt === 'lnk') {
            if (substr($str_hex, 0, 16) !== '4c00000001140200') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'LNK')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'LNK') . ' (' . $ofnSafe . ')'
                );
            }
        } elseif (substr($str_hex, 0, 16) === '4c00000001140200') {
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                $flagged = true;
            }
            $heur['detections']++;
            $phpMussel['memCache']['detections_count']++;
            $out .= $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'LNK')
            ) . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'LNK') . ' (' . $ofnSafe . ')'
            );
        }
        if ($xt === 'msi') {
            if (substr($str_hex, 0, 16) !== 'd0cf11e0a1b11ae1') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'MSI')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'MSI') . ' (' . $ofnSafe . ')'
                );
            }
        }
    }

    /** Archive chameleon attack detection. */
    if ($phpMussel['Config']['attack_specific']['chameleon_to_archive']) {
        if ($xts === 'zip*') {
            if ($twocc !== '504b') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'ZIP')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'ZIP') . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'rar') {
            if ($fourcc !== '52617221' && $fourcc !== '52457e5e') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'RAR')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'RAR') . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'gz') {
            if ($twocc !== '1f8b') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'GZIP')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'GZIP') . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'bz2') {
            if (substr($str_hex, 0, 6) !== '425a68') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'BZIP2')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'BZIP2') . ' (' . $ofnSafe . ')'
                );
            }
        }
    }

    /** Office document chameleon attack detection. */
    if ($phpMussel['Config']['attack_specific']['chameleon_to_doc']) {
        if (substr_count(',doc,dot,pps,ppt,xla,xls,wiz,', ',' . $xt . ',')) {
            if ($fourcc !== 'd0cf11e0') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'Office')
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], 'Office') . ' (' . $ofnSafe . ')'
                );
            }
        }
    }

    /** Image chameleon attack detection. */
    if ($phpMussel['Config']['attack_specific']['chameleon_to_img']) {
        if ($xt === 'bmp' || $xt === 'dib') {
            if ($twocc !== '424d') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'gif') {
            if (substr($str_hex, 0, 12) !== '474946383761' && substr($str_hex, 0, 12) !== '474946383961') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if (
            $xt === 'jfi' ||
            $xt === 'jfif' ||
            $xt === 'jif' ||
            $xt === 'jpe' ||
            $xt === 'jpeg' ||
            $xt === 'jpg'
        ) {
            if (substr($str_hex, 0, 6) !== 'ffd8ff') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'jp2') {
            if (substr($str_hex, 0, 16) !== '0000000c6a502020') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'pdd' || $xt === 'psd') {
            if ($fourcc !== '38425053') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'png') {
            if ($fourcc !== '89504e47') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'webp') {
            if ($fourcc !== '52494646' || substr($str, 8, 4) !== 'WEBP') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
        if ($xt === 'xcf') {
            if (substr($str,0,8) !== 'gimp xcf') {
                if (!$flagged) {
                    $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                    $flagged = true;
                }
                $heur['detections']++;
                $phpMussel['memCache']['detections_count']++;
                $out .= $lnap . sprintf(
                    $phpMussel['lang']['_exclamation_final'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image'])
                ) . "\n";
                $phpMussel['whyflagged'] .= sprintf(
                    $phpMussel['lang']['_exclamation'],
                    sprintf($phpMussel['lang']['scan_chameleon'], $phpMussel['lang']['image']) . ' (' . $ofnSafe . ')'
                );
            }
        }
    }

    /** PDF chameleon attack detection. */
    if ($phpMussel['Config']['attack_specific']['chameleon_to_pdf']) {
        if ($xt === 'pdf' && !$pdf_magic) {
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                $flagged = true;
            }
            $heur['detections']++;
            $phpMussel['memCache']['detections_count']++;
            $out .= $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'PDF')
            ) . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                sprintf($phpMussel['lang']['scan_chameleon'], 'PDF') . ' (' . $ofnSafe . ')'
            );
        }
    }

    /** Control character detection. */
    if ($phpMussel['Config']['attack_specific']['block_control_characters']) {
        if (preg_match('/[\x00-\x08\x0b\x0c\x0e\x1f\x7f]/i', $str)) {
            $out .= $lnap . sprintf(
                $phpMussel['lang']['_exclamation'],
                $phpMussel['lang']['detected_control_characters']
            ) . "\n";
            $heur['detections']++;
            $phpMussel['memCache']['detections_count']++;
            if (!$flagged) {
                $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                $flagged = true;
            }
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                $phpMussel['lang']['detected_control_characters'] . ' (' . $ofnSafe . ')'
            );
        }
    }

    /**
     * If the heuristic weight of the current scan iteration exceeds the
     * heuristic threshold defined by the configuration, or if outs has already
     * been filled, dump all heuristic detections and non-heuristic detections
     * together into outs and regard the iteration as flagged.
     */
    if (
        $heur['weight'] >= $phpMussel['Config']['heuristic']['threshold'] ||
        $out
    ) {
        $out .= $heur['cli'];
        $phpMussel['whyflagged'] .= $heur['web'];
    }

    /** Virus Total API integration. */
    if (
        !$out &&
        !empty($phpMussel['Config']['virustotal']['vt_public_api_key'])
    ) {
        $DoScan = false;
        $phpMussel['Config']['virustotal']['vt_suspicion_level'] =
            (int)$phpMussel['Config']['virustotal']['vt_suspicion_level'];
        if ($phpMussel['Config']['virustotal']['vt_suspicion_level'] === 0) {
            $DoScan = ($heur['weight'] > 0);
        } elseif ($phpMussel['Config']['virustotal']['vt_suspicion_level'] === 1) {
            $DoScan = (
                $heur['weight'] > 0 ||
                $is_pe ||
                $fileswitch === 'chrome' ||
                $fileswitch === 'java' ||
                $fileswitch === 'docfile' ||
                $fileswitch === 'vt_interest'
            );
        } elseif ($phpMussel['Config']['virustotal']['vt_suspicion_level'] === 2) {
            $DoScan = true;
        }
        if ($DoScan) {
            $VTWeight = ['weight' => 0, 'cli' => '', 'web' => ''];
            if (!isset($phpMussel['memCache']['vt_quota'])) {
                $phpMussel['memCache']['vt_quota'] = $phpMussel['FetchCache']('vt_quota');
            }
            $x = 0;
            if (!empty($phpMussel['memCache']['vt_quota'])) {
                $phpMussel['memCache']['vt_quota'] = explode(';', $phpMussel['memCache']['vt_quota']);
                foreach ($phpMussel['memCache']['vt_quota'] as &$phpMussel['ThisQuota']) {
                    if ($phpMussel['ThisQuota'] > $phpMussel['Time']) {
                        $x++;
                    } else {
                        $phpMussel['ThisQuota'] = '';
                    }
                }
                unset($phpMussel['ThisQuota']);
                $phpMussel['memCache']['vt_quota'] =
                    implode(';', $phpMussel['memCache']['vt_quota']);
            }
            if ($x < $phpMussel['Config']['virustotal']['vt_quota_rate']) {
                $VTParams = [
                    'apikey' => $phpMussel['Config']['virustotal']['vt_public_api_key'],
                    'resource' => $md5
                ];
                $VTRequest = $phpMussel['Request'](
                    'http://www.virustotal.com/vtapi/v2/file/report?apikey=' .
                    urlencode($phpMussel['Config']['virustotal']['vt_public_api_key']) .
                    '&resource=' . $md5,
                $VTParams, 12);
                $VTJSON = json_decode($VTRequest, true);
                $y = $phpMussel['Time'] + ($phpMussel['Config']['virustotal']['vt_quota_time'] * 60);
                $phpMussel['memCache']['vt_quota'] .= $y . ';';
                while (substr_count($phpMussel['memCache']['vt_quota'], ';;')) {
                    $phpMussel['memCache']['vt_quota'] = str_ireplace(';;', ';', $phpMussel['memCache']['vt_quota']);
                }
                $phpMussel['SaveCache']('vt_quota', $y + 60, $phpMussel['memCache']['vt_quota']);
                if (isset($VTJSON['response_code'])) {
                    $VTJSON['response_code'] = (int)$VTJSON['response_code'];
                    if (
                        isset($VTJSON['scans']) &&
                        $VTJSON['response_code'] === 1 &&
                        is_array($VTJSON['scans'])
                    ) {
                        foreach ($VTJSON['scans'] as $VTKey => $VTValue) {
                            if ($VTValue['detected'] && $VTValue['result']) {
                                $VN = $VTKey . '(VirusTotal)-' . $VTValue['result'];
                                if (
                                    !substr_count($phpMussel['memCache']['greylist'], ',' . $VN . ',') &&
                                    empty($phpMussel['memCache']['ignoreme'])
                                ) {
                                    if (!$flagged) {
                                        $phpMussel['killdata'] .= $md5 . ':' . $str_len . ':' . $ofn . "\n";
                                        $flagged = true;
                                    }
                                    $heur['detections']++;
                                    $phpMussel['memCache']['detections_count']++;
                                    if ($phpMussel['Config']['virustotal']['vt_weighting'] > 0) {
                                        $VTWeight['weight']++;
                                        $VTWeight['web'] .= $lnap . sprintf(
                                            $phpMussel['lang']['_exclamation'],
                                            sprintf($phpMussel['lang']['detected'], $VN)
                                        ) . "\n";
                                        $VTWeight['cli'] .= sprintf(
                                            $phpMussel['lang']['_exclamation'],
                                            sprintf($phpMussel['lang']['detected'], $VN) . ' (' . $ofnSafe . ')'
                                        );
                                    } else {
                                        $out .= $lnap . sprintf(
                                            $phpMussel['lang']['_exclamation_final'],
                                            sprintf($phpMussel['lang']['detected'], $VN)
                                        ) . "\n";
                                        $phpMussel['whyflagged'] .= sprintf(
                                            $phpMussel['lang']['_exclamation'],
                                            sprintf($phpMussel['lang']['detected'], $VN) . ' (' . $ofnSafe . ')'
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
                if (
                    $VTWeight['weight'] > 0 &&
                    $VTWeight['weight'] >= $phpMussel['Config']['virustotal']['vt_weighting']
                ) {
                    $out .= $VTWeight['web'];
                    $phpMussel['whyflagged'] .= $VTWeight['cli'];
                }
            }
        }
    }

    /** Plugin hook: "after_vt". */
    $phpMussel['Execute_Hook']('after_vt');

    if (
        isset($phpMussel['HashCacheData']) &&
        !isset($phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']]) &&
        $phpMussel['Config']['general']['scan_cache_expiry'] > 0
    ) {
        if (empty($phpMussel['HashCache']['Data']) || !is_array($phpMussel['HashCache']['Data'])) {
            $phpMussel['HashCache']['Data'] = [];
        }
        $phpMussel['HashCache']['Data'][$phpMussel['HashCacheData']] = [
            $phpMussel['HashCacheData'],
            $phpMussel['Time'] + $phpMussel['Config']['general']['scan_cache_expiry'],
            (empty($out) ? '' : bin2hex($out)),
            (empty($phpMussel['whyflagged']) ? '' : bin2hex($phpMussel['whyflagged']))
        ];
    }

    /** Set final debug values, if this has been enabled. */
    if (isset($phpMussel['DebugArr'], $phpMussel['DebugArrKey'])) {
        $phpMussel['DebugArr'][$phpMussel['DebugArrKey']]['Results'] = !$out ? 1 : 2;
        $phpMussel['DebugArr'][$phpMussel['DebugArrKey']]['Output'] = $out;
    }

    if ($out) {
        /** Register object flagged. */
        if (isset($phpMussel['cli_args'][1]) && $phpMussel['cli_args'][1] == 'cli_scan') {
            $phpMussel['Stats-Increment']('CLI-Flagged', 1);
        } else {
            $phpMussel['Stats-Increment']($phpMussel['EOF'] ? 'API-Flagged' : 'Web-Blocked', 1);
        }
    }

    /** Exit data handler. */
    return !$out ? [1, ''] : [2, $out];
};

/**
 * Splits a signature into its constituent parts (name, pattern, etc).
 *
 * @param string $Sig The signature.
 * @param int $Max The maximum number of parts to return (optional).
 * @return array The parts.
 */
$phpMussel['SplitSigParts'] = function ($Sig, $Max = -1) {
    return preg_split('~(?<!\?|\<)\:~', $Sig, $Max, PREG_SPLIT_NO_EMPTY);
};

/**
 * Handles scanning for files contained within archives.
 *
 * @param string $ItemRef A reference to the path and original filename of the
 *      item being scanned in relation to its container and/or its heirarchy
 *      within the scan process.
 * @param string $Filename The original filename of the item being scanned.
 * @param string $Data The data to be scanned.
 * @param int $Depth The depth of the item being scanned in relation to its
 *      container and/or its heirarchy within the scan process.
 * @param string $lnap Line padding for the scan results.
 * @param int $r Scan results inherited from parent in the form of an integer.
 * @param string $x Scan results inherited from parent in the form of a string.
 * @return array Returns an array containing the results of the scan as both an
 *      integer (the first element) and as human-readable text (the second
 *      element).
 */
$phpMussel['MetaDataScan'] = function ($ItemRef, $Filename, $Data, $Depth, $lnap, $r, $x) use (&$phpMussel) {
    if (!$Filesize = strlen($Data)) {
        return [$r, $x];
    }
    $ItemRefSafe = urlencode($ItemRef);
    $MD5 = md5($Data);
    if (
        $phpMussel['Config']['files']['filesize_archives'] &&
        $phpMussel['Config']['files']['filesize_limit'] > 0 &&
        $Filesize > $phpMussel['ReadBytes']($phpMussel['Config']['files']['filesize_limit'])
    ) {
        if (!$phpMussel['Config']['files']['filesize_response']) {
            $x .=
                $lnap . $phpMussel['lang']['ok'] . ' (' .
                $phpMussel['lang']['filesize_limit_exceeded'] . ").\n";
            return [$r, $x];
        }
        $r = 2;
        $phpMussel['killdata'] .= $MD5 . ':' . $Filesize . ':' . $ItemRef . "\n";
        $phpMussel['whyflagged'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            $phpMussel['lang']['filesize_limit_exceeded'] . ' (' . $ItemRefSafe . ')'
        );
        $x .=
            $lnap . $phpMussel['lang']['filesize_limit_exceeded'] .
            $phpMussel['lang']['_fullstop_final'] . "\n";
        return [$r, $x];
    }
    if ($phpMussel['Config']['files']['filetype_archives']) {
        $decPos = strrpos($Filename, '.');
        $ofnLen = strlen($Filename);
        if ($decPos === false || $decPos === ($ofnLen - 1)) {
            $xts = $xt = '-';
        } else {
            $xt = strtolower(substr($Filename, ($decPos + 1)));
            $xts = substr($xt, 0, 3) . '*';
        }
        if (
            substr_count(',' . $phpMussel['Config']['files']['filetype_whitelist'] . ',', ',' . $xt . ',') ||
            substr_count(',' . $phpMussel['Config']['files']['filetype_whitelist'] . ',', ',' . $xts . ',')
        ) {
            $x .= $lnap . $phpMussel['lang']['scan_no_problems_found'] . "\n";
            return [$r, $x];
        }
        if (
            substr_count(',' . $phpMussel['Config']['files']['filetype_blacklist'] . ',', ',' . $xt . ',') ||
            substr_count(',' . $phpMussel['Config']['files']['filetype_blacklist'] . ',', ',' . $xts . ',')
        ) {
            $r = 2;
            $phpMussel['killdata'] .= $MD5 . ':' . $Filesize . ':' . $ItemRef . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                $phpMussel['lang']['filetype_blacklisted'] . ' (' . $ItemRefSafe . ')'
            );
            $x .=
                $lnap . $phpMussel['lang']['filetype_blacklisted'] .
                $phpMussel['lang']['_fullstop_final'] . "\n";
            return [$r, $x];
        }
        if (
            $phpMussel['Config']['files']['filetype_greylist'] &&
            !substr_count(',' . $phpMussel['Config']['files']['filetype_greylist'] . ',', ',' . $xt . ',') &&
            !substr_count(',' . $phpMussel['Config']['files']['filetype_greylist'] . ',', ',' . $xts . ',')
        ) {
            $r = 2;
            $phpMussel['killdata'] .= $MD5 . ':' . $Filesize . ':' . $ItemRef . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                $phpMussel['lang']['filetype_blacklisted'] . ' (' . $ItemRefSafe . ')'
            );
            $x .=
                $lnap . $phpMussel['lang']['filetype_blacklisted'] .
                $phpMussel['lang']['_fullstop_final'] . "\n";
            return [$r, $x];
        }
    }
    $phpMussel['memCache']['objects_scanned']++;
    try {
        $Scan = $phpMussel['DataHandler']($Data, $Depth, $Filename);
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }
    if ($Scan[0] !== 1) {
        return [$Scan[0], $x . $Scan[1]];
    }
    return [$r, $x];
};

/**
 * Looks for indicators of image files (i.e., attempts to determine whether a
 * file is an image file).
 *
 * @param string $Ext The file extension.
 * @param string $Head The file header.
 * @return bool True: Indicators found. False: Indicators not found.
 */
$phpMussel['Indicator-Image'] = function ($Ext, $Head) {
    return (
        preg_match(
            '/^(?:bm[2p]|c(d5|gm)|d(ib|w[fg]|xf)|ecw|fits|gif|img|j(f?if?|p[2s]|pe?g?2?|xr)|p(bm|cx|dd|gm|ic|n[gms]|' .
            'pm|s[dp])|s(id|v[ag])|tga|w(bmp?|ebp|mp)|x(cf|bmp))$/'
        , $Ext) ||
        preg_match(
            '/^(?:0000000c6a502020|25504446|38425053|424d|474946383[79]61|57454250|67696d7020786366|89504e47|ffd8ff)/'
        , $Head)
    );
};

/**
 * Fetches extensions data from filenames.
 *
 * @param string $ofn The original filename.
 * @return array The extensions data.
 */
$phpMussel['FetchExt'] = function ($ofn) {
    $decPos = strrpos($ofn, '.');
    $ofnLen = strlen($ofn);
    if ($decPos === false || $decPos === ($ofnLen - 1)) {
        return ['-', '-', '-', '-'];
    }
    $xt = strtolower(substr($ofn, ($decPos + 1)));
    $xts = substr($xt, 0, 3) . '*';
    if (strtolower(substr($ofn, -3)) === '.gz') {
        $ofnNoGZ = substr($ofn, 0, ($ofnLen - 3));
        $decPosNoGZ = strrpos($ofnNoGZ, '.');
        if ($decPosNoGZ !== false && $decPosNoGZ !== (strlen($ofnNoGZ) - 1)) {
            $gzxt = strtolower(substr($ofnNoGZ, ($decPosNoGZ + 1)));
            $gzxts = substr($gzxt, 0, 3) . '*';
        }
    } else {
        $gzxts = $gzxt = '-';
    }
    return [$xt, $xts, $gzxt, $gzxts];
};

/**
 * Remove occurrence of $A from leading substring of $B.
 */
$phpMussel['RemoveLeadMatch'] = function ($A, $B) {
    $LenA = strlen($A);
    $LenB = strlen($B);
    for ($Iter = 0; $Iter < $LenA && $Iter < $LenB; $Iter++) {
        $CharA = substr($A, $Iter, 1);
        $CharB = substr($B, $Iter, 1);
        if ($CharA !== $CharB) {
            break;
        }
    }
    return ($Iter === $LenB) ? '' : substr($B, $Iter);
};

/**
 * Get substring of string after final slash.
 */
$phpMussel['SubstrAfterFinalSlash'] = function ($String) {
    return strpos($String, '/') !== false ? substr($String, strrpos($String, '/') + 1) : (
        strpos($String, "\\") !== false ? substr($String, strrpos($String, "\\") + 1) : $String
    );
};

/**
 * Responsible for recursing through any files given to it to be scanned, which
 * may be necessary for the case of archives and directories. It performs the
 * preparations necessary for scanning files using the "data handler" and the
 * "meta data scan" closures. Additionally, it performs some necessary
 * whitelist, blacklist and greylist checks, filesize and file extension
 * checks, and handles the processing and extraction of files from archives,
 * fetching the files contained in archives being scanned in order to process
 * those contained files as so that they, too, may be scanned.
 *
 * When phpMussel is instructed to scan a directory or an array of multiple
 * files, the recursor is the closure function responsible for iterating
 * through that directory and/or array queued for scanning, and if necessary,
 * will recurse itself (such as for when scanning a directory containing
 * sub-directories or when scanning a multidimensional array of multiple files
 * and/or directories).
 *
 * @param string|array $f In the context of the initial file upload scanning
 *      that phpMussel performs when operating via a server, this parameter (a
 *      string) represents the "temporary filename" of the file being scanned
 *      (the temporary filename, in this context, referring to the name
 *      temporarily assigned to the file by the server upon the file being
 *      uploaded to the temporary uploads location assigned to the server).
 *      When operating in the context of CLI mode, both $f and $ofn represent
 *      the scan target, as per specified by the CLI operator; The only
 *      difference between the two is when the scan target is a directory,
 *      rather than a single file; $f will represent the full path to the file
 *      (so, directory plus filename), whereas $ofn will represent only the
 *      filename. This parameter can also accept an array of filenames.
 * @param bool $n This optional parameter is a boolean (defaults to false, but
 *      set to true during the initial scan of file uploads), indicating the
 *      format for returning the scan results. False instructs the function to
 *      return results as an integer; True instructs the function to return
 *      results as human readable text (refer to Section 3A of the README
 *      documentation, "HOW TO USE (FOR WEB SERVERS)", for more information).
 * @param bool $zz This optional parameter is a boolean (defaults to false, but
 *      set to true during the initial scan of file uploads), indicating to the
 *      function whether or not arrayed results should be imploded prior to
 *      being returned to the calling function. False instructs the function to
 *      return the arrayed results as verbatim; True instructs the function to
 *      return the arrayed results as an imploded string.
 * @param int $dpt Represents the current depth of recursion from which the
 *      function has been called. This information is used for determining how
 *      far to indent any entries generated for logging and for the display of
 *      scan results in CLI (you should never manually set this parameter
 *      yourself).
 * @param string $ofn For the file upload scanning that phpMussel normally
 *      performs by default, this parameter represents the "original filename"
 *      of the file being scanned (the original filename, in this context,
 *      referring to the name supplied by the upload client, as opposed to the
 *      temporary filename assigned by the server or anything else).
 *      When operating in the context of CLI mode, both $f and $ofn represent
 *      the scan target, as per specified by the CLI operator; The only
 *      difference between the two is when the scan target is a directory,
 *      rather than a single file; $f will represent the full path to the file
 *      (so, directory plus filename), whereas $ofn will represent only the
 *      filename.
 * @return int|string|array The scan results, returned as an array when the $f
 *      parameter is an array and when $n and/or $zz is/are false, and
 *      otherwise returned as per described by the README documentation. The
 *      function may also die the script and return nothing, if something goes
 *      wrong, such as if the function is triggered in the absense of the
 *      required $phpMussel['memCache'] variable being set.
 */
$phpMussel['Recursor'] = function ($f = '', $n = false, $zz = false, $dpt = 0, $ofn = '') use (&$phpMussel) {
    if (!isset($phpMussel['memCache'])) {
        throw new \Exception(
            (!isset($phpMussel['lang']['required_variables_not_defined'])) ?
            '[phpMussel] Required variables aren\'t defined: Can\'t continue.' :
            '[phpMussel] ' . $phpMussel['lang']['required_variables_not_defined']
        );
    }

    /** Prepare signature files for the scan process. */
    if (empty($phpMussel['memCache']['OrganisedSigFiles'])) {
        $phpMussel['OrganiseSigFiles']();
        $phpMussel['memCache']['OrganisedSigFiles'] = true;
    }

    if ($phpMussel['EOF']) {
        $phpMussel['whyflagged'] = $phpMussel['killdata'] = $phpMussel['PEData'] = '';
        if ($dpt === 0 || !isset(
            $phpMussel['memCache']['objects_scanned'],
            $phpMussel['memCache']['detections_count'],
            $phpMussel['memCache']['scan_errors']
        )) {
            $phpMussel['memCache']['objects_scanned'] = 0;
            $phpMussel['memCache']['detections_count'] = 0;
            $phpMussel['memCache']['scan_errors'] = 0;
        }
    } else {
        if (!isset($phpMussel['killdata'])) {
            $phpMussel['killdata'] = '';
        }
        if (!isset($phpMussel['whyflagged'])) {
            $phpMussel['whyflagged'] = '';
        }
        if (!isset($phpMussel['PEData'])) {
            $phpMussel['PEData'] = '';
        }
        if (!isset(
            $phpMussel['memCache']['objects_scanned'],
            $phpMussel['memCache']['detections_count'],
            $phpMussel['memCache']['scan_errors']
        )) {
            $phpMussel['memCache']['objects_scanned'] = 0;
            $phpMussel['memCache']['detections_count'] = 0;
            $phpMussel['memCache']['scan_errors'] = 0;
        }
    }

    /** Increment scan depth. */
    $dpt++;
    /** Controls indenting relating to scan depth for normal logging and for CLI-mode scanning. */
    $lnap = str_pad('> ', ($dpt + 1), '-', STR_PAD_LEFT);

    /**
     * If the scan target is an array, iterate through the array and recurse
     * the recursor with each array element.
     */
    if (is_array($f)) {
        foreach ($f as &$Current) {
            try {
                $Current = $phpMussel['Recursor']($Current, $n, false, $dpt, $Current);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return ($n && $zz) ? $phpMussel['implode_md']($f) : $f;
    }

    $ofn = $phpMussel['prescan_decode']($ofn);
    $ofnSafe = urlencode($ofn);

    /**
     * If the scan target is a directory, iterate through the directory
     * contents and recurse the recursor with these contents.
     */
    if (is_dir($f)) {
        if (!is_readable($f)) {
            $phpMussel['memCache']['scan_errors']++;
            return !$n ? 0 : $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                sprintf($phpMussel['lang']['failed_to_access'], $ofn)
            ) . "\n";
        }
        $Dir = $phpMussel['DirectoryRecursiveList']($f);
        foreach ($Dir as &$Sub) {
            try {
                $Sub = $phpMussel['Recursor']($f . '/' . $Sub, $n, false, $dpt, $Sub);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return ($n && $zz) ? $phpMussel['implode_md']($Dir) : $Dir;
    }

    /** Increment our scanned files/objects tally. */
    $phpMussel['memCache']['objects_scanned']++;
    /** Define file phase. */
    $phpMussel['memCache']['phase'] = 'file';
    /**
     * Indicates whether the file/object being scanned is a part of a
     * container (e.g., an OLE object, ZIP file, TAR, PHAR, etc).
     */
    $phpMussel['memCache']['container'] = 'none';
    /** Indicates whether the file/object being scanned is an OLE object. */
    $phpMussel['memCache']['file_is_ole'] = false;

    /** Fetch the greylist if it hasn't already been fetched. */
    if (!isset($phpMussel['memCache']['greylist'])) {
        if (!file_exists($phpMussel['Vault'] . 'greylist.csv')) {
            $phpMussel['memCache']['greylist'] = ',';
            $Handle = fopen($phpMussel['Vault'] . 'greylist.csv', 'a');
            fwrite($Handle, ',');
            fclose($Handle);
        } else {
            $phpMussel['memCache']['greylist'] = $phpMussel['ReadFile']($phpMussel['Vault'] . 'greylist.csv');
        }
    }

    /** Plugin hook: "before_scan". */
    $phpMussel['Execute_Hook']('before_scan');

    $fnCRC = hash('crc32b', $ofn);

    /** Kill it here if the scan target isn't a valid file. */
    if (!$f || !$d = is_file($f)) {
        return (!$n) ? 0 :
            $lnap . $phpMussel['lang']['scan_checking'] . ' \'' . $ofn .
            '\' (FN: ' . $fnCRC . "):\n-" . $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                $phpMussel['lang']['invalid_file']
            ) . "\n";
    }

    $fS = filesize($f);
    if ($phpMussel['Config']['files']['filesize_limit'] > 0) {
        if ($fS > $phpMussel['ReadBytes']($phpMussel['Config']['files']['filesize_limit'])) {
            if (!$phpMussel['Config']['files']['filesize_response']) {
                return (!$n) ? 1 :
                    $lnap . $phpMussel['lang']['scan_checking'] . ' \'' .
                    $ofn . '\' (FN: ' . $fnCRC . "):\n-" . $lnap .
                    $phpMussel['lang']['ok'] . ' (' .
                    $phpMussel['lang']['filesize_limit_exceeded'] . ").\n";
            }
            $phpMussel['killdata'] .= '--FILESIZE-LIMIT--------NO-HASH-:' . $fS . ':' . $ofn . "\n";
            $phpMussel['whyflagged'] .= sprintf(
                $phpMussel['lang']['_exclamation'],
                $phpMussel['lang']['filesize_limit_exceeded'] . ' (' . $ofnSafe . ')'
            );
            if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
                unlink($f);
            }
            return (!$n) ? 2 :
                $lnap . $phpMussel['lang']['scan_checking'] . ' \'' . $ofn .
                '\' (FN: ' . $fnCRC . "):\n-" . $lnap .
                $phpMussel['lang']['filesize_limit_exceeded'] .
                $phpMussel['lang']['_fullstop_final'] . "\n";
        }
    }
    if (!$phpMussel['Config']['attack_specific']['allow_leading_trailing_dots'] && (
        substr($ofn, 0, 1) === '.' || substr($ofn, -1) === '.'
    )) {
        $phpMussel['killdata'] .= '--FILENAME-MANIPULATION-NO-HASH-:' . $fS . ':' . $ofn . "\n";
        $phpMussel['whyflagged'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            $phpMussel['lang']['scan_filename_manipulation_detected'] . ' (' . $ofnSafe . ')'
        );
        if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
            unlink($f);
        }
        return (!$n) ? 2 :
            $lnap . $phpMussel['lang']['scan_checking'] . ' \'' . $ofn .
            '\' (FN: ' . $fnCRC . "):\n-" . $lnap . sprintf(
                $phpMussel['lang']['_exclamation_final'],
                $phpMussel['lang']['scan_filename_manipulation_detected']
            ) . "\n";
    }
    list($xt, $xts, $gzxt, $gzxts) = $phpMussel['FetchExt']($ofn);
    if (
        substr_count(',' . $phpMussel['Config']['files']['filetype_whitelist'] . ',', ',' . $xt . ',') ||
        substr_count(',' . $phpMussel['Config']['files']['filetype_whitelist'] . ',', ',' . $xts . ',') ||
        substr_count(',' . $phpMussel['Config']['files']['filetype_whitelist'] . ',', ',' . $gzxt . ',') ||
        substr_count(',' . $phpMussel['Config']['files']['filetype_whitelist'] . ',', ',' . $gzxts . ',')
    ) {
        return (!$n) ? 1 :
            $lnap . $phpMussel['lang']['scan_checking'] . ' \'' . $ofn .
            '\' (FN: ' . $fnCRC . "):\n-" . $lnap .
            $phpMussel['lang']['scan_no_problems_found'] . "\n";
    }
    if (
        substr_count(',' . $phpMussel['Config']['files']['filetype_blacklist'] . ',', ',' . $xt . ',') ||
        substr_count(',' . $phpMussel['Config']['files']['filetype_blacklist'] . ',', ',' . $xts . ',') ||
        substr_count(',' . $phpMussel['Config']['files']['filetype_blacklist'] . ',', ',' . $gzxt . ',') ||
        substr_count(',' . $phpMussel['Config']['files']['filetype_blacklist'] . ',', ',' . $gzxts . ',')
    ) {
        $phpMussel['killdata'] .= '--FILETYPE-BLACKLISTED--NO-HASH-:' . $fS . ':' . $ofn . "\n";
        $phpMussel['whyflagged'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            $phpMussel['lang']['filetype_blacklisted'] . ' (' . $ofnSafe . ')'
        );
        if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
            unlink($f);
        }
        return (!$n) ? 2 :
            $lnap . $phpMussel['lang']['scan_checking'] . ' \'' .
            $ofn . '\' (FN: ' . $fnCRC . "):\n-" . $lnap .
            $phpMussel['lang']['filetype_blacklisted'] .
            $phpMussel['lang']['_fullstop_final'] . "\n";
    }
    if (
        $phpMussel['Config']['files']['filetype_greylist'] &&
        !substr_count(',' . $phpMussel['Config']['files']['filetype_greylist'] . ',', ',' . $xt . ',') &&
        !substr_count(',' . $phpMussel['Config']['files']['filetype_greylist'] . ',', ',' . $xts . ',') &&
        !substr_count(',' . $phpMussel['Config']['files']['filetype_greylist'] . ',', ',' . $gzxt . ',') &&
        !substr_count(',' . $phpMussel['Config']['files']['filetype_greylist'] . ',', ',' . $gzxts . ',')
    ) {
        $phpMussel['killdata'] .= '----FILETYPE--NOT-GREYLISTED----:' . $fS . ':' . $ofn . "\n";
        $phpMussel['whyflagged'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            $phpMussel['lang']['filetype_blacklisted'] . ' (' . $ofnSafe . ')'
        );
        if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
            unlink($f);
        }
        return (!$n) ? 2 :
            $lnap . $phpMussel['lang']['scan_checking'] . ' \'' .
            $ofn . '\' (FN: ' . $fnCRC . "):\n-" . $lnap .
            $phpMussel['lang']['filetype_blacklisted'] .
            $phpMussel['lang']['_fullstop_final'] . "\n";
    }
    $in = $phpMussel['ReadFile']($f, (
        $phpMussel['Config']['attack_specific']['scannable_threshold'] > 0 &&
        $fS > $phpMussel['ReadBytes']($phpMussel['Config']['attack_specific']['scannable_threshold'])
    ) ? $phpMussel['ReadBytes']($phpMussel['Config']['attack_specific']['scannable_threshold']) : $fS, true);
    $fdCRC = hash('crc32b', $in);

    /** Check for non-image items. */
    if (!empty($in) && $phpMussel['Config']['compatibility']['only_allow_images'] && !$phpMussel['Indicator-Image']($xt, bin2hex(substr($in, 0, 16)))) {
        $phpMussel['killdata'] .= md5($in) . ':' . $fS . ':' . $ofn . "\n";
        $phpMussel['whyflagged'] .= sprintf(
            $phpMussel['lang']['_exclamation'],
            $phpMussel['lang']['only_allow_images'] . ' (' . $ofnSafe . ')'
        );
        if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
            unlink($f);
        }
        return (!$n) ? 2 :
            $lnap . $phpMussel['lang']['scan_checking'] . ' \'' .
            $ofn . '\' (FN: ' . $fnCRC . '; FD: ' . $fdCRC . "):\n-" .
            $lnap . $phpMussel['lang']['only_allow_images'] .
            $phpMussel['lang']['_fullstop_final'] . "\n";
    }

    /** Send the file/object being scanned to the data handler. */
    try {
        $z = $phpMussel['DataHandler']($in, $dpt, $ofn);
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }

    /** Executed if there were any problems or anything detected: */
    if ($z[0] !== 1) {
        if ($z[0] === 2) {
            if (
                $phpMussel['Config']['general']['quarantine_key'] &&
                !$phpMussel['Config']['general']['honeypot_mode'] &&
                strlen($in) < $phpMussel['ReadBytes']($phpMussel['Config']['general']['quarantine_max_filesize'])
            ) {
                $qfu =
                    $phpMussel['Time'] .
                    '-' .
                    md5($phpMussel['Config']['general']['quarantine_key'] . $fdCRC . $phpMussel['Time']);
                $phpMussel['Quarantine'](
                    $in,
                    $phpMussel['Config']['general']['quarantine_key'],
                    $_SERVER[$phpMussel['Config']['general']['ipaddr']],
                    $qfu
                );
                $phpMussel['killdata'] .= sprintf($phpMussel['lang']['quarantined_as'], $qfu);
            }
        }
        if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
            unlink($f);
        }
        return (!$n) ? $z[0] :
            $lnap . $phpMussel['lang']['scan_checking'] .
            ' \'' . $ofn . '\' (FN: ' . $fnCRC . '; FD: ' . $fdCRC . "):\n" .
            $z[1];
    }

    $x =
        $lnap . $phpMussel['lang']['scan_checking'] . ' \'' .
        $ofn . '\' (FN: ' . $fnCRC . '; FD: ' . $fdCRC . "):\n-" . $lnap .
        $phpMussel['lang']['scan_no_problems_found'] . "\n";
    $r = 1;

    /**
     * Begin archive phase.
     * Note: Archive phase will only occur when "check_archives" is enabled and
     * when no problems were detected with the file/object being scanned by
     * this stage of the scan.
     */
    if (
        $phpMussel['Config']['files']['check_archives'] &&
        !empty($in) &&
        $phpMussel['Config']['files']['max_recursion'] > 1
    ) {
        /** Define archive phase. */
        $phpMussel['memCache']['phase'] = 'archive';

        /** Reset container definition. */
        $phpMussel['memCache']['container'] = 'none';

        /** Try to generate symlinks if enabled and useful for the instance. */
        if ($phpMussel['Config']['general']['allow_symlinks'] && strpos($f, '.') === false) {
            $Try = $phpMussel['cachePath'] . bin2hex($f) . '.tmp';
            $ReadFrom = symlink($f, $Try) ? $Try : $f;
            unset($Try);
        } else {
            $ReadFrom = $f;
        }

        /** Set appropriate container definitions. */
        if (substr($in, 0, 2) === 'PK') {
            if ($xt === 'ole') {
                $PharType = 'OLE';
            } elseif ($xt === 'smpk') {
                $PharType = 'SMPTE';
            } elseif ($xt === 'xpi') {
                $PharType = 'XPInstall';
            } elseif ($xts === 'app*') {
                $PharType = 'App';
            } elseif (substr_count(
                ',docm,docx,dotm,dotx,potm,potx,ppam,ppsm,ppsx,pptm,pptx,xlam,xlsb,xlsm,x' .
                'lsx,xltm,xltx,', ',' . $xt . ','
            )) {
                $PharType = 'OpenXML';
            } elseif (substr_count(
                ',odc,odf,odg,odm,odp,ods,odt,otg,oth,otp,ots,ott,', ',' . $xt . ','
            ) || $xts === 'fod*') {
                $PharType = 'OpenDocument';
            } elseif (substr_count(',opf,epub,', ',' . $xt . ',')) {
                $PharType = 'EPUB';
            } else {
                $PharType = 'ZIP';
                $phpMussel['memCache']['container'] = 'zipfile';
            }
            if ($PharType !== 'ZIP') {
                $phpMussel['memCache']['file_is_ole'] = true;
                $phpMussel['memCache']['container'] = 'pkfile';
            }
        } elseif (
            substr($in, 257, 6) === "ustar\x00" ||
            substr_count(',tar,tgz,tbz,tlz,tz,', ',' . $xt . ',')
        ) {
            $PharType = 'TarFile';
            $phpMussel['memCache']['container'] = 'tarfile';
        } elseif (
            substr($in, 0, 4) === 'Rar!' ||
            bin2hex(substr($in, 0, 4)) === '52457e5e'
        ) {
            $PharType = 'RarFile';
            $phpMussel['memCache']['container'] = 'rarfile';
        } else {
            $PharType = '';
        }

        /** Set default state for our array of phar'd files. */
        $PharData = [];

        /**
         * Alternative archive analysis method to account for the extensionless phar file bug.
         * - https://bugs.php.net/bug.php?id=76061
         * - https://github.com/phpMussel/phpMussel/issues/155
         */
        if (substr($in, 0, 2) === 'PK' && strpos($ReadFrom, '.') === false) {
            $x .= '-' . $lnap . $phpMussel['lang']['scan_reading'] . ' \'' . $ofn . "' (PHAR):\n";
            $PharIter = 0;
            $Handle = zip_open($ReadFrom);
            if (is_resource($Handle)) {
                while (($EntryID = zip_read($Handle)) && is_resource($EntryID) && zip_entry_open($Handle, $EntryID, 'rb')) {
                    $PharData[$PharIter] = ['DoScan' => true, 'Depth' => 1, 'Path' => zip_entry_name($EntryID)];
                    $PharData[$PharIter]['Data'] = zip_entry_read($EntryID, zip_entry_filesize($EntryID));
                    $PharData[$PharIter]['Filename'] = $phpMussel['SubstrAfterFinalSlash']($PharData[$PharIter]['Path']);
                    $PharData[$PharIter]['Path'] = ltrim($phpMussel['RemoveLeadMatch']($ofn, $PharData[$PharIter]['Path']), "\\/");
                    $PharData[$PharIter]['ItemRef'] = $ofn . '>' . $PharData[$PharIter]['Path'];
                    zip_entry_close($EntryID);
                    $PharIter++;
                }
                unset($EntryID);
                zip_close($Handle);
            }
            unset($Handle, $PharIter);
        }

        /** Check if pharable, and if so, generate an array of the contents. */
        elseif (is_dir('phar://' . $ReadFrom) && is_readable('phar://' . $ReadFrom)) {
            $x .= '-' . $lnap . $phpMussel['lang']['scan_reading'] . ' \'' . $ofn . "' (PHAR):\n";
            $PharData = explode("\n", $phpMussel['BuildPharList']($ReadFrom, $dpt)) ?: [];

            /** Iterate through each item in the pharable file/object. */
            foreach ($PharData as &$ThisPhar) {
                if (empty($ThisPhar)) {
                    continue;
                }
                $ThisPhar = [
                    'DoScan' => true,
                    'Depth' => $phpMussel['substrbf']($ThisPhar, ' '),
                    'Path' => $phpMussel['substraf']($ThisPhar, ' ')
                ];
                $ThisPhar['Data'] = $phpMussel['ReadFile']('phar://' . $ThisPhar['Path']);
                $ThisPhar['Filename'] = $phpMussel['SubstrAfterFinalSlash']($ThisPhar['Path']);
                $ThisPhar['Path'] = ltrim($phpMussel['RemoveLeadMatch']($ofn, $ThisPhar['Path']), "\\/");
                $ThisPhar['ItemRef'] = $ofn . '>' . $ThisPhar['Path'];
            }
        }

        /** Default to the parent if PharData is empty. */
        if (empty($PharData)) {
            $PharData = [0 => ['DoScan' => false, 'Depth' => 1, 'Data' => $in]];
            $PharData[0]['Filename'] = $phpMussel['SubstrAfterFinalSlash']($ReadFrom);
            $PharData[0]['Path'] = ltrim($phpMussel['RemoveLeadMatch']($ofn, $f), "\\/");
            $PharData[0]['ItemRef'] = $ofn . '>' . $PharData[0]['Path'];
        }

        /** And now we begin processing our pharable contents array. */
        foreach ($PharData as &$ThisPhar) {
            if (empty($ThisPhar)) {
                continue;
            }
            $ThisPhar['Filesize'] = strlen($ThisPhar['Data']);
            $ThisPhar['MD5'] = md5($ThisPhar['Data']);
            $ThisPhar['NameCRC32'] = hash('crc32b', $ThisPhar['Filename']);
            $ThisPhar['DataCRC32'] = hash('crc32b', $ThisPhar['Data']);
            if (
                $phpMussel['Config']['files']['block_encrypted_archives'] &&
                substr($ThisPhar['Data'], 0, 2) === 'PK'
            ) {
                /**
                 * ZIP File Format Specification:
                 * - https://pkware.cachefly.net/webdocs/casestudies/APPNOTE.TXT
                 */
                $ThisPhar['ZipBits'] = $phpMussel['explode_bits'](substr($ThisPhar['Data'], 6, 2));
                if ($ThisPhar['ZipBits'] && $ThisPhar['ZipBits'][7]) {
                    /** Encryption detected. */
                    $r = 2;
                    $phpMussel['killdata'] .= $ThisPhar['MD5'] . ':' . $ThisPhar['Filesize'] . ':' . $ThisPhar['ItemRef'] . "\n";
                    $phpMussel['whyflagged'] .= sprintf(
                        $phpMussel['lang']['_exclamation'],
                        $phpMussel['lang']['encrypted_archive'] . ' (' . urlencode($ThisPhar['ItemRef']) . ')'
                    );
                    $x .=
                        '-' . $lnap . $phpMussel['lang']['scan_checking'] .
                        ' \'' . $ThisPhar['ItemRef'] . '\' (FN: ' .
                        $ThisPhar['NameCRC32'] . '; FD: ' .
                        $ThisPhar['DataCRC32'] . "):\n--" . $lnap .
                        $phpMussel['lang']['encrypted_archive'] .
                        $phpMussel['lang']['_fullstop_final'] . "\n";
                    break;
                }
            }
            if ($ThisPhar['DoScan']) {
                $x .=
                    '-' . $lnap . $phpMussel['lang']['scan_checking'] .
                    ' \'' . $ThisPhar['ItemRef'] .
                    '\' (FN: ' . $ThisPhar['NameCRC32'] .
                    '; FD: ' . $ThisPhar['DataCRC32'] . "):\n";
                try {
                    list($r, $x) = $phpMussel['MetaDataScan'](
                        $ThisPhar['ItemRef'],
                        $ThisPhar['Filename'],
                        $ThisPhar['Data'],
                        $ThisPhar['Depth'],
                        '-' . $lnap,
                        $r,
                        $x
                    );
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
                if ($r !== 1) {
                    break;
                }
                $x .= '--' . $lnap . $phpMussel['lang']['scan_no_problems_found'] . "\n";
            }
            $ScanDepth = 0;
            while (true) {
                if ($ScanDepth > $phpMussel['Config']['files']['max_recursion']) {
                    $r = 2;
                    $phpMussel['killdata'] .= $ThisPhar['MD5'] . ':' . $ThisPhar['Filesize'] . ':' . $ThisPhar['ItemRef'] . "\n";
                    $phpMussel['whyflagged'] .= sprintf(
                        $phpMussel['lang']['_exclamation'],
                        $phpMussel['lang']['recursive'] . ' (' . $ofnSafe . ')'
                    );
                    $x .=
                        '-' . $lnap . $phpMussel['lang']['scan_checking'] .
                        ' \'' . $ThisPhar['ItemRef'] . '\' (FN: ' .
                        $ThisPhar['NameCRC32'] . '; FD: ' .
                        $ThisPhar['DataCRC32'] . "):\n--" .
                        $lnap . $phpMussel['lang']['recursive'] .
                        $phpMussel['lang']['_fullstop_final'] . "\n";
                    break 2;
                }
                $zDo = false;
                $ThisPhar['Indent'] = str_repeat('-', $ScanDepth + $dpt) . $lnap;
                $xtt = (strpos($ThisPhar['Filename'], '.') !== false) ? [
                    substr($ThisPhar['Filename'], -3),
                    substr($ThisPhar['Filename'], -4),
                    substr($ThisPhar['Filename'], -7, 4),
                    substr($ThisPhar['Filename'], -8, 4)
                ] : false;
                $GZable = false;
                $BZable = false;
                $LZable = false;
                if ($phpMussel['substr_compare_hex']($ThisPhar['Data'], 0, 2, '1f8b', true)) {
                    $GZable = true;
                } elseif ($phpMussel['substr_compare_hex']($ThisPhar['Data'], 0, 3, '425a68', true)) {
                    $BZable = true;
                } elseif (!empty($xtt) && !$zDo && (
                    substr_count(',.gz,', ',' . $xtt[0] . ',') ||
                    substr_count(',.tgz,', ',' . $xtt[1] . ',')
                )) {
                    $GZable = true;
                } elseif (!empty($xtt) && !$zDo && (
                    substr_count(',.bz,', ',' . $xtt[0] . ',') ||
                    substr_count(',.bz2,.tbz,', ',' . $xtt[1] . ',')
                )) {
                    $BZable = true;
                } elseif (!empty($xtt) && !$zDo && (
                    substr_count(',.lz,', ',' . $xtt[0] . ',') ||
                    substr_count(',.lha,.lzh,.lzo,.lzw,.lzx,.tlz,', ',' . $xtt[1] . ',')
                )) {
                    $LZable = true;
                } elseif (!$zDo && (
                    substr($ThisPhar['Data'], 257, 6) === "ustar\x00" || (
                        !empty($xtt) && (
                            substr_count(',.tar,.tgz,.tbz,.tlz,', ',' . $xtt[1] . ',') ||
                            substr_count(',.tar,.tgz,.tbz,.tlz,', ',' . $xtt[2] . ',') ||
                            substr_count(',.tar,.tgz,.tbz,.tlz,', ',' . $xtt[3] . ',')
                        )
                    )
                )) {
                    /** Allows recursion for TAR files. */
                    $x .=
                        $ThisPhar['Indent'] .
                        $phpMussel['lang']['scan_reading'] . ' \'' .
                        $ThisPhar['ItemRef'] . "' (TAR):\n";
                    $TarFile = ['Offset' => 0];
                    while (true) {
                        if (($TarFile['Offset'] + 1024) > $ThisPhar['Filesize']) {
                            break;
                        }
                        $TarFile['File'] = [
                            'Filename' => preg_replace('/[^\x20-\xff]/', '', substr($ThisPhar['Data'], $TarFile['Offset'], 100)),
                            'Filesize' => octdec(preg_replace('/\D/', '', substr($ThisPhar['Data'], $TarFile['Offset'] + 124, 12))),
                        ];
                        if ($TarFile['File']['Filesize'] < 0) {
                            $r = 2;
                            $phpMussel['killdata'] .= $TarFile['File']['MD5'] . ':' . $TarFile['File']['Filesize'] . ':' . $ThisPhar['ItemRef'] . "\n";
                            $phpMussel['whyflagged'] .= sprintf(
                                $phpMussel['lang']['_exclamation'],
                                $phpMussel['lang']['scan_tampering'] . ' (' . urlencode($ThisPhar['ItemRef']) . ')'
                            );
                            $x .= '-' . $ThisPhar['Indent'] . sprintf(
                                $phpMussel['lang']['_exclamation_final'],
                                $phpMussel['lang']['scan_tampering']
                            ) . "\n";
                            break;
                        }
                        $TarFile['File']['Directory'] = (
                            substr($TarFile['File']['Filename'], -1, 1) === '/' &&
                            $TarFile['File']['Filesize'] === 0
                        );
                        $TarFile['File']['Blocks'] = ceil($TarFile['File']['Filesize'] / 512) + 1;
                        if ($TarFile['File']['Directory']) {
                            $TarFile['Offset'] += $TarFile['File']['Blocks'] * 512;
                            continue;
                        }
                        if ($TarFile['File']['Filename']) {
                            if (substr_count($TarFile['File']['Filename'], "\\")) {
                                $TarFile['File']['Filename'] =
                                    $phpMussel['substral']($TarFile['File']['Filename'], "\\") ;
                            }
                            if (substr_count($TarFile['File']['Filename'], '/')) {
                                $TarFile['File']['Filename'] =
                                    $phpMussel['substral']($TarFile['File']['Filename'], '/') ;
                            }
                        }
                        $TarFile['File']['Data'] =
                            substr($ThisPhar['Data'], $TarFile['Offset'] + 512, $TarFile['File']['Filesize']);
                        if (empty($TarFile['File']['Data'])) {
                            break;
                        }
                        $TarFile['File']['MD5'] = md5($TarFile['File']['Data']);
                        if (!$TarFile['File']['Filename']) {
                            $r = 2;
                            $phpMussel['killdata'] .= $TarFile['File']['MD5'] . ':' . $TarFile['File']['Filesize'] . ":MISSING-FILENAME\n";
                            $phpMussel['whyflagged'] .= sprintf(
                                $phpMussel['lang']['_exclamation'],
                                $phpMussel['lang']['scan_missing_filename']
                            );
                            $x .= '-' . $ThisPhar['Indent'] . sprintf(
                                $phpMussel['lang']['_exclamation_final'],
                                $phpMussel['lang']['scan_missing_filename']
                            ) . "\n";
                            break;
                        }
                        $phpMussel['memCache']['objects_scanned']++;
                        try {
                            list($r, $x) = $phpMussel['MetaDataScan'](
                                $ThisPhar['ItemRef'] . '>' . $TarFile['File']['Filename'],
                                $TarFile['File']['Filename'],
                                $TarFile['File']['Data'],
                                $dpt,
                                $ThisPhar['Indent'],
                                $r,
                                $x
                            );
                        } catch (\Exception $e) {
                            throw new \Exception($e->getMessage());
                        }
                        $TarFile['File']['DataCRC32'] = hash('crc32b', $TarFile['File']['Data']);
                        $TarFile['File']['NameCRC32'] = hash('crc32b', $TarFile['File']['Filename']);
                        $TarFile['Offset'] += $TarFile['File']['Blocks'] * 512;
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_checking'] . ' \'' .
                            $ThisPhar['ItemRef'] . '>' . $TarFile['File']['Filename'] .
                            '\' (FN: ' . $TarFile['File']['NameCRC32'] . '; FD: ' .
                            $TarFile['File']['DataCRC32'] . "):\n";
                        if ($r === 1) {
                            $x .=
                                '-' . $ThisPhar['Indent'] .
                                $phpMussel['lang']['scan_no_problems_found'] . "\n";
                        }
                    }
                    $TarFile = '';
                }
                if ($GZable) {
                    if (!function_exists('gzdecode')) {
                        $phpMussel['memCache']['scan_errors']++;
                        if (!$phpMussel['Config']['signatures']['fail_extensions_silently']) {
                            $r = -1;
                            $phpMussel['killdata'] .=
                                $ThisPhar['MD5'] . ':' .
                                $ThisPhar['Filesize'] . ':' .
                                $ThisPhar['ItemRef'] . "\n";
                            $phpMussel['whyflagged'] .= $phpMussel['lang']['scan_extensions_missing'] . ' ';
                        }
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_reading'] . ' \'' .
                            $ThisPhar['ItemRef'] . "' (GZIP):\n-" .
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_extensions_missing'] . "\n";
                        break;
                    }
                    if (!$ThisPhar['Data'] = gzdecode($ThisPhar['Data'])) {
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_reading'] . ' \'' .
                            $ThisPhar['ItemRef'] . "' (GZIP):\n-" .
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_not_archive'] . "\n";
                        break;
                    }
                    $x .=
                        $ThisPhar['Indent'] .
                        $phpMussel['lang']['scan_reading'] . ' \'' .
                        $ThisPhar['ItemRef'] . "' (GZIP):\n";
                    $zDo = true;
                } elseif ($BZable) {
                    if (!function_exists('bzdecompress')) {
                        $phpMussel['memCache']['scan_errors']++;
                        if (!$phpMussel['Config']['signatures']['fail_extensions_silently']) {
                            $r = -1;
                            $phpMussel['killdata'] .=
                                $ThisPhar['MD5'] . ':' .
                                $ThisPhar['Filesize'] . ':' .
                                $ThisPhar['ItemRef'] . "\n";
                            $phpMussel['whyflagged'] .= $phpMussel['lang']['scan_extensions_missing'] . ' ';
                        }
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_reading'] . ' \'' .
                            $ThisPhar['ItemRef'] . "' (BZIP2):\n-" .
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_extensions_missing'] . "\n";
                        break;
                    }
                    if (!$ThisPhar['Data'] = bzdecompress($ThisPhar['Data'])) {
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_reading'] . ' \'' .
                            $ThisPhar['ItemRef'] . "' (BZIP2):\n-" .
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_not_archive'] . "\n";
                        break;
                    }
                    $x .=
                        $ThisPhar['Indent'] .
                        $phpMussel['lang']['scan_reading'] . ' \'' .
                        $ThisPhar['ItemRef'] . "' (BZIP2):\n";
                    $zDo = true;
                } elseif ($LZable) {
                    if (!function_exists('lzf_decompress')) {
                        $phpMussel['memCache']['scan_errors']++;
                        if (!$phpMussel['Config']['signatures']['fail_extensions_silently']) {
                            $r = -1;
                            $phpMussel['killdata'] .=
                                $ThisPhar['MD5'] . ':' .
                                $ThisPhar['Filesize'] . ':' .
                                $ThisPhar['ItemRef'] . "\n";
                            $phpMussel['whyflagged'] .= $phpMussel['lang']['scan_extensions_missing'] . ' ';
                        }
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_reading'] . ' \'' .
                            $ThisPhar['ItemRef'] . "' (LZF):\n-" .
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_extensions_missing'] . "\n";
                        break;
                    }
                    if (!$ThisPhar['Data'] = lzf_decompress($ThisPhar['Data'])) {
                        $x .=
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_reading'] . ' \'' .
                            $ThisPhar['ItemRef'] . "' (LZF):\n-" .
                            $ThisPhar['Indent'] .
                            $phpMussel['lang']['scan_not_archive'] . "\n";
                        break;
                    }
                    $x .=
                        $ThisPhar['Indent'] .
                        $phpMussel['lang']['scan_reading'] . ' \'' .
                        $ThisPhar['ItemRef'] . "' (LZF):\n";
                    $zDo = true;
                }
                if ($zDo) {
                    $ScanDepth++;
                    if (substr_count($ThisPhar['Filename'], '.')) {
                        $ThisPhar['Filename'] = $phpMussel['substrbl']($ThisPhar['Filename'], '.');
                    }
                    if (substr_count($ThisPhar['Filename'], "\\")) {
                        $ThisPhar['Filename'] = $phpMussel['substral']($ThisPhar['Filename'], "\\");
                    }
                    if (substr_count($ThisPhar['Filename'], '/')) {
                        $ThisPhar['Filename'] = $phpMussel['substral']($ThisPhar['Filename'], '/');
                    }
                    $ThisPhar['Filesize'] = strlen($ThisPhar['Data']);
                    $ThisPhar['ItemRef'] .= '>' . $ThisPhar['Filename'];
                    $ThisPhar['NameCRC32'] = hash('crc32b', $ThisPhar['Filename']);
                    $ThisPhar['DataCRC32'] = hash('crc32b', $ThisPhar['Data']);
                    $x .=
                        $ThisPhar['Indent'] .
                        $phpMussel['lang']['scan_checking'] .
                        ' \'' . $ThisPhar['ItemRef'] .
                        '\' (FN: ' . $ThisPhar['NameCRC32'] .
                        '; FD: ' . $ThisPhar['DataCRC32'] . "):\n";
                    try {
                        list($r, $x) = $phpMussel['MetaDataScan'](
                            $ThisPhar['ItemRef'],
                            $ThisPhar['Filename'],
                            $ThisPhar['Data'],
                            $ThisPhar['Depth'] + $ScanDepth + $dpt,
                            $ThisPhar['Indent'],
                            $r,
                            $x
                        );
                    } catch (\Exception $e) {
                        throw new \Exception($e->getMessage());
                    }
                    if ($r !== 1) {
                        break 2;
                    }
                    $x .=
                        $ThisPhar['Indent'] .
                        $phpMussel['lang']['scan_no_problems_found'] .
                        "\n";
                    continue;
                }
                break;
            }
        }

        /** Remove old symlinks. */
        if ($phpMussel['Config']['general']['allow_symlinks'] && $ReadFrom !== $f && is_link($ReadFrom)) {
            unlink($ReadFrom);
        }

        /** Cleanup. */
        unset($ThisPhar, $PharData, $PharType, $ReadFrom);
    }
    if ($r === 2) {
        if (
            $phpMussel['Config']['general']['quarantine_key'] &&
            !$phpMussel['Config']['general']['honeypot_mode'] &&
            strlen($in) < $phpMussel['ReadBytes']($phpMussel['Config']['general']['quarantine_max_filesize'])
        ) {
            $qfu = $phpMussel['Time'] . '-' . md5(
                $phpMussel['Config']['general']['quarantine_key'] . $fdCRC . $phpMussel['Time']
            );
            $phpMussel['Quarantine'](
                $in,
                $phpMussel['Config']['general']['quarantine_key'],
                $_SERVER[$phpMussel['Config']['general']['ipaddr']],
                $qfu
            );
            $phpMussel['killdata'] .= sprintf($phpMussel['lang']['quarantined_as'], $qfu);
        }
    }
    if ($r !== 1 && $phpMussel['Config']['general']['delete_on_sight'] && is_readable($f)) {
        unlink($f);
    }
    return !$n ? $r : $x;
};

/**
 * Forks the PHP process when scanning in CLI mode. This ensures that if PHP
 * crashes during scanning, phpMussel can continue to scan any remaining items
 * queued for scanning (because if the parent process handles the scan queue
 * and the child process handles the actual scanning of each item queued for
 * scanning, if the child process crashes, the parent process can simply create
 * a new child process to continue iterating through the queue).
 *
 * There are some additional benefits to be had by scanning in this way, such
 * as the ability to kill a child process, responsible for the actual scanning,
 * and yet still have the results of this scanning logged (which, if killed in
 * this way prior to completing the scan, would likely involve some type of
 * error).
 *
 * @param string $f The name of the item to be scanned, with path included.
 * @param string $ofn The name of the item to be scanned, without any path
 *      included (so, just the name by itself).
 * @return string The scan results, piped back to the parent from the child
 *      process and returned to the calling function as a string.
 */
$phpMussel['Fork'] = function ($f = '', $ofn = '') use (&$phpMussel) {
    $pf = popen(
        $phpMussel['Mussel_PHP'] . ' "' . $phpMussel['Vault'] .
        '../loader.php" "cli_scan" "' . $f . '" "' . $ofn . '"',
        'r'
    );
    $s = '';
    while ($x = fgets($pf)) {
        $s .= $x;
    }
    pclose($pf);
    return $s;
};

/** Assigns an array to use for dumping scan debug information (optional). */
$phpMussel['Set-Scan-Debug-Array'] = function (&$Var) use (&$phpMussel) {
    if (isset($phpMussel['DebugArr'])) {
        unset($phpMussel['DebugArr']);
    }
    if (!is_array($Var)) {
        $Var = [];
    }
    $phpMussel['DebugArr'] = &$Var;
};

/** Destroys the scan debug array (optional). */
$phpMussel['Destroy-Scan-Debug-Array'] = function (&$Var) use (&$phpMussel) {
    unset($phpMussel['DebugArrKey'], $phpMussel['DebugArr']);
    $Var = null;
};

/**
 * The main scan closure, responsible for initialising scans in most
 * circumstances. Should generally be called whenever phpMussel is
 * required by external scripts, apps, CMS, etc.
 *
 * Please refer to Section 3A of the README documentation, "HOW TO USE (FOR WEB
 * SERVERS)", for more information.
 *
 * @param string|array $f Indicates which file, files, directory, or
 *      directories to scan (can be a string, an array, or a multidimensional
 *      array).
 * @param bool $n A boolean, indicating the format for the scan results to be
 *      returned as. False instructs the function to return the results as an
 *      integer; True instructs the function to return the results as human
 *      readable text. Optional; Defaults to false.
 * @param bool $zz A boolean, indicating to the function whether or not arrayed
 *      results should be imploded prior to being returned to the calling
 *      function. False instructs the function to return the arrayed results as
 *      verbatim; True instructs the function to return the arrayed results as
 *      an imploded string. Optional; Defaults to false.
 * @param int $dpt Represents the current depth of recursion from which the
 *      function has been called. This information is used for determining how
 *      far to indent any entries generated for logging (you should never
 *      manually set this parameter yourself).
 * @param string $ofn For the file upload scanning that phpMussel normally
 *      performs by default, this parameter represents the "original filename"
 *      of the file being scanned (the original filename, in this context,
 *      referring to the name supplied by the upload client, as opposed to the
 *      temporary filename assigned by the server or anything else).
 * @return bool|int|string|array The scan results, returned as an array when
 *      the $f parameter is an array and when $n and/or $zz is/are false, and
 *      otherwise returned as per described by the README documentation. The
 *      function may also die the script and return nothing, if something goes
 *      wrong, such as if the function is triggered in the absense of the
 *      required $phpMussel['memCache'] variable being set, and may also return
 *      false, in the absense of the required $phpMussel['HashCache']['Data']
 *      variable being set.
 */
$phpMussel['Scan'] = function ($f = '', $n = false, $zz = false, $dpt = 0, $ofn = '') use (&$phpMussel) {
    if (!isset($phpMussel['memCache'])) {
        throw new \Exception(
            !isset($phpMussel['lang']['required_variables_not_defined']) ?
            '[phpMussel] Required variables aren\'t defined: Can\'t continue.' :
            '[phpMussel] ' . $phpMussel['lang']['required_variables_not_defined']
        );
    }

    /** Prepare signature files for the scan process. */
    if (empty($phpMussel['memCache']['OrganisedSigFiles'])) {
        $phpMussel['OrganiseSigFiles']();
        $phpMussel['memCache']['OrganisedSigFiles'] = true;
    }

    /** Initialise statistics if they've been enabled. */
    $phpMussel['Stats-Initialise']();

    if ($phpMussel['EOF']) {
        $phpMussel['PrepareHashCache']();
    }
    if (!isset($phpMussel['HashCache']['Data'])) {
        return false;
    }
    if (!$ofn) {
        $ofn = $f;
    }
    $xst = time() + ($phpMussel['Config']['general']['timeOffset'] * 60);
    $xst2822 = $phpMussel['TimeFormat']($xst, $phpMussel['Config']['general']['timeFormat']);
    try {
        $r = $phpMussel['Recursor']($f, $n, $zz, $dpt, $ofn);
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }
    $xet = time() + ($phpMussel['Config']['general']['timeOffset'] * 60);
    $xet2822 = $phpMussel['TimeFormat']($xet, $phpMussel['Config']['general']['timeFormat']);

    /** Plugin hook: "after_scan". */
    $phpMussel['Execute_Hook']('after_scan');

    if ($n && !is_array($r)) {
        $r =
            $xst2822 . ' ' . $phpMussel['lang']['started'] .
            $phpMussel['lang']['_fullstop_final'] . "\n" .
            $r . $xet2822 . ' ' . $phpMussel['lang']['finished'] .
            $phpMussel['lang']['_fullstop_final'] . "\n";
        $phpMussel['WriteScanLog']($r);
    }
    if (!isset($phpMussel['SkipSerial'])) {
        $phpMussel['WriteSerial']($xst, $xet);
    }
    if ($phpMussel['EOF']) {
        if ($phpMussel['Config']['general']['scan_cache_expiry'] > 0) {
            foreach ($phpMussel['HashCache']['Data'] as &$phpMussel['ThisItem']) {
                if (is_array($phpMussel['ThisItem'])) {
                    $phpMussel['ThisItem'] = implode(':', $phpMussel['ThisItem']) . ';';
                }
            }
            $phpMussel['SaveCache'](
                'HashCache',
                $phpMussel['Time'] + $phpMussel['Config']['general']['scan_cache_expiry'],
                implode('', $phpMussel['HashCache']['Data'])
            );
            unset($phpMussel['ThisItem'], $phpMussel['HashCache']['Data']);
        }
    }

    /** Register scan event. */
    $phpMussel['Stats-Increment']($phpMussel['EOF'] ? 'API-Events' : 'Web-Events', 1);

    /** Update statistics. */
    if (@$phpMussel['CacheModified']) {
        $phpMussel['Statistics'] = $phpMussel['SaveCache']('Statistics', -1, serialize($phpMussel['Statistics']));
    }

    /** Exit scan process. */
    return $r;
};

/**
 * Writes to the serialized logfile upon scan completion.
 *
 * @param string $StartTime When the scan started.
 * @param string $FinishTime When the scan finished.
 * @return bool True on success; False on failure.
 */
$phpMussel['WriteSerial'] = function ($StartTime = '', $FinishTime = '') use (&$phpMussel) {
    if ($phpMussel['Mussel_sapi']) {
        $Origin = 'CLI';
    } else {
        $Origin = $phpMussel['Config']['legal']['pseudonymise_ip_addresses'] ? $phpMussel['Pseudonymise-IP'](
            $_SERVER[$phpMussel['Config']['general']['ipaddr']]
        ) : $_SERVER[$phpMussel['Config']['general']['ipaddr']];
    }
    $ScanData = empty($phpMussel['whyflagged']) ? $phpMussel['lang']['data_not_available'] : trim($phpMussel['whyflagged']);
    if ($phpMussel['Config']['general']['scan_log_serialized']) {
        if (!isset($phpMussel['memCache']['objects_scanned'])) {
            $phpMussel['memCache']['objects_scanned'] = 0;
        }
        if (!isset($phpMussel['memCache']['detections_count'])) {
            $phpMussel['memCache']['detections_count'] = 0;
        }
        if (!isset($phpMussel['memCache']['scan_errors'])) {
            $phpMussel['memCache']['scan_errors'] = 1;
        }
        $Handle = [
            'Data' => serialize([
                'start_time' => $StartTime ?: (isset($phpMussel['memCache']['start_time']) ? $phpMussel['memCache']['start_time'] : '-'),
                'end_time' => $FinishTime ?: (isset($phpMussel['memCache']['end_time']) ? $phpMussel['memCache']['end_time'] : '-'),
                'origin' => $Origin,
                'objects_scanned' => $phpMussel['memCache']['objects_scanned'],
                'detections_count' => $phpMussel['memCache']['detections_count'],
                'scan_errors' => $phpMussel['memCache']['scan_errors'],
                'detections' => $ScanData
            ]) . "\n",
            'File' => $phpMussel['TimeFormat']($phpMussel['Time'], $phpMussel['Config']['general']['scan_log_serialized'])
        ];
        $WriteMode = (!file_exists($phpMussel['Vault'] . $Handle['File']) || (
            $phpMussel['Config']['general']['truncate'] > 0 &&
            filesize($phpMussel['Vault'] . $Handle['File']) >= $phpMussel['ReadBytes']($phpMussel['Config']['general']['truncate'])
        )) ? 'w' : 'a';
        if ($phpMussel['BuildLogPath']($Handle['File'])) {
            $Stream = fopen($phpMussel['Vault'] . $Handle['File'], $WriteMode);
            fwrite($Stream, $Handle['Data']);
            fclose($Stream);
            if ($WriteMode === 'w') {
                $phpMussel['LogRotation']($phpMussel['Config']['general']['scan_log_serialized']);
            }
            return true;
        }
    }
    return false;
};

/**
 * Writes to the standard scan log upon scan completion.
 *
 * @param string $Data What to write.
 * @return bool True on success; False on failure.
 */
$phpMussel['WriteScanLog'] = function ($Data) use (&$phpMussel) {
    if ($phpMussel['Config']['general']['scan_log']) {
        $File = $phpMussel['TimeFormat']($phpMussel['Time'], $phpMussel['Config']['general']['scan_log']);
        if (!file_exists($phpMussel['Vault'] . $File)) {
            $Data = $phpMussel['safety'] . "\n" . $Data;
        }
        $WriteMode = (!file_exists($phpMussel['Vault'] . $File) || (
            $phpMussel['Config']['general']['truncate'] > 0 &&
            filesize($phpMussel['Vault'] . $File) >= $phpMussel['ReadBytes']($phpMussel['Config']['general']['truncate'])
        )) ? 'w' : 'a';
        if ($phpMussel['BuildLogPath']($File)) {
            $Handle = fopen($phpMussel['Vault'] . $File, 'a');
            fwrite($Handle, $Data);
            fclose($Handle);
            if ($WriteMode === 'w') {
                $phpMussel['LogRotation']($phpMussel['Config']['general']['scan_log']);
            }
            return true;
        }
    }
    return false;
};

/**
 * A simple closure for replacing date/time placeholders with corresponding
 * date/time information. Used by the logfiles and some timestamps.
 *
 * @param int $Time A unix timestamp.
 * @param string|array $In An input or an array of inputs to manipulate.
 * @return string|array The adjusted input(/s).
 */
$phpMussel['TimeFormat'] = function ($Time, $In) use (&$phpMussel) {
    $Time = date('dmYHisDMP', $Time);
    $values = [
        'dd' => substr($Time, 0, 2),
        'mm' => substr($Time, 2, 2),
        'yyyy' => substr($Time, 4, 4),
        'yy' => substr($Time, 6, 2),
        'hh' => substr($Time, 8, 2),
        'ii' => substr($Time, 10, 2),
        'ss' => substr($Time, 12, 2),
        'Day' => substr($Time, 14, 3),
        'Mon' => substr($Time, 17, 3),
        'tz' => substr($Time, 20, 3) . substr($Time, 24, 2),
        't:z' => substr($Time, 20, 6)
    ];
    $values['d'] = (int)$values['dd'];
    $values['m'] = (int)$values['mm'];
    return is_array($In) ? array_map(function ($Item) use (&$values, &$phpMussel) {
        return $phpMussel['ParseVars']($values, $Item);
    }, $In) : $phpMussel['ParseVars']($values, $In);
};

/**
 * Normalises values defined by the YAML closure.
 *
 * @param string|int|bool $Value The value to be normalised.
 * @param int $ValueLen The length of the value to be normalised.
 * @param string|int|bool $ValueLow The value to be normalised, lowercased.
 */
$phpMussel['YAML-Normalise-Value'] = function (&$Value, $ValueLen, $ValueLow) {
    if (substr($Value, 0, 1) === '"' && substr($Value, $ValueLen - 1) === '"') {
        $Value = substr($Value, 1, $ValueLen - 2);
    } elseif (substr($Value, 0, 1) === '\'' && substr($Value, $ValueLen - 1) === '\'') {
        $Value = substr($Value, 1, $ValueLen - 2);
    } elseif ($ValueLow === 'true' || $ValueLow === 'y') {
        $Value = true;
    } elseif ($ValueLow === 'false' || $ValueLow === 'n') {
        $Value = false;
    } elseif (substr($Value, 0, 2) === '0x' && ($HexTest = substr($Value, 2)) && !preg_match('/[^\da-f]/i', $HexTest) && !($ValueLen % 2)) {
        $Value = hex2bin($HexTest);
    } else {
        $ValueInt = (int)$Value;
        if (strlen($ValueInt) === $ValueLen && $Value == $ValueInt && $ValueLen > 1) {
            $Value = $ValueInt;
        }
    }
    if (!$Value) {
        $Value = false;
    }
};

/**
 * A simplified YAML-like parser. Note: This is intended to adequately serve
 * the needs of this package in a way that should feel familiar to users of
 * YAML, but it isn't a true YAML implementation and it doesn't adhere to any
 * specifications, official or otherwise.
 *
 * @param string $In The data to parse.
 * @param array $Arr Where to save the results.
 * @param bool $VM Validator Mode (if true, results won't be saved).
 * @param int $Depth Tab depth (inherited through recursion; ignore it).
 * @return bool Returns false if errors are encountered, and true otherwise.
 */
$phpMussel['YAML'] = function ($In, &$Arr, $VM = false, $Depth = 0) use (&$phpMussel) {
    if (!is_array($Arr)) {
        if ($VM) {
            return false;
        }
        $Arr = [];
    }
    if (!substr_count($In, "\n")) {
        return false;
    }
    $In = str_replace("\r", '', $In);
    $Key = $Value = $SendTo = '';
    $TabLen = $SoL = 0;
    while ($SoL !== false) {
        $ThisLine = (
            ($EoL = strpos($In, "\n", $SoL)) === false
        ) ? substr($In, $SoL) : substr($In, $SoL, $EoL - $SoL);
        $SoL = ($EoL === false) ? false : $EoL + 1;
        $ThisLine = preg_replace(['/#.*$/', '/\s+$/'], '', $ThisLine);
        if (empty($ThisLine) || $ThisLine === "\n") {
            continue;
        }
        $ThisTab = 0;
        while (($Chr = substr($ThisLine, $ThisTab, 1)) && ($Chr === ' ' || $Chr === "\t")) {
            $ThisTab++;
        }
        if ($ThisTab > $Depth) {
            if ($TabLen === 0) {
                $TabLen = $ThisTab;
            }
            $SendTo .= $ThisLine . "\n";
            continue;
        } elseif ($ThisTab < $Depth) {
            return false;
        } elseif (!empty($SendTo)) {
            if (empty($Key)) {
                return false;
            }
            if (!isset($Arr[$Key])) {
                if ($VM) {
                    return false;
                }
                $Arr[$Key] = false;
            }
            if (!$phpMussel['YAML']($SendTo, $Arr[$Key], $VM, $TabLen)) {
                return false;
            }
            $SendTo = '';
        }
        if (substr($ThisLine, -1) === ':') {
            $Key = substr($ThisLine, $ThisTab, -1);
            $KeyLen = strlen($Key);
            $KeyLow = strtolower($Key);
            $phpMussel['YAML-Normalise-Value']($Key, $KeyLen, $KeyLow);
            if (!isset($Arr[$Key])) {
                if ($VM) {
                    return false;
                }
                $Arr[$Key] = false;
            }
        } elseif (substr($ThisLine, $ThisTab, 2) === '- ') {
            $Value = substr($ThisLine, $ThisTab + 2);
            $ValueLen = strlen($Value);
            $ValueLow = strtolower($Value);
            $phpMussel['YAML-Normalise-Value']($Value, $ValueLen, $ValueLow);
            if (!$VM && $ValueLen > 0) {
                $Arr[] = $Value;
            }
        } elseif (($DelPos = strpos($ThisLine, ': ')) !== false) {
            $Key = substr($ThisLine, $ThisTab, $DelPos - $ThisTab);
            $KeyLen = strlen($Key);
            $KeyLow = strtolower($Key);
            $phpMussel['YAML-Normalise-Value']($Key, $KeyLen, $KeyLow);
            if (!$Key) {
                return false;
            }
            $Value = substr($ThisLine, $ThisTab + $KeyLen + 2);
            $ValueLen = strlen($Value);
            $ValueLow = strtolower($Value);
            $phpMussel['YAML-Normalise-Value']($Value, $ValueLen, $ValueLow);
            if (!$VM && $ValueLen > 0) {
                $Arr[$Key] = $Value;
            }
        } elseif (strpos($ThisLine, ':') === false && strlen($ThisLine) > 1) {
            $Key = $ThisLine;
            $KeyLen = strlen($Key);
            $KeyLow = strtolower($Key);
            $phpMussel['YAML-Normalise-Value']($Key, $KeyLen, $KeyLow);
            if (!isset($Arr[$Key])) {
                if ($VM) {
                    return false;
                }
                $Arr[$Key] = false;
            }
        }
    }
    if (!empty($SendTo) && !empty($Key)) {
        if (!isset($Arr[$Key])) {
            if ($VM) {
                return false;
            }
            $Arr[$Key] = [];
        }
        if (!$phpMussel['YAML']($SendTo, $Arr[$Key], $VM, $TabLen)) {
            return false;
        }
    }
    return true;
};

/**
 * Fix incorrect typecasting for some for some variables that sometimes default
 * to strings instead of booleans or integers.
 */
$phpMussel['AutoType'] = function (&$Var, $Type = '') use (&$phpMussel) {
    if ($Type === 'string' || $Type === 'timezone') {
        $Var = (string)$Var;
    } elseif ($Type === 'int' || $Type === 'integer') {
        $Var = (int)$Var;
    } elseif ($Type === 'real' || $Type === 'double' || $Type === 'float') {
        $Var = (real)$Var;
    } elseif ($Type === 'bool' || $Type === 'boolean') {
        $Var = (strtolower($Var) !== 'false' && $Var);
    } elseif ($Type === 'kb') {
        $Var = $phpMussel['ReadBytes']($Var, 1);
    } else {
        $LVar = strtolower($Var);
        if ($LVar === 'true') {
            $Var = true;
        } elseif ($LVar === 'false') {
            $Var = false;
        } elseif ($Var !== true && $Var !== false) {
            $Var = (int)$Var;
        }
    }
};

/**
 * Used to send cURL requests.
 *
 * @param string $URI The resource to request.
 * @param array $Params (Optional) An associative array of key-value pairs to
 *      to send along with the request.
 * @return string The results of the request.
 */
$phpMussel['Request'] = function ($URI, $Params = '', $Timeout = '') use (&$phpMussel) {
    if (!$Timeout) {
        $Timeout = $phpMussel['Timeout'];
    }

    /** Initialise the cURL session. */
    $Request = curl_init($URI);

    $LCURI = strtolower($URI);
    $SSL = (substr($LCURI, 0, 6) === 'https:');

    curl_setopt($Request, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($Request, CURLOPT_HEADER, false);
    if (empty($Params)) {
        curl_setopt($Request, CURLOPT_POST, false);
    } else {
        curl_setopt($Request, CURLOPT_POST, true);
        curl_setopt($Request, CURLOPT_POSTFIELDS, $Params);
    }
    if ($SSL) {
        curl_setopt($Request, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($Request, CURLOPT_SSL_VERIFYPEER, false);
    }
    curl_setopt($Request, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($Request, CURLOPT_MAXREDIRS, 1);
    curl_setopt($Request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Request, CURLOPT_TIMEOUT, $Timeout);
    curl_setopt($Request, CURLOPT_USERAGENT, $phpMussel['ScriptUA']);

    /** Execute and get the response. */
    $Response = curl_exec($Request);

    /** Close the cURL session. */
    curl_close($Request);

    /** Return the results of the request. */
    return $Response;
};

/**
 * Used to generate new salts when necessary, which may be occasionally used by
 * some specific optional peripheral features (note: should not be considered
 * cryptographically secure; especially so for versions of PHP < 7).
 *
 * @return string Salt.
 */
$phpMussel['GenerateSalt'] = function () {
    static $MinLen = 32;
    static $MaxLen = 72;
    static $MinChr = 1;
    static $MaxChr = 255;
    $Salt = '';
    if (function_exists('random_int')) {
        try {
            $Length = random_int($MinLen, $MaxLen);
        } catch (\Exception $e) {
            $Length = rand($MinLen, $MaxLen);
        }
    } else {
        $Length = rand($MinLen, $MaxLen);
    }
    if (function_exists('random_bytes')) {
        try {
            $Salt = random_bytes($Length);
        } catch (\Exception $e) {
            $Salt = '';
        }
    }
    if (empty($Salt)) {
        if (function_exists('random_int')) {
            try {
                for ($Index = 0; $Index < $Length; $Index++) {
                    $Salt .= chr(random_int($MinChr, $MaxChr));
                }
            } catch (\Exception $e) {
                $Salt = '';
                for ($Index = 0; $Index < $Length; $Index++) {
                    $Salt .= chr(rand($MinChr, $MaxChr));
                }
            }
        } else {
            for ($Index = 0; $Index < $Length; $Index++) {
                $Salt .= chr(rand($MinChr, $MaxChr));
            }
        }
    }
    return $Salt;
};

/** Clears expired entries from a list. */
$phpMussel['ClearExpired'] = function (&$List, &$Check) use (&$phpMussel) {
    if ($List) {
        $End = 0;
        while (true) {
            $Begin = $End;
            if (!$End = strpos($List, "\n", $Begin + 1)) {
                break;
            }
            $Line = substr($List, $Begin, $End - $Begin);
            if ($Split = strrpos($Line, ',')) {
                $Expiry = (int)substr($Line, $Split + 1);
                if ($Expiry < $phpMussel['Time']) {
                    $List = str_replace($Line, '', $List);
                    $End = 0;
                    $Check = true;
                }
            }
        }
    }
};

/** Fetch information about signature files and prepare for use with the scan process. */
$phpMussel['OrganiseSigFiles'] = function () use (&$phpMussel) {

    $LastActive = $phpMussel['FetchCache']('Active') ?: '';
    if (empty($phpMussel['Config']['signatures']['Active'])) {
        if ($LastActive) {
            $phpMussel['ClearHashCache']();
        }
        return false;
    }
    if ($phpMussel['Config']['signatures']['Active'] !== $LastActive) {
        $phpMussel['ClearHashCache']();
        $phpMussel['SaveCache']('Active', -1, $phpMussel['Config']['signatures']['Active']);
    }

    $Classes = [
        'General_Command_Detections',
        'Filename',
        'Hash',
        'Standard',
        'Standard_RegEx',
        'Normalised',
        'Normalised_RegEx',
        'HTML',
        'HTML_RegEx',
        'PE_Extended',
        'PE_Sectional',
        'Complex_Extended',
        'URL_Scanner'
    ];

    $List = explode(',', $phpMussel['Config']['signatures']['Active']);
    foreach ($List as $File) {
        $File = (strpos($File, ':') === false) ? $File : substr($File, strpos($File, ':') + 1);
        $Handle = fopen($phpMussel['sigPath'] . $File, 'rb');
        if (fread($Handle, 9) !== 'phpMussel') {
            fclose($Handle);
            continue;
        }
        $Class = fread($Handle, 1);
        fclose($Handle);
        $Nibbles = $phpMussel['split_nibble']($Class);
        if (!empty($Classes[$Nibbles[0]])) {
            if (!isset($phpMussel['memCache'][$Classes[$Nibbles[0]]])) {
                $phpMussel['memCache'][$Classes[$Nibbles[0]]] = ',';
            }
            $phpMussel['memCache'][$Classes[$Nibbles[0]]] .= $File . ',';
        }
    }

};

/** A simple safety wrapper for unpack. */
$phpMussel['UnpackSafe'] = function ($Format, $Data) {
    return (strlen($Data) > 1) ? unpack($Format, $Data) : '';
};

/** A simple safety wrapper for hex2bin. */
$phpMussel['HexSafe'] = function ($Data) use (&$phpMussel) {
    return ($Data && !preg_match('/[^\da-f]/i', $Data) && !(strlen($Data) % 2)) ? $phpMussel['Function']('HEX', $Data) : '';
};

/** If input isn't an array, make it so. Remove empty elements. */
$phpMussel['Arrayify'] = function (&$Input) {
    if (!is_array($Input)) {
        $Input = [$Input];
    }
    $Input = array_filter($Input);
};

/**
 * Read byte value configuration directives as byte values.
 *
 * @param string $In Input.
 * @param int $Mode Operating mode. 0 for true byte values, 1 for validating.
 *      Default is 0.
 * @return string|int Output (depends on operating mode).
 */
$phpMussel['ReadBytes'] = function ($In, $Mode = 0) {
    if (preg_match('/[KMGT][oB]$/i', $In)) {
        $Unit = substr($In, -2, 1);
    } elseif (preg_match('/[KMGToB]$/i', $In)) {
        $Unit = substr($In, -1);
    }
    $Unit = isset($Unit) ? strtoupper($Unit) : 'K';
    $In = (real)$In;
    if ($Mode === 1) {
        return $Unit === 'B' || $Unit === 'o' ? $In . 'B' : $In . $Unit . 'B';
    }
    $Multiply = ['K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776];
    return (int)floor($In * (isset($Multiply[$Unit]) ? $Multiply[$Unit] : 1));
};

/**
 * Improved recursive directory iterator for phpMussel.
 *
 * @param string $Base Directory root.
 * @return array Directory tree.
 */
$phpMussel['DirectoryRecursiveList'] = function ($Base) {
    $Arr = [];
    $Offset = strlen($Base);
    $List = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Base), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($List as $Item => $List) {
        if (preg_match('~^(?:/\.\.|./\.|\.{3})$~', str_replace("\\", '/', substr($Item, -3))) || !is_readable($Item)) {
            continue;
        }
        $Arr[] = substr($Item, $Offset);
    }
    return $Arr;
};

/**
 * Internal aliases for common hash functions (used by CLI).
 *
 * @param string $Alias The alias used.
 * @param string $Data The data to be hashed.
 * @return string The output hash.
 */
$phpMussel['HashAlias'] = function ($Alias, $Data) {
    if ($Alias === 'm' || $Alias === 'md5' || $Alias === 'md5_file') {
        return md5($Data);
    }
    if ($Alias === 'sha1' || $Alias === 'sha1_file') {
        return sha1($Data);
    }
    return '';
};

/**
 * Duplication avoidance (forking the process via recursive CLI mode commands).
 *
 * @param string $Command Command with parameters for the action to be taken.
 * @param callable $Callable Executed normally when not forking the process.
 * @return string Returnable data to be echoed to the CLI output.
 */
$phpMussel['CLI-RecursiveCommand'] = function ($Command, $Callable) use (&$phpMussel) {
    $Params = substr($Command, strlen($phpMussel['cmd']) + 1);
    if (is_dir($Params)) {
        if (!is_readable($Params)) {
            return sprintf($phpMussel['lang']['failed_to_access'], $Params) . "\n";
        }
        $Decal = [':-) - (-:', ':-) \\ (-:', ':-) | (-:', ':-) / (-:'];
        $Frame = 0;
        $Terminal = $Params[strlen($Params) - 1];
        if ($Terminal !== "\\" && $Terminal !== '/') {
            $Params .= '/';
        }
        $List = $phpMussel['DirectoryRecursiveList']($Params);
        $Returnable = '';
        foreach ($List as $Item) {
            echo "\r" . $Decal[$Frame];
            $Returnable .= $phpMussel['Fork']($phpMussel['cmd'] . ' ' . $Params . $Item, $Item) . "\n";
            $Frame = $Frame < 3 ? $Frame + 1 : 0;
        }
        echo "\r         ";
        return $Returnable;
    }
    return is_file($Params) ? $Callable($Params) : sprintf($phpMussel['lang']['cli_is_not_a'], $Params) . "\n";
};

/** Handles errors (will expand this later). */
$phpMussel['ErrorHandler_1'] = function ($errno) use (&$phpMussel) {
    return;
};

/** Duplication avoidance (some file handling for honeypot functionality). */
$phpMussel['ReadFile-For-Honeypot'] = function (&$Array, $File) use (&$phpMussel) {
    if (!isset($Array['qdata'])) {
        $Array['qdata'] = '';
    }
    $Array['odata'] = $phpMussel['ReadFile']($File);
    $Array['len'] = strlen($Array['odata']);
    $Array['crc'] = hash('crc32b', $Array['odata']);
    $Array['qfile'] = $phpMussel['Time'] . '-' . md5($phpMussel['Config']['general']['quarantine_key'] . $Array['crc'] . $phpMussel['Time']);
    if ($Array['len'] > 0 && $Array['len'] < $phpMussel['ReadBytes']($phpMussel['Config']['general']['quarantine_max_filesize'])) {
        $phpMussel['Quarantine'](
            $Array['odata'],
            $phpMussel['Config']['general']['quarantine_key'],
            $_SERVER[$phpMussel['Config']['general']['ipaddr']],
            $Array['qfile']
        );
    }
    if ($phpMussel['Config']['general']['delete_on_sight'] && is_readable($File)) {
        unlink($File);
    }
    $Array['qdata'] .= sprintf(
        'TEMP FILENAME: %1$s%6$sFILENAME: %2$s%6$sFILESIZE: %3$s%6$sMD5: %4$s%6$s%5$s',
        $File,
        urlencode($File),
        $Array['len'],
        md5($Array['odata']),
        sprintf($phpMussel['lang']['quarantined_as'], $Array['qfile']),
        "\n"
    );
};

/** Duplication avoidance (assigning kill details and unlinking files). */
$phpMussel['KillAndUnlink'] = function () use (&$phpMussel) {
    $phpMussel['killdata'] .=
        '-UPLOAD-LIMIT-EXCEEDED--NO-HASH-:' .
        $phpMussel['upload']['FilesData']['FileSet']['size'][$phpMussel['upload']['FilesData']['FileSet']['i']] . ':' .
        $phpMussel['upload']['FilesData']['FileSet']['name'][$phpMussel['upload']['FilesData']['FileSet']['i']] . "\n";
    $phpMussel['whyflagged'] .= sprintf($phpMussel['lang']['_exclamation'],
        $phpMussel['lang']['upload_limit_exceeded'] .
        ' (' . $phpMussel['upload']['FilesData']['FileSet']['name'][$phpMussel['upload']['FilesData']['FileSet']['i']] . ')'
    );
    if (
        $phpMussel['Config']['general']['delete_on_sight'] &&
        is_uploaded_file($phpMussel['upload']['FilesData']['FileSet']['tmp_name'][$phpMussel['upload']['FilesData']['FileSet']['i']]) &&
        is_readable($phpMussel['upload']['FilesData']['FileSet']['tmp_name'][$phpMussel['upload']['FilesData']['FileSet']['i']]) &&
        !$phpMussel['Config']['general']['honeypot_mode']
    ) {
        unlink($phpMussel['upload']['FilesData']['FileSet']['tmp_name'][$phpMussel['upload']['FilesData']['FileSet']['i']]);
    }
};

/** Increments statistics if they've been enabled. */
$phpMussel['Stats-Increment'] = function ($Statistic, $Amount) use (&$phpMussel) {
    if ($phpMussel['Config']['general']['statistics'] && isset($phpMussel['Statistics'][$Statistic])) {
        $phpMussel['Statistics'][$Statistic] += $Amount;
        $phpMussel['CacheModified'] = true;
    }
};

/** Initialise statistics if they've been enabled. */
$phpMussel['Stats-Initialise'] = function () use (&$phpMussel) {
    if ($phpMussel['Config']['general']['statistics']) {
        $phpMussel['CacheModified'] = false;

        if ($phpMussel['Statistics'] = ($phpMussel['FetchCache']('Statistics') ?: [])) {
            $phpMussel['Statistics'] = unserialize($phpMussel['Statistics']) ?: [];
        }

        if (empty($phpMussel['Statistics']['Other-Since'])) {
            $phpMussel['Statistics'] = [
                'Other-Since' => $phpMussel['Time'],
                'Web-Events' => 0,
                'Web-Scanned' => 0,
                'Web-Blocked' => 0,
                'Web-Quarantined' => 0,
                'CLI-Events' => 0,
                'CLI-Scanned' => 0,
                'CLI-Flagged' => 0,
                'API-Events' => 0,
                'API-Scanned' => 0,
                'API-Flagged' => 0
            ];
            $phpMussel['CacheModified'] = true;
        }
    }
};

/** Clears out the hash cache. */
$phpMussel['ClearHashCache'] = function () use (&$phpMussel) {
    $File = $phpMussel['cachePath'] . '48.tmp';
    $Data = $phpMussel['ReadFile']($File) ?: '';
    while (strpos($Data, 'HashCache:') !== false) {
        $Data = str_ireplace('HashCache:' . $phpMussel['substrbf']($phpMussel['substraf']($Data, 'HashCache:'), ';') . ';', '', $Data);
    }
    if (strlen($Data) < 2) {
        unset($File);
    } else {
        $Handle = fopen($File, 'w');
        fwrite($Handle, $Data);
        fclose($Handle);
    }
    $IndexFile = $phpMussel['cachePath'] . 'index.dat';
    $IndexNewData = $IndexData = $phpMussel['ReadFile']($IndexFile) ?: '';
    while (strpos($IndexNewData, 'HashCache:') !== false) {
        $IndexNewData = str_ireplace('HashCache:' . $phpMussel['substrbf']($phpMussel['substraf']($IndexNewData, 'HashCache:'), ';') . ';', '', $IndexNewData);
    }
    if ($IndexNewData !== $IndexData) {
        $IndexHandle = fopen($IndexFile, 'w');
        fwrite($IndexHandle, $IndexNewData);
        fclose($IndexHandle);
    }
    return true;
};

/**
 * Build directory path for logfiles.
 *
 * @param string $File The file we're building for.
 * @return bool True on success; False on failure.
 */
$phpMussel['BuildLogPath'] = function ($File) use (&$phpMussel) {
    $ThisPath = $phpMussel['Vault'];
    $File = str_replace("\\", '/', $File);
    while (strpos($File, '/') !== false) {
        $Dir = substr($File, 0, strpos($File, '/'));
        $ThisPath .= $Dir . '/';
        $File = substr($File, strlen($Dir) + 1);
        if (!file_exists($ThisPath) || !is_dir($ThisPath)) {
            if (!mkdir($ThisPath)) {
                return false;
            }
        }
    }
    return true;
};

/**
 * Checks whether the specified directory is empty.
 *
 * @param string $Directory The directory to check.
 * @return bool True if empty; False if not empty.
 */
$phpMussel['IsDirEmpty'] = function ($Directory) {
    return !((new \FilesystemIterator($Directory))->valid());
};

/**
 * Deletes empty directories (used by some front-end functions and log rotation).
 *
 * @param string $Dir The directory to delete.
 */
$phpMussel['DeleteDirectory'] = function ($Dir) use (&$phpMussel) {
    while (strrpos($Dir, '/') !== false || strrpos($Dir, "\\") !== false) {
        $Separator = (strrpos($Dir, '/') !== false) ? '/' : "\\";
        $Dir = substr($Dir, 0, strrpos($Dir, $Separator));
        if (!is_dir($phpMussel['Vault'] . $Dir) || !$phpMussel['IsDirEmpty']($phpMussel['Vault'] . $Dir)) {
            break;
        }
        rmdir($phpMussel['Vault'] . $Dir);
    }
};

/** Convert configuration directives for logfiles to regexable patterns. */
$phpMussel['BuildLogPattern'] = function ($Str, $GZ = false) {
    return '~^' . preg_replace(
        ['~\\\{(?:dd|mm|yy|hh|ii|ss)\\\}~i', '~\\\{yyyy\\\}~i', '~\\\{(?:Day|Mon)\\\}~i', '~\\\{tz\\\}~i', '~\\\{t\\\:z\\\}~i'],
        ['\d{2}', '\d{4}', '\w{3}', '.{1,2}\d{4}', '.{1,2}\d{2}\:\d{2}'],
        preg_quote(str_replace("\\", '/', $Str))
    ) . ($GZ ? '(?:\.gz)?' : '') . '$~i';
};

/**
 * GZ-compress a file (used by log rotation).
 *
 * @param string $File The file to GZ-compress.
 * @return bool True if the file exists and is readable; False otherwise.
 */
$phpMussel['GZCompressFile'] = function ($File) {
    if (!is_file($File) || !is_readable($File)) {
        return false;
    }
    static $Blocksize = 131072;
    $Filesize = filesize($File);
    $Size = ($Filesize && $Blocksize) ? ceil($Filesize / $Blocksize) : 0;
    if ($Size > 0) {
        $Handle = fopen($File, 'rb');
        $HandleGZ = gzopen($File . '.gz', 'wb');
        $Block = 0;
        while ($Block < $Size) {
            $Data = fread($Handle, $Blocksize);
            gzwrite($HandleGZ, $Data);
            $Block++;
        }
        gzclose($HandleGZ);
        fclose($Handle);
    }
    return true;
};

/**
 * Log rotation.
 *
 * @param string $Pattern What to identify logfiles by (should be supplied via the relevant logging directive).
 * @return bool False when log rotation is disabled or errors occur; True otherwise.
 */
$phpMussel['LogRotation'] = function ($Pattern) use (&$phpMussel) {
    $Action = empty($phpMussel['Config']['general']['log_rotation_action']) ? '' : $phpMussel['Config']['general']['log_rotation_action'];
    $Limit = empty($phpMussel['Config']['general']['log_rotation_limit']) ? 0 : $phpMussel['Config']['general']['log_rotation_limit'];
    if (!$Limit || ($Action !== 'Delete' && $Action !== 'Archive')) {
        return false;
    }
    $Pattern = $phpMussel['BuildLogPattern']($Pattern);
    $Arr = [];
    $Offset = strlen($phpMussel['Vault']);
    $List = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($phpMussel['Vault']), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($List as $Item => $List) {
        $ItemFixed = str_replace("\\", '/', substr($Item, $Offset));
        if ($ItemFixed && preg_match($Pattern, $ItemFixed) && is_readable($Item)) {
            $Arr[$ItemFixed] = filemtime($Item);
        }
    }
    unset($ItemFixed, $List, $Offset);
    $Count = count($Arr);
    $Err = 0;
    if ($Count > $Limit) {
        asort($Arr, SORT_NUMERIC);
        foreach ($Arr as $Item => $Modified) {
            if ($Action === 'Archive') {
                $Err += !$phpMussel['GZCompressFile']($phpMussel['Vault'] . $Item);
            }
            $Err += !unlink($phpMussel['Vault'] . $Item);
            if (strpos($Item, '/') !== false) {
                $phpMussel['DeleteDirectory']($Item);
            }
            $Count--;
            if (!($Count > $Limit)) {
                break;
            }
        }
    }
    return $Err ? false : true;
};

/**
 * Pseudonymise an IP address (reduce IPv4s to /24s and IPv6s to /32s).
 *
 * @param string $IP An IP address.
 * @return string A pseudonymised IP address.
 */
$phpMussel['Pseudonymise-IP'] = function($IP) {
    if (($CPos = strpos($IP, ':')) !== false) {
        $Parts = [(substr($IP, 0, $CPos) ?: ''), (substr($IP, $CPos +1) ?: '')];
        if (($CPos = strpos($Parts[1], ':')) !== false) {
            $Parts[1] = substr($Parts[1], 0, $CPos) ?: '';
        }
        $Parts = $Parts[0] . ':' . $Parts[1] . '::x';
        return str_replace(':::', '::', $Parts);
    }
    return preg_replace(
        '/^([01]?\d{1,2}|2[0-4]\d|25[0-5])\.([01]?\d{1,2}|2[0-4]\d|25[0-5])\.([01]?\d{1,2}|2[0-4]\d|25[0-5])\.([01]?\d{1,2}|2[0-4]\d|25[0-5])$/i',
        '\1.\2.\3.x',
        $IP
    );
};
