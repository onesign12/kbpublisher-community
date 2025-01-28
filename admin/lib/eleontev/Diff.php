<?php

class Diff2
{

    /**
     * Returns difference between two HTML strings: $str1 (old) and $str2 (new),
     * marks removed parts with <del>...</del>
     * and inserted parts with <ins>...</ins>
     */
    static function diff_html($str1, $str2, $show_source = false)
    {
        $old = Diff2::html2list($str1);
        $new = Diff2::html2list($str2);

        $diff = Diff2::diff_unix($old, $new);

        $first = '';
        $second = '';
        $mode = 'c';
        $name = '';    // dummy
        $to_ins = array();
        $to_del = array();
        foreach ($diff as $x) {
            switch ($x[0]) {
                case 'c':
                    switch ($mode) {
                        case 'd':
                            $first .= Diff2::_stick('del', $to_del, $show_source);
                            break;
                        case 'i':
                            $second .= Diff2::_stick('ins', $to_ins, $show_source);
                            break;
                    }
                    $mode = 'c';
                    $first .= ($show_source ? htmlspecialchars($x[1]) : $x[1]);
                    $second .= ($show_source ? htmlspecialchars($x[1]) : $x[1]);
                    break;
                case 'd':
                    if (Diff2::_is_open_tag($x[1], $name)
                    || Diff2::_is_close_tag($x[1], $name)) {
                        $first .= ($show_source ? htmlspecialchars($x[1]) : $x[1]);
                    } else {
                        if ($mode == 'i') {
                            $second .= Diff2::_stick('ins', $to_ins, $show_source);
                        }
                        $mode = 'd';
                        $to_del[] = $x[1];
                    }
                    break;
                case 'i':
                    if (Diff2::_is_open_tag($x[1], $name)
                    || Diff2::_is_close_tag($x[1], $name)) {
                        $second .= ($show_source ? htmlspecialchars($x[1]) : $x[1]);
                    } else {
                        if ($mode == 'd') {
                            $first .= Diff2::_stick('del', $to_del, $show_source);
                        }
                        $mode = 'i';
                        $to_ins[] = $x[1];
                    }
                    break;
            }
        }

        $first .= Diff2::_stick('del', $to_del, $show_source);
        $second .= Diff2::_stick('ins', $to_ins, $show_source);

        if ($show_source) {
            $first = nl2br($first);
            $second = nl2br($second);
        }
        return array($first, $second);
    }

    static function _stick($tag, &$arr, $show_source)
    {
        if (count($arr) == 0) {
            return '';
        }
        $res = '<' . $tag . ' class="diff">';
        foreach ($arr as $a) {
            if ($show_source) {
                $a = htmlspecialchars($a);
            }
            $res .= $a;
        }
        $res .= '</' . $tag . '>';
        $arr = array();
        return $res;
    }


