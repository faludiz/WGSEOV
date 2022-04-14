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

include 'vetulet.php';
include 'settings.php';

// mysql kapcsolódás
$con = mysqli_connect($hostname, $username, $password); 

// kapcsolat tesztelése

if ($con->connect_error) {
	die("sikertelen kapcsolat" . $con->connect_error);
}

// adatbázis kiválasztása

mysqli_select_db($con, $db) or die("Could not select database");

function dist ($x1, $y1, $x2, $y2) {
  //két pont közötti távolság számítása
  $d=sqrt( pow($x2-$x1,2)+pow($y2-$y1,2) );
  if ($d==0) { 
    $d=1E-16; //hogy véletlenül se legyen 0-val való osztás
  }
  return $d;
}

function get_prm($prm) {
  //paraméterek lekérdezése
  global $con,$mod_prm;
  $sql = "SELECT * FROM `".$mod_prm."` WHERE `prm` = '".$prm."'";
  //$res = mysqli_query($con, $sql);
  //$row = mysqli_free_result($res);
  
  if ($res = mysqli_query($con, $sql)) {
    $row = mysqli_fetch_row($res);
    return  $row[1];
  }
}

function get_modell_eov_wgs84($eovy, $eovx) {
  //EOV->WGS84 modell lekérdezése
  global $con,$mod_eov_wgs;
  $ey=get_prm("ey");
  $ex=get_prm("ex");
  $sql="SELECT *  FROM `".$mod_eov_wgs."` WHERE `y` > ".$eovy."-".$ey." AND `y` < ".$eovy."+".$ey." AND `x` > ".$eovx."-".$ex." AND `x` < ".$eovx."+".$ex."";
  
  if ($res = mysqli_query($con, $sql)) {
 
    //$count = mysqli_num_fields($res);
  
    //echo "count: " . $count . "\n";
  
    $sump=0; //súlyok összege
    $dfi=0;
    $dla=0;
    $dh=0;
    $c=0;
  
    while ($row = mysqli_fetch_row($res)) {
      $my=$row[0];
      $mx=$row[1];
	  $p=1000/dist($eovy,$eovx,$my,$mx);
	  $dfi=$dfi+$row[2]*$p;
	  $dla=$dla+$row[3]*$p;
	  $dh=$dh+$row[4]*$p;	
	  $sump=$sump+$p;
	  $c=$c+1;
    } 

  	if (c > 0) {
      return array($dfi/$sump,$dla/$sump,$dh/$sump);
	} else {
	  return array(0.0,0.0,0.0);
    } 
	
  } else {
	  return array(0.0,0.0,0.0);
  }
}

function get_modell_wgs84_eov($fi, $la) {
  //WGS84->EOV modell lekérdezése
  global $con,$mod_wgs_eov;
  $ef=get_prm("ef");
  $el=get_prm("el");
  $sql="SELECT *  FROM `".$mod_wgs_eov."` WHERE `fi` > ".$fi."-".$ef." AND `fi` < ".$fi."+".$ef." AND `la` > ".$la."-".$el." AND `la` < ".$la."+".$el."";
  $res = mysqli_query($con, $sql);
 
  $count = mysqli_num_fields($res);
  
  $sump=0; //súlyok összege
  $dy=0;
  $dx=0;
  $dz=0;
  
  while ($row = mysqli_fetch_row($res)) {
    $mfi=$row[0];
    $mla=$row[1];
	$p=1/dist($fi,$la,$mfi,$mla);
	$dy=$dy+$row[2]*$p;
	$dx=$dx+$row[3]*$p;
	$dz=$dz+$row[4]*$p;	
	$sump=$sump+$p;
  } 

  return array($dy/$sump,$dx/$sump,$dz/$sump);	
}

function eov_wgs84($eovy,$eovx,$eovh) {
  //átszámítás EOV-ból WGS84-be
  $vet=new tvetulet();
  
  $dx=get_prm("dx");
  $dy=get_prm("dy");
  $dz=get_prm("dz");
  $rx=get_prm("rx");
  $ry=get_prm("ry");
  $rz=get_prm("rz");  
  $m=get_prm("m"); 

  $arr=$vet->eovtoeur_sevenprm($dx,$dy,$dz,$rx,$ry,$rz,$m,$eovy,$eovx,$eovh);
  $eurx=$arr[0];
  $eury=$arr[1];
  $eurz=$arr[2];
  
  $arr=get_modell_eov_wgs84($eovy,$eovx);
  $dx=$arr[0];
  $dy=$arr[1];
  $dz=$arr[2];
  
  $eurx=$eurx+$dx;
  $eury=$eury+$dy;
  $eurz=$eurz+$dz;
  
  $arr=$vet->eurtowgs($eurx,$eury,$eurz);
  $fi=$arr[0];
  $la=$arr[1];
  $h=$arr[2];
  
  return array($eurx,$eury,$eurz,$fi,$la,$h); 
}

function wgs84_eov($eurx,$eury,$eurz) {
  //átszámítás WGS84-bol EOV-ba
  $vet=new tvetulet();
  
  if ((50<$eurx+$eury+$eurz) && ($eurx+$eury+$eurz<1000)) {
    $arr=$vet->wgstoeur($eurx,$eury,$eurz);
	$eurx=$arr[0];
	$eury=$arr[1];
	$eurz=$arr[2];
  }
  
  $dx=get_prm("dx");
  $dy=get_prm("dy");
  $dz=get_prm("dz");
  $rx=get_prm("rx");
  $ry=get_prm("ry");
  $rz=get_prm("rz");  
  $m=get_prm("m"); 

  $arr=$vet->eurtoeov_sevenprm($dx,$dy,$dz,$rx,$ry,$rz,$m,$eurx,$eury,$eurz);
  $eovy=$arr[0];
  $eovx=$arr[1];
  $eovh=$arr[2];
  
  $arr=$vet->eurtowgs($eurx,$eury,$eurz);
  $fi=$arr[0];
  $la=$arr[1];
  
  $arr=get_modell_wgs84_eov($fi,$la);
  $dy=$arr[0];
  $dx=$arr[1];
  $dh=$arr[2];
  
  $eovy=$eovy+$dy;
  $eovx=$eovx+$dx;
  $eovh=$eovh+$dh;

  return array($eovy,$eovx,$eovh);
}

?>
