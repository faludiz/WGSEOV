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

class tvetulet {
  //WGSEOV konstansok
  const r = 6370000;
  const ro = 206264.806247;
  const rofok = 57.2957795130824;
  const gauss_r = 6379743.001;                            //  Gauss gömb sugara
  const nfn = 0.823213630523992;                          //  fi_n = 47-10-00,normal paralell on the ellipsoid
  const kfn = 0.822438208856524;                          //  fi_n = 47-07-20.0578, normal paralell on the sphere
  const f0 = 0.822050077689329;                           //  fi_0 = 47-06-00, origin of projection (gellerthegy)
  const l0 = 0.332460295324692;                           //  lambda_0 = 19-02-54.8584, origin of longitude on the ellipsoid
  const m0 = 0.99993;                                     //  méretarány
  const k2 = 1.0007197049;                                //  this is k2 in the "9. annex/1.a and 2.b" formula
  const av = 1.00155641;                                  //  this is called as c4 in the "9. annex/2.b" formula
  const bv = 0.000000024436;                              //                    c5
  const cv = 6.5e-15;                                     //                    c6
  const an = 0.99844601;
  const bn = 0.000000024323;
  const cn = 5.3e-15;
  const grs_a = 6378160;                                  //  the "a" axis of the grs67 ellipsoid
  const grs_b = 6356774.516;                              //  the "b" axis of the grs67 ellipsoid
  const grs_en = 6.69460535691779e-03;
  const grs_flat = 3.35292372721916e-03;
  const wgs_a = 6378137;                                  //  the "a" axis of the wgs84 ellipsoid
  const wgs_b = 6356752.314;                              //  the "b" axis of the wgs84 ellipsoid
  const wgs_en = 6.6943800667647e-03;
  const wgs_flat = 3.35281070318806e-03;
  
  function sqr($val) {
    //szám négyzete
    return $val*$val;
  }
  
  function filah_xyz($fi,$lambda,$h, $major_axis,$num_exc) {
    //ellipszoidi koordináták átszámítása geocentrikus koordinátákká
    $rn = $major_axis / sqrt(1 - $num_exc * $this->sqr(sin($fi)));
    $x = ($rn + $h) * cos($fi) * cos($lambda);
    $y = ($rn + $h) * cos($fi) * sin($lambda);
    $z = ($rn * (1 - $num_exc) + $h) * sin($fi);
	return array($x,$y,$z);
  }

  function xyz_filah($x, $y, $z, $major_axis, $num_exc) {
    //geocentrikus koodináták átalakítása ellipszoidi koordinátákká
    $f = 1 - sqrt($major_axis * $major_axis - $num_exc * $major_axis * $major_axis) / $major_axis;
    $p = sqrt($x * $x + $y * $y);
    $rh = sqrt($p * $p + $z * $z);
    $u = atan(($z / $p) * ((1 - $f) + ($num_exc * $major_axis) / $rh));
    $lambda = atan($y / $x);
    $fent = $z * (1 - $f) + $num_exc * $major_axis * sin($u) * sin($u) * sin($u);
    $lent = (1 - $f) * ($p - $num_exc * $major_axis * cos($u) * cos($u) * cos($u));
    $fi = atan($fent / $lent);
    $h = $p * cos($fi) + $z * sin($fi) - $major_axis * sqrt(1 - $num_exc * sin($fi) * sin($fi));
    return array($fi,$lambda,$h);
  }
  
  function wgstoeur($fwgs,$lwgs,$hwgs) {
    //WGS84 ellipszoidi koordináták átszámítása WGS84 geocentrikus koordinátákká
    $arr = $this->filah_xyz($fwgs*pi()/180,$lwgs*pi()/180,$hwgs,self::wgs_a,self::wgs_en);
    $xeur = $arr[0];
    $yeur = $arr[1];
    $zeur = $arr[2];
    return array($xeur,$yeur,$zeur);
  }   
  
  function eurtowgs($xeur,$yeur,$zeur) {
    //WGS84 geocentrikus koodináták átalakítása WGS84 ellipszoidi koordinátákká
    $arr = $this->xyz_filah($xeur,$yeur,$zeur,self::wgs_a,self::wgs_en);
	$fwgs = $arr[0]*180/pi();
	$lwgs = $arr[1]*180/pi();
	$hwgs = $arr[2];
    return array($fwgs,$lwgs,$hwgs);
  } 
  
  function eovtogrs($yeov,$xeov,$heov) {
    //EOV koordináták átszámítása a GRS67 geocentrikus koordinátákká
    //eov-gauss-start
    $y = $yeov - 650000;
    $x = $xeov - 200000;
    $fiv = 2 * (atan(exp($x / self::gauss_r / self::m0)) - pi() / 4);
    $lav = $y / self::gauss_r / self::m0;
    $sf = (cos(self::f0) * sin($fiv) + sin(self::f0) * cos($fiv) * cos($lav));
    $fi = atan($sf / sqrt(1 - $sf * $sf));
    $sl = (sin($lav) * cos($fiv) / cos($fi));
    $la = atan($sl / sqrt(1 - $sl * $sl));
    //eov-gauss_end

    //gauss-grs67_start
    $df = ($fi - self::kfn) * self::ro;
    $dnf = (self::av * $df - self::bv * $df * $df + self::cv * $df * $df * $df) / self::ro;
    $nf = self::nfn + $dnf;      //fi on the grs67 ellipsoid in radian
    $dnl = $la / self::k2;
    $nl = self::l0 + $dnl;       //lambda on the grs67 ellipsoid in radian
    //gauss-grs67_end

	$arr = $this->filah_xyz($nf,$nl,$heov, self::grs_a, self::grs_en);
	$xgrs = $arr[0];
	$ygrs = $arr[1];
	$zgrs = $arr[2];
	
    return array($xgrs,$ygrs,$zgrs);
  }  
  
