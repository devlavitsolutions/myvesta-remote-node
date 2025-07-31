<?php

// --- file functions ---

function myvesta_find_in_file($file, $find, $quiet=false) {
    if (!file_exists($file)) {
        if ($quiet) return false;
        return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");
    }

    $buf=file_get_contents($file);

    $pos=strpos($buf, $find);
    
    if ($pos===false) return myvesta_throw_error (MYVESTA_ERROR_STRING_NOT_FOUND, "");
    if ($quiet) return true;
    return $pos;
}

function myvesta_replace_in_file($file, $find, $replace) {
    if (!file_exists($file)) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");

    $buf=file_get_contents($file);

    if (strpos($buf, $find)===false) return myvesta_throw_error (MYVESTA_ERROR_STRING_NOT_FOUND, "String '$find' not found");

    $buf=str_replace($find, $replace, $buf);
    $r=file_put_contents($file, $buf);
    if ($r===false) return false;
    return true;
}

function myvesta_get_between_in_file($file, $left_substring, $right_substring, $start=0, $return_left_substring=0, $return_right_substring=0, $left_substring_necessary=1, $right_substring_necessary=1) {
    if (!file_exists($file)) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");
    $text=file_get_contents($file);
    return myvesta_str_get_between ($text, $left_substring, $right_substring, $start, $return_left_substring, $return_right_substring, $left_substring_necessary, $right_substring_necessary);
}

function myvesta_replace_in_file_once_between_including_borders($file, $left, $right, $replace_with) {
    if (!file_exists($file)) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");
    $text=file_get_contents($file);
    $buf=myvesta_str_replace_once_between_including_borders($text, $left, $right, $replace_with);
    $r=file_put_contents($file, $buf);
    if ($r===false) return false;
    return true;
}

function myvesta_strip_once_in_file_between_including_borders($file, $left, $right) {
    if (!file_exists($file)) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");
    $text=file_get_contents($file);
    $buf=myvesta_str_strip_once_between_including_borders($text, $left, $right);
    $r=file_put_contents($file, $buf);
    if ($r===false) return false;
    return true;
}

function myvesta_replace_in_file_between_including_borders($file, $left, $right, $replace_with) {
    if (!file_exists($file)) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");
    $text=file_get_contents($file);
    $buf=myvesta_str_replace_between_including_borders($text, $left, $right, $replace_with);
    $r=file_put_contents($file, $buf);
    if ($r===false) return false;
    return true;
}

function myvesta_strip_in_file_between_including_borders($file, $left, $right) {
    if (!file_exists($file)) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File '$file' not found");
    $text=file_get_contents($file);
    $buf=myvesta_str_strip_between_including_borders($text, $left, $right);
    $r=file_put_contents($file, $buf);
    if ($r===false) return false;
    return true;
}

// --- mixed functions ---

function myvesta_grep($find, $content, $count=0, $quiet=0) {
    global $myvesta_stdin, $myvesta_stdin_return_not_found, $myvesta_quiet_mode;
    if ($count==='-c') {$count=1; $quiet=0;}
    if ($count==='-q') {$count=0; $quiet=1;}
    if ($myvesta_quiet_mode==0) $myvesta_quiet_mode=$quiet;
    //echo "find          = " . $find."\n"; echo "file_or_stdin = " . $content."\n"; echo "count         = " . $count."\n"; echo "quiet         = " . $quiet."\n"; exit;
    if ($myvesta_stdin_return_not_found==true) {
        if ($count==1) return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "0");
        return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "");
    }
    
    $arr=explode("\n", $content);

    $buffer='';
    $hits=0;
    foreach ($arr as $line) {
        if (strpos($line, $find)!==false) {
            $hits++;
            if ($quiet==false && $count==false) $buffer.=$line."\n";
        }
    }
    if ($count==1) {
        if ($hits==0) return myvesta_exit (MYVESTA_ERROR_STRING_NOT_FOUND, "0");
        return $hits;
    }
    if ($quiet==1) {
        if ($hits==0) return myvesta_exit (MYVESTA_ERROR_STRING_NOT_FOUND, "");
        return true;
    }
    if ($hits==0) return myvesta_exit (MYVESTA_ERROR_STRING_NOT_FOUND, "");
    return $buffer;
}

