<?php
#
# sorts an array of named arrays by the supplied fields
#   code by dholmes at jccc d0t net
#   taken from http://au.php.net/function.uasort
# modified by cablehead, messju and pscs at http://www.phpinsider.com/smarty-forum

function array_sort_by_fields(&$data, $sortby){

    // PHP 8.3 compatible replacement for create_function
    // We will use usort/uasort with an anonymous function that implements the logic directly.

    uasort($data, function($a, $b) use ($sortby) {
        $c = 0;
        foreach (explode(',', $sortby) as $key)
        {
           $d = 1;
              if (substr($key, 0, 1) == '-')
              {
                 $d = -1;
                 $key = substr($key, 1);
              }
              if (substr($key, 0, 1) == '#')
              {
                 $key = substr($key, 1);
                 // Check existence to avoid warnings
                 $valA = $a[$key] ?? 0;
                 $valB = $b[$key] ?? 0;

                 if ( ($c = ($valA - $valB)) != 0 ) return $d * $c;
              }
              else
              {
                 $valA = $a[$key] ?? '';
                 $valB = $b[$key] ?? '';
                 if ( ($c = strcasecmp($valA, $valB)) != 0 ) return $d * $c;
            }
        }
        return $c;
    });
}

#
# Modifier: sortby - allows arrays of named arrays to be sorted by a given field
#
function smarty_modifier_sortby($arrData,$sortfields) {
   array_sort_by_fields($arrData,$sortfields);
   return $arrData;
}
