<?php       
   
   // сколько раз исполнять код каждого варианта теста
   $cn=1;
  
   // данный код выполнится перед тестами
   // (здесь надо открыть пхп-код "<?php")
   $phpbegin='
      <?php 
         unset($text);
         $x="Ив";
         $text=implode("",file("speed.html"));
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text.$text;
         $text=$text.$text.$text;
         
         echo "(".strlen($text).") ";
   ';

   // код самих тестов (от 0 до N) - по строке на варианты тестов
   // учитываются конечные и начальные пробелы, пустые строки пропускаются
   $phptest='
eregi("(ма[a-zа-я]{1,20})",$text);
preg_match("/(ма[a-zа-я]{1,20})/im",$text);
   ';

   // разделить между варинтами из $test (переход на новую строку)
   $phpexplode="\r\n";

   // данный код выполнится после тестов
   // (здесь надо закрыть пхп-код "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>