<?php

/*
Äëÿ òîíêîãî èçìåðåíèÿ âðåìè âûïîëíåíèÿ âñåé ïðîãðàììû è îòäåëüíûõ 
êóñêîâ (èõ ì.á. î÷åíü ìíîãî) íóæíî ðàññòàâèòü â ïðîãðàììå ÿêîðÿ - 
"íà÷àòü çàìåð âðåìåíè" è "çàêîí÷èòü". ßêîðÿ ðàçëè÷àþòñÿ ñâîèìè èìåíàìè.

Ìîæíî èñïîëüçîâàòü äëÿ çàìåðà SQL çàïðîñà, â òåëå öèêëà, ïðîñòî â 
ðàçíûõ ìåñòàõ. 

Ó÷èòûâàåòñÿ íå òîëüêî âðåìÿ âûïîëíåíèÿ, íî è êîë-âî ðàç âûçîâà
çàìåðà âðåìåíè. Êëþ÷åâîå ñëîâî 'my_time' èñïîëüçîâàòü íåëüçÿ (ò.å.
íåëüçÿ âûçûâàòü timestart("my_time") - ïðèäóìûâàéòå ëþáîå äðóãîå ñëîâî).

Ïàðàìåòðû:

  timeprint("%max") èëè "%min" - ïîêàç % âðåìåíè
     %max: 100% çàíèìàåò ñàìàÿ äëèòåëüíàÿ èç âñåõ çàìåðåííûõ îïåðàöèé
     %min: 0% çàíèìàåò ñàìàÿ áûñòðàÿ èç âñåõ çàìåðåííûõ îïåðàöèé,
       à îñòàëüíûå öèôðû - êîëâî ïðîöåòîâ îò âðåìåíè, ÍÀ ñêîëüêî 
       äðóãèå îïåðàöèè ìåäëåííåå ñàìîé áûñòðîé

  timeprint("graf") - âûäàòü ãðàôè÷åñêîå ïðåäñòàâëåíèå %max
  
  timeprint("nomain") - íå âûäàâàòü íèæíèå 3 ñòðî÷êè îá îáùåì 
     âðåìåíè ðàáîòû ïðîãðàììû

×òîáû èñïîëüçîâàòü íåñêîëüêî ïàìåòðîâ, ïåðå÷èñëèòå èõ òàê:

  timeprint("%min %max graf");
        

------------------- 1 --------------------
Ñàìîå ïðîñòîå: 
   1. ïîäêëþ÷èòü ýòîò ôàéë â _íà÷àëå_ âàøåé ïðîãðàììû ÷åðåç include()
   2. âûçâàòü â ñàìîì êîíöå âàøåé ïðîãðàììû: timeprint()
Ðåçóëüòàò: 
   ïîÿâèòüñÿ âðåìÿ âûïîëíåíèÿ âñåé ïðîãðàììû (îò èíêëþäà, äî timeprint)
Âûâîä: 
   îáùåå âðåìÿ ðàáîòû ïðîãðàììû ïîäñ÷èòûâàåòñÿ àâòîìàòè÷åñêè

------------------- 2 --------------------
Çàìåð âûïîëíåíèÿ SQL çàïðîñ:
   áûë êîä: 
      ....
      mysql_query(....);
      ....
   çàìåíèòü íà êîä:
      ....
      timerstart("èìÿ1");
      mysql_query(....);
      timeend("èìÿ1");
      ....
   â êîíöå ïðîãðàììû:
      timeprint();
Ðåçóëüòàò: 
   äîïîëíèòåëüíî ïîÿâèòüñÿ âðåìÿ (ïîä êîäîâûì íàçâàíèåì "èìÿ1") ìåæäó ýòèìè 
   äâóìÿ òî÷êàìè

------------------- 3 --------------------
Çàìåð âðåìåíè ïðè ìíîãîêðàòíûõ âûçîâàõ îäíèõ è òåõ æå ÿêîðåé
   áûë êîä: 
      ....
      while() {
         ....
      }
      ....
   çàìåíèòü íà 
      ....
      while() {
         timestart("èìÿ2");
         ....
         timestop("èìÿ2");
      }
      ....
   â êîíöå ïðîãðàììû:
      timeprint();
Ðåçóëüòàò:
   äîïîëíèòåëüíî ñîîáùàò, êîëüêî ðàç ýòî âñå âûçûâàëè, îáùåå âðåìÿ è ñðåäíåå
   âðåìÿ íà îäèí öèêë.




*/


//error_reporting(2039);