  function grstoeov($xgrs, $ygrs, $zgrs) {
    //GRS67 geocentrikus koordináták átszámítása EOV-ba
    $arr=$this->xyz_filah($xgrs, $ygrs, $zgrs, self::grs_a, self::grs_en);
	$fi=$arr[0];
	$la=$arr[1];
	$heov=$arr[2];
    //from grs67 ellipsoid to gauss sphere
    $df = ($fi - self::nfn) * self::ro;
    $df = (self::an * $df + self::bn * $df * $df - self::cn * $df * $df * $df) / self::ro;
    $dl = $la - self::l0;
    $f = self::kfn + $df;
    $l = self::k2 * $dl;

    //from gauss sphere to eov plane
    $fiv = sin($f) * cos(self::f0) - cos($f) * sin(self::f0) * cos($l);
    $fiv = atan($fiv / sqrt(1 - $fiv * $fiv));
    $lav = cos($f) * sin($l) / cos($fiv);
    $lav = atan($lav / sqrt(1 - $lav * $lav));
    $xeov = self::gauss_r * self::m0 * log(tan(pi() / 4 + $fiv / 2));
    $yeov = self::gauss_r * self::m0 * $lav;
    $xeov = $xeov + 200000;
    $yeov = $yeov + 650000;
    return array($yeov,$xeov,$heov); 
  }  
  
  function eovtoeur_sevenprm($dx, $dy, $dz, $rx, $ry, $rz, $sf, $eovy, $eovx, $eovh) {
    // EOV koordináták átszámítása WGS84 geocentrikus rendszerbe 7 paraméterrel
    $arr = $this->eovtogrs($eovy,$eovx,$eovh);
	$grsx = $arr[0];
	$grsy = $arr[1];
	$grsz = $arr[2];
	
    $frx = (($rx / 3600) / 180) * pi();
    $fry = (($ry / 3600) / 180) * pi();
    $frz = (($rz / 3600) / 180) * pi();

    $m11 = cos($fry) * cos($frz);
    $m12 = cos($fry) * sin($frz);
    $m13 = sin($fry) * -1;

    $m21 = (sin($frx) * sin($fry) * cos($frz)) - (cos($frx) * sin($frz));
    $m22 = (sin($frx) * sin($fry) * sin($frz)) + (cos($frx) * cos($frz));
    $m23 = sin($frx) * cos($fry);

    $m31 = (cos($frx) * sin($fry) * cos($frz)) + (sin($frx) * sin($frz));
    $m32 = (cos($frx) * sin($fry) * sin($frz)) - (sin($frx) * cos($frz));
    $m33 = cos($frx) * cos($fry);	
	
    $grsx = $grsx-$dx;
    $grsy = $grsy-$dy;
    $grsz = $grsz-$dz;
    $eurx = ($m11 * $grsx + $m21 * $grsy + $m31 * $grsz) / $sf;
    $eury = ($m12 * $grsx + $m22 * $grsy + $m32 * $grsz) / $sf;
    $eurz = ($m13 * $grsx + $m23 * $grsy + $m33 * $grsz) / $sf;	
	
	return array($eurx,$eury,$eurz);
  }
  
  function eurtoeov_sevenprm($dx, $dy, $dz, $crx, $cry, $crz, $sf, $eurx, $eury, $eurz) {
    //WGS84 geocentrikus koordináták átszámítása EOV-ba
    $rx = (($crx / 3600) / 180) * pi();
    $ry = (($cry / 3600) / 180) * pi();
    $rz = (($crz / 3600) / 180) * pi();

    $m11 = cos($ry) * cos($rz);
    $m12 = cos($ry) * sin($rz);
    $m13 = sin($ry) * -1;

    $m21 = (sin($rx) * sin($ry) * cos($rz)) - (cos($rx) * sin($rz));
    $m22 = (sin($rx) * sin($ry) * sin($rz)) + (cos($rx) * cos($rz));
    $m23 =  sin($rx) * cos($ry);

    $m31 = (cos($rx) * sin($ry) * cos($rz)) + (sin($rx) * sin($rz));
    $m32 = (cos($rx) * sin($ry) * sin($rz)) - (sin($rx) * cos($rz));
    $m33 =  cos($rx) * cos($ry);

    $grsx = $dx + $sf * ($m11 * $eurx + $m12 * $eury + $m13 * $eurz);
    $grsy = $dy + $sf * ($m21 * $eurx + $m22 * $eury + $m23 * $eurz);
    $grsz = $dz + $sf * ($m31 * $eurx + $m32 * $eury + $m33 * $eurz);	
	
	$arr = $this->grstoeov($grsx,$grsy,$grsz);
	$eovy = $arr[0];
	$eovx = $arr[1];
	$eovh = $arr[2];
    
    return array($eovy,$eovx,$eovh);
  }  
  
}

?>
