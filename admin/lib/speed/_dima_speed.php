<?php

   include_once("_dima_timestat.php");
   @set_time_limit(60*30); // 30 ìèíóò

   unset($cf);
   unset($evalcod);
   for ($i=0; $i<50; $i++) {
      echo "<!-- çàòðàâêà â 1Êá äëÿ flush(), èíà÷å íå ðàáîòàåò (ãëþê IE) -->";
   }
   echo "Ïîäãîòîâêà...";
   flush();

   //////////////////////////////////////////////////////////////////////
   
   
   foreach(explode($phpexplode,$phptest) as $s) {
      if (strlen(trim($s))>1) {
         $cf++;
         $evalcod[]=$s;
      }
   }

   for ($i=0; $i<$cf; $i++) {
      $f[$i]=fopen("tmp.tmp$i","w+") or die("err# open '.tmp$i'");
      fputs($f[$i],"$phpbegin ");
   }

   for ($i=0; $i<$cn; $i++) {
      for ($j=0; $j<$cf; $j++) {
         fputs($f[$j],$evalcod[$j]);
      }
   }

   echo "Ãîòîâî.<br>Òåñòèðîâàíèå...<hr size=1 noshade>";
   flush();

   for ($i=0; $i<$cf; $i++) {
      fputs($f[$i]," $phpend");
      fclose($f[$i]);
      timestart("test N".($i+1));
      include("tmp.tmp$i");
      timestop("test N".($i+1));
      unlink("tmp.tmp$i");
      echo ($i+1)." ";
      flush();
   }


   echo "<hr size=1 noshade>\r\n\r\n<ol>";
   for ($i=0; $i<count($evalcod); $i++) {
      echo "<li><b><font color=red>{</font><tt><font color=blue face='Lucida'>".
         str_replace(" ","&nbsp;",htmlspecialchars($evalcod[$i]))."</font></tt><font color=red>}</font></b>\r\n";
   }    
   echo "</ol>\r\n\r\n\r\n\r\n";
   timeprint("%min %max graf nomain");

?>