function timestart($name) {
   global $mytimestats;
   if (strlen($name)==0) {
      // Îøèáêà èñïîëüçîâàíèÿ ôóíêöèè TIMESTART. Òpåáóåòñÿ óêàçàòü ïàpàìåòp!
      return;
   }
   $x=explode(" ",microtime());
   $x[1]=substr("$x[1]",2,14);
   $mytimestats[$name]['temp']=$x[1]+$x[0];
   //echo "<br> *-* ".$mytimestats[$name][temp]." *-* <br>";
}

function timestop($name) {
   global $mytimestats;
   if (strlen($name)==0) {
      // Îøèáêà èñïîëüçîâàíèÿ ôóíêöèè TIMEEND. Òpåáóåòñÿ óêàçàòü ïàpàìåòp!
      return;
   }
   $x=explode(" ",microtime());
   $x[1]=substr("$x[1]",2,14);
   @$mytimestats[$name]['all']+=$x[1]+$x[0]-$mytimestats[$name]['temp'];
   @$mytimestats[$name]['counter']++;
   //echo "<br> *---* ".$mytimestats[$name]['all']." *---* <br>";
}

// function timeprint($par="") {
//    timestop("my_time");
//    global $mytimestats;
//
//    $k=array_keys($mytimestats);
//
//    if (strstr($par,"nomain")) {
//       $nomain=1;
//    }
//    if (strstr($par,"%min")) {
//       $proc1=1;
//       $procent1="<td>% from min</td>";
//    }
//    if (strstr($par,"%max")) {
//       $proc2=1;
//       $procent2="<td>% from max</td>";
//    }
//    if (strstr($par,"graf")) {
//       $graf=1;
//       $grafik="<td align=center>All time</td>";
//    }
//    if ($proc1 || $proc2 || $graf) {
//       $mmin=999999;
//       $mmax=-1;
//       for ($i=0; $i<count($k); $i++) {
//          if ($k[$i]=="my_time") continue;
//          if ($mmin>$mytimestats[$k[$i]]['all']) $mmin=$mytimestats[$k[$i]]['all'];
//          if ($mmax<$mytimestats[$k[$i]]['all']) $mmax=$mytimestats[$k[$i]]['all'];
//       }
//    }
//
//    echo "<center><table border=1 cellspacing=0 cellpadding=3 bordercolor=#a2a2a2 bgcolor=#ededed>
//    <tr><td align=center>Counter</td>
// <td align=center>Number<br>calls</td>
// <td align=center>Total<br>time</td><td align=center>Average<br>time</td>
// $procent1$procent2$grafik</tr>";
//    for ($i=0; $i<count($k); $i++) {
//       if ($k[$i]=="my_time") continue;
//       @printf("<tr><td><b>$k[$i]</b></td><td>%d</td><td>%.4f</td><td>%.4f</td>",
//             $mytimestats[$k[$i]]['counter'],
//             $mytimestats[$k[$i]]['all'],
//             (float)$mytimestats[$k[$i]]['all']/$mytimestats[$k[$i]]['counter']);
//       if ($k[$i]<>"my_time") {
//          if ($proc1) {
//             printf("<td>%02.1f%%</td>",(float)$mytimestats[$k[$i]]['all']/$mmin*100-100);
//          }
//          if ($proc2) {
//             printf("<td>%02.1f%%</td>",(float)$mytimestats[$k[$i]]['all']/$mmax*100);
//          }
//          if ($graf) {
//             $width=round(100*(float)$mytimestats[$k[$i]]['all']/$mmax);
//             $width2=100-$width;
//             echo "<td>".
//
//                 "<table width=100 border=0 cellspacing=0 cellpadding=0>".
//                 "<tr>".
//                     "<td width=$width bgcolor=#FF0000></td>".
//                     "<td width=$width2 bgcolor=#dedede><img src=\"staff/images/s.gif\" width=$width2 height=10></td>".
//                 "</tr>".
//                 "</table>".
//
//                 "</td>";
//          }
//          $tt+=$mytimestats[$k[$i]]['all'];
//          $tc+=$mytimestats[$k[$i]]['counter'];
//       }
//       else {
//          if ($proc1) echo "<td>&nbsp;</td>";
//          if ($proc2) echo "<td>&nbsp;</td>";
//          if ($graf) echo "<td>&nbsp;</td>";
//       }
//       echo "</tr>";
//    }
//    if (@!$nomain)
//       printf("
// <tr><td colspan=4>Total execution time  %.4f sec</td></tr>
// <tr><td colspan=4>Total inside calls time %.4f sec (%d times)</td></tr>
// <tr><td colspan=4>Time remaining %.4f sec</td></tr>",
//    @$mytimestats['my_time']['all'],@$tt,@$tc,
//    @$mytimestats['my_time']['all']-@$tt);
//
//     if(function_exists('memory_get_usage')) {
//         $mem = number_format(memory_get_usage() / 1048576 * 100 / 100, 2, '.', ' ') ." mb";
//         printf("<tr><td colspan=4>Total memory usage %s</td></tr>", $mem);
//     }
//
//    echo "</table></center>\r\n\r\n\r\n";
// }


