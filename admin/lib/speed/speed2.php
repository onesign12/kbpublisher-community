<?php       
   
   // ������� ��� ��������� ��� ������� �������� �����
   $cn=50000;
  
   // ������ ��� ���������� ����� �������
   // (����� ���� ������� ���-��� "<?php")
   $phpbegin='<?php';

   // ��� ����� ������ (�� 0 �� N) - �� ������ �� �������� ������
   // ����������� �������� � ��������� �������, ������ ������ ������������
   $phptest='
$x=1;
$x2=1;
$x03=1;
$x004=1;
$x0005=1;
$x00006=1;
$x000007=1;
$x0000008=1;
$x000000010=1;
$x00000000012=1;
$x0000000000014=1;
$x000000000000016=1;
$x0000000000000000000000000000032=1;
   ';

   // ��������� ����� ��������� �� $test (������� �� ����� ������)
   $phpexplode="\r\n";

   // ������ ��� ���������� ����� ������
   // (����� ���� ������� ���-��� "<?php")
   $phpend='?>';

   include("_dima_speed.php");

?>