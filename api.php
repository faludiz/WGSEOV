<?php 

/*
    WgsEov - WGS84-EOV transformation
    Copyright (C) 2010  Zoltan Faludi  <zoltan.faludi@gmail.com>
    This file is part of WgsEov http://wgseov.sourceforge.net

    WgsEov is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WgsEov.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
http://192.168.0.40:8765/api.php?mode=we&x=47&y=18&z=100
http://192.168.0.40:8765/api.php?mode=we&x=4144501.278&y=1346630.106&z=4641870.760
http://192.168.0.40:8765/api.php?mode=ew&y=584000&x=850000&h=123

EOV -> WGS84:
=============
api.php?mode=ew&y=584000&x=850000&h=123

ahol:
mode=ew : átszámítás EOV-ból WGS84-be
y= EOV Y
x= EOV X
h= EOV H (balti magasság)

eredmény:
WGS84 X
WGS84 Y
WGS84 Z
WGS84 fi
WGS84 la
WGS84 h

WGS84 ->EOV:
=============

api.php?mode=we&x=4144501.278&y=1346630.106&z=4641870.760

api.php?mode=we&x=47&y=18&z=100


*/

include 'wgseov.php';

$mode=$_GET['mode'];

if ($mode=="ew") {

  $yeov=$_GET['y'];
  $xeov=$_GET['x'];
  $heov=$_GET['h'];

  $arr=eov_wgs84($yeov,$xeov,$heov);
  $eurx=$arr[0];
  $eury=$arr[1];
  $eurz=$arr[2];
  $fi=$arr[3];
  $la=$arr[4];
  $h=$arr[5];
  print(sprintf("%01.3f;", $eurx));
  print(sprintf("%01.3f;", $eury));
  print(sprintf("%01.3f;", $eurz));
  print(sprintf("%01.9f;", $fi));
  print(sprintf("%01.9f;", $la));
  print(sprintf("%01.3f;", $h));  

} else if ($mode=="we") {
  
  $xwgs=$_GET['x'];
  $ywgs=$_GET['y'];
  $zwgs=$_GET['z'];
  
  $arr=wgs84_eov($xwgs,$ywgs,$zwgs);
  $eovy=$arr[0];
  $eovx=$arr[1];
  $eovh=$arr[2];

  print(sprintf("%01.3f;", $eovy));
  print(sprintf("%01.3f;", $eovx));
  print(sprintf("%01.3f;", $eovh));  

} else if ($mode=="help") {

  print("Súgó");
  
} else {

  print('<a href="./api.php?mode=help">hiba</a>');
  
}

?>