function timeprint($par="") {
   timestop("my_time");
   global $mytimestats;

   $nomain = 0;
   $k=array_keys($mytimestats);

   if (strstr($par,"nomain")) {
      $nomain=1;
   }
   if (strstr($par,"%min")) {
      $proc1=1;
      $procent1="<td>% from min</td>";
   }
   if (strstr($par,"%max")) {
      $proc2=1;
      $procent2="<td>% from max</td>";
   }
   if (strstr($par,"graf")) {
      $graf=1;
      $grafik="<td align=center>All time</td>";
   }
   if ($proc1 || $proc2 || $graf) {
      $mmin=999999;
      $mmax=-1;
      for ($i=0; $i<count($k); $i++) {
         if ($k[$i]=="my_time") continue;
         if ($mmin>$mytimestats[$k[$i]]['all']) $mmin=$mytimestats[$k[$i]]['all'];
         if ($mmax<$mytimestats[$k[$i]]['all']) $mmax=$mytimestats[$k[$i]]['all'];
      }
   }
 
   $str = "<center><table border=1 cellspacing=0 cellpadding=3 bordercolor=#a2a2a2 bgcolor=#ededed>
   <tr><td align=center>Counter</td>
<td align=center>Number<br>calls</td>
<td align=center>Total<br>time</td><td align=center>Average<br>time</td>
$procent1$procent2$grafik</tr>";
   for ($i=0; $i<count($k); $i++) {
      if ($k[$i]=="my_time") continue;
      @$str .= sprintf("<tr><td><b>$k[$i]</b></td><td>%d</td><td>%.4f</td><td>%.4f</td>",
            $mytimestats[$k[$i]]['counter'],
            $mytimestats[$k[$i]]['all'],
            (float)$mytimestats[$k[$i]]['all']/$mytimestats[$k[$i]]['counter']);
      if ($k[$i]<>"my_time") {
         if ($proc1) {
            $str .= sprintf("<td>%02.1f%%</td>",(float)$mytimestats[$k[$i]]['all']/$mmin*100-100);
         }
         if ($proc2) {
            $str .= sprintf("<td>%02.1f%%</td>",(float)$mytimestats[$k[$i]]['all']/$mmax*100);
         }
         if ($graf) {
            $width=round(100*(float)$mytimestats[$k[$i]]['all']/$mmax);
            $width2=100-$width;
            $str .= "<td>".
                
                "<table width=100 border=0 cellspacing=0 cellpadding=0>".
                "<tr>".
                    "<td width=$width bgcolor=#FF0000></td>".
                    "<td width=$width2 bgcolor=#dedede><img src=\"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7\" style=\"width:{$width2}px; height:10px\"></td>".
                "</tr>".
                "</table>".
                
                "</td>";
         }
         @$tt+=$mytimestats[$k[$i]]['all'];
         @$tc+=$mytimestats[$k[$i]]['counter'];
      }
      else {
         if ($proc1) $str .= "<td>&nbsp;</td>";
         if ($proc2) $str .= "<td>&nbsp;</td>";
         if ($graf) $str .= "<td>&nbsp;</td>";
      }
      $str .= "</tr>";
   }
   if (@!$nomain)
      $str .= sprintf("
<tr><td colspan=4>Total execution time  %.4f sec</td></tr>
<tr><td colspan=4>Total inside calls time %.4f sec (%d times)</td></tr>
<tr><td colspan=4>Time remaining %.4f sec</td></tr>",
   @$mytimestats['my_time']['all'],@$tt,@$tc,
   @$mytimestats['my_time']['all']-@$tt);

    if(function_exists('memory_get_usage')) {
        $mem = number_format(memory_get_usage() / 1048576 * 100 / 100, 2, '.', ' ') ." mb";
        $str .= sprintf("<tr><td colspan=4>Total memory usage %s</td></tr>", $mem);    
    }

   $str .= "</table></center>\r\n\r\n\r\n";

   return $str;
}

timestart("my_time");
?>