<?php       
   
   // сколько раз исполнять код каждого варианта теста
   $cn=50000;
  
   // данный код выполнится перед тестами
   // (здесь надо открыть пхп-код "<?php")
   $phpbegin='
      <?php 
         unset($test); 
         $myarray=array("123",123,"test"=>"asdfgh",array(1,2,array("qwe")),"name"=>array(1,2,"second"=>array("qwe","asd","zxc"))); 
   ';

   // код самих тестов (от 0 до N) - по строке на варианты тестов
   // учитываются конечные и начальные пробелы, пустые строки пропускаются
   $phptest='
$x="test".$myarray["test"]."test";
$x="test$myarray[test]test";      
$x="test{$myarray[test]}test";    
   ';

   // разделить между варинтами из $test (переход на новую строку)
   $phpexplode="\r\n";

   // данный код выполнится после тестов
   // (здесь надо закрыть пхп-код "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>