    /**
     * Analogue of standard Unix `diff` for two arrays of strings: $t1 (old) and $t2 (new).
     * Returns array with elements:
     *   array('c', '...') - common string
     *   array('d', '...') - deleted string
     *   array('i', '...') - inserted string
     */
    static function diff_unix($t1, $t2)
    {
        // build a reverse-index array using the line as key and line number as value
        // don't store blank lines, so they won't be targets of the shortest distance
        // search
        foreach($t1 as $i => $x) if ($x > '') $r1[$x][] = $i;
        foreach($t2 as $i => $x) if ($x > '') $r2[$x][] = $i;
        $a1 = 0;
        $a2 = 0; // start at beginning of each list
        $actions = array();
        $cnt1 = count($t1);
        $cnt2 = count($t2);
        // walk this loop until we reach the end of one of the lists
        while ($a1 < $cnt1 && $a2 < $cnt2) {
            // if we have a common element, save it and go to the next
            if ($t1[$a1] == $t2[$a2]) {
                $actions[] = 4;
                $a1++;
                $a2++;
                continue;
            }
            // otherwise, find the shortest move (Manhattan-distance) from the
            // current location
            $best1 = $cnt1;
            $best2 = $cnt2;
            $s1 = $a1;
            $s2 = $a2;
            while (($s1 + $s2 - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
                $d = - 1;
                foreach((array)@$r1[$t2[$s2]] as $n) if ($n >= $s1) {
                    $d = $n;
                    break;
                }
                if ($d >= $s1 && ($d + $s2 - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
                    $best1 = $d;
                    $best2 = $s2;
                }
                $d = - 1;
                foreach((array)@$r2[$t1[$s1]] as $n) if ($n >= $s2) {
                    $d = $n;
                    break;
                }
                if ($d >= $s2 && ($s1 + $d - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
                    $best1 = $s1;
                    $best2 = $d;
                }
                $s1++;
                $s2++;
            }
            while ($a1 < $best1) {
                $actions[] = 1;
                $a1++;
            } // deleted elements
            while ($a2 < $best2) {
                $actions[] = 2;
                $a2++;
            } // added elements

        }
        // we've reached the end of one list, now walk to the end of the other
        while ($a1 < $cnt1) {
            $actions[] = 1;
            $a1++;
        } // deleted elements
        while ($a2 < $cnt2) {
            $actions[] = 2;
            $a2++;
        } // added elements
        // and this marks our ending point
        $actions[] = 8;
        // now, let's follow the path we just took and report the added/deleted
        // elements into $res.
        $op = 0;
        $x0 = $x1 = 0;
        $y0 = $y1 = 0;
        $res = array();
        foreach($actions as $act) {
            if ($act == 1) {
                $op|= $act;
                $x1++;
                continue;
            }
            if ($act == 2) {
                $op|= $act;
                $y1++;
                continue;
            }
            if ($op > 0) {
                while ($x0 < $x1) {
                    $res[] = array('d', $t1[$x0]);
                    $x0++;
                } // deleted elems
                while ($y0 < $y1) {
                    $res[] = array('i', $t2[$y0]);
                    $y0++;
                } // added elems

            }
            if (isset($t1[$x1])) {    // we are here only if $act == 4 or $act == 8
                $res[] = array('c', $t1[$x1]);
            }
            $x1++;
            $x0 = $x1;
            $y1++;
            $y0 = $y1;
            $op = 0;
        }
        return $res;
    }


    /**
     * Checks if the string $s is an opening tag (e.g. '<html>')
     * Returns boolean, changes $name to the name of tag (if TRUE)
     */
    static function _is_open_tag($s, &$name)
    {
        // should we use preg_match() here?
        $len = strlen($s);
        if ($s[0] == '<' && $s[1] != '/' && $s[$len - 2] != '/') {
            $p = $len - 1;
            foreach (array(' ', "\t", "\n", '>') as $c) {
                $i = strpos($s, $c);
                if ($i > 0 && $i < $p) {
                    $p = $i;
                }
            }
            $name = strtolower(substr($s, 1, $p - 1));
            return true;
        }
        return false;
    }


    /**
     * Checks if the string $s is a closing tag (e.g. '</html>')
     * Name is stored at $name
     * Returns boolean.
     */
    static function _is_close_tag($s, &$name)
    {
        if (preg_match('/^<\/(.+)>$/', $s, $matches) == 1) {
            $name = strtolower($matches[1]);
            return true;
        }
        return false;
    }


    static function _is_whitespace($c)
    {
        return (in_array($c, array(' ', "\t", "\n", "\r")));
    }


    /**
     * Helper function
     */
    static function _html2list_push($cur, &$out)
    {
        if ($cur != '') {
            $out[] = $cur;
        }
    }


    /**
     * Converts HTML string into array of tags and words of plain text.
     * For example: '<html>asd qwe</html>' will be returned as array('<html>', 'asd', 'qwe', '</html>');
     */
    static function html2list($x)
    {
        $mode = 0; // 0 - char, 1 - tag, 2 - comment
        $cur = '';
        $out = array();
        $ln = strlen($x);
        for ($i = 0; $i < $ln; $i += 1) {
            $c = $x[$i];
            if ($mode == 1) { // tag
                if ($c == '>') {
                    $cur .= $c;
                    Diff2::_html2list_push($cur, $out);
                    $cur = '';
                    $mode = 0;
                } else {
                    $cur .= $c;
                }
            } elseif ($mode == 0) {    // char
                if ($c == '<') {
                    Diff2::_html2list_push($cur, $out);
                    if (substr($x, $i + 1, 3) == '!--') {
                        $i += 3; // skip '!--' in the input string
                        $mode = 2;
                    } else {
                        $cur = $c;
                        $mode = 1;
                    }
                } elseif (Diff2::_is_whitespace($c)) {
                    // attach spaces to the item end
                    $cur .= $c;
                } else {
                    $curlast = strlen($cur) - 1;
                    if (isset($cur[$curlast]) && Diff2::_is_whitespace($cur[$curlast])) {
                        // start new item
                        Diff2::_html2list_push($cur, $out);
                        $cur = '';
                    }
                    $cur .= $c;
                }
            } elseif ($mode == 2) { // comment
                if ($c == '-' && substr($x, $i, 3) == '-->') {
                    $i += 2;
                    $mode = 0;
                }
                // skipping comments
            }
        }
        Diff2::_html2list_push($cur, $out);
        return $out;
    }
}

?>