function myvesta_sed($find, $replace, $content) {
    global $myvesta_stdin, $myvesta_stdin_return_not_found, $myvesta_stdin_from_file;
    //echo "find            = " . $find."\n"; echo "replace         = " . $replace."\n"; echo "file_or_stdin   = " . $content."\n"; echo "stdin_from_file = " . $myvesta_stdin_from_file."\n"; exit;
    if ($myvesta_stdin_return_not_found==true) {
        return myvesta_throw_error (MYVESTA_ERROR_FILE_DOES_NOT_EXISTS, "File not found");
    }

    if (strpos($content, $find)===false) return myvesta_throw_error (MYVESTA_ERROR_STRING_NOT_FOUND, "String '$find' not found");

    $content=str_replace($find, $replace, $content);
    if ($myvesta_stdin_from_file!='') {
        $r=file_put_contents($myvesta_stdin_from_file, $content);
        if ($r===false) return false;
    } else {
        myvesta_echo ($content);
    }
    return true;
}

// --- string functions ---

function myvesta_str_get_between (&$text, $left_substring, $right_substring, $start=0, $return_left_substring=0, $return_right_substring=0, $left_substring_necessary=1, $right_substring_necessary=1) {
    global $myvesta_str_found_at, $myvesta_str_end_at;
    $myvesta_str_found_at=0;
    $myvesta_str_end_at=0;
    $from_null=0;
    $pos1=strpos($text, $left_substring, $start);
    if ($pos1===FALSE)
    {
        if ($left_substring_necessary==1) return "";
        $pos1=$start;
        $from_null=1;
    }

    if ($return_left_substring==0)
    {
        if ($from_null==0) $pos1=$pos1+strlen($left_substring);
    }
    $pos2=strpos($text, $right_substring, $pos1+strlen($left_substring));
    if ($pos2===FALSE)
    {
        if ($right_substring_necessary==1) return "";
        $pos2=strlen($text);
    }
    if ($return_right_substring==0) $len=$pos2-$pos1;
    else $len=($pos2-$pos1)+strlen($right_substring);
    
    $slen=strlen($text);
    if ($pos1+$len>$slen) $len=$slen-$pos1;
    
    $myvesta_str_found_at=$pos1;
    $myvesta_str_end_at=$pos1+$len;
    
    return substr($text, $pos1, $len);
}

function myvesta_str_replace_once_between_including_borders(&$text, $left, $right, $replace_with) {
    $pos1=strpos($text, $left);
    if ($pos1===false) return $text;
    $pos2=strpos($text, $right, $pos1+strlen($left));
    if ($pos2===false) return $text;
    return substr($text, 0, $pos1).$replace_with.substr($text, $pos2+strlen($right));
}

function myvesta_str_strip_once_between_including_borders(&$text, $left, $right) {
    $pos1=strpos($text, $left);
    if ($pos1===false) return $text;
    $pos2=strpos($text, $right, $pos1+strlen($left));
    if ($pos2===false) return $text;
    return substr($text, 0, $pos1).substr($text, $pos2+strlen($right));
}

function myvesta_str_replace_between_including_borders($text, $left, $right, $replace_with) {
    $start=0;
    $left_len=strlen($left);
    $right_len=strlen($right);
    while (true) {
        $pos1=strpos($text, $left);
        if ($pos1===false) break;
        $pos2=strpos($text, $right, $pos1+$left_len);
        if ($pos2===false) break;
        $text=substr($text, 0, $pos1).$replace_with.substr($text, $pos2+$right_len);
    }
    return $text;
}

function myvesta_str_strip_between_including_borders($text, $left, $right) {
    global $myvesta_stdin;
    $args=func_get_args();
    $args_i=-1;
    if ($myvesta_stdin!='') {
        $text=$myvesta_stdin;
    } else {
        $args_i++; $text=$args[$args_i];
    }
    $args_i++; $left=$args[$args_i];
    $args_i++; $right=$args[$args_i];

    $left_len=strlen($left);
    $right_len=strlen($right);
    while (true) {
        $pos1=strpos($text, $left);
        if ($pos1===false) break;
        $pos2=strpos($text, $right, $pos1+$left_len);
        if ($pos2===false) break;
        $text=substr($text, 0, $pos1).substr($text, $pos2+$right_len);
    }
    return $text;
}

function myvesta_str_find($text, $find, $quiet=false) {
    $pos=strpos($text, $find);
    if ($pos===false) return myvesta_throw_error (MYVESTA_ERROR_STRING_NOT_FOUND, "");
    if ($quiet) return true;
    return $pos;
}

function myvesta_str_uppercase($text) {
    return strtoupper($text);
}

function myvesta_str_lowercase($text) {
    return strtolower($text);
}

function myvesta_str_substring($text, $start, $length=null) {
    if ($length===null) return substr($text, $start);
    if ($length!==null) return substr($text, $start, $length);
}
