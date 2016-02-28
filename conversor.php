<?php


///////////////////Convertimos el mensaje y la contraseña de hexadecimal a binario

//hexa-bin
$conversion=array('0'=>'0000','1'=>'0001','2'=>'0010','3'=>'0011','4'=>'0100','5'=>'0101','6'=>'0110', '7'=>'0111', '8'=>'1000','9'=>'1001','A'=>'1010', 'B' => '1011', 'C'=>'1100', 'D'=>'1101', 'E'=>'1110','F'=>'1111');

//message
$m_hexa=$_POST['texto'];
$m_hexa = strtoupper(str_pad($m_hexa,16,0, STR_PAD_LEFT));

$m_bin="";

$split=str_split($m_hexa);
for($i=0;$i<count($split); $i++) {
    $m_bin=$m_bin.$conversion[$split[$i]];
}

//password
$k_hexa=$_POST['password'];
$k_hexa =str_pad(strtoupper($k_hexa),16,0, STR_PAD_LEFT);
$k_bin="";

$split=str_split($k_hexa);
for($i=0;$i<count($split); $i++) {
    $k_bin=$k_bin.$conversion[$split[$i]];
}

///////////////////Ciframos el mensaje

$l=substr($m_bin,0,32);
$r=substr($m_bin,32,32);


//////////Planificación de contraseña


///Permutamos con la tabla PC-1
$tabla = array(57,49,41,33,25,17,9,1,58,50,42,34,26,18,10,2,59,51,43,35,27,19,11,3,60,52,44,36,63,55,47,39,31,23,15,7,62,54,46,38,30,22,14,6,61,53,45,37,29,21,13,5,28,20,12,4);
$split=str_split($k_bin);
$k_mas = "";
for ($i=0;$i<count($tabla);$i++) {
    $k_mas=$k_mas.$split[$tabla[$i]-1];
}

//Dividimos la clave permutada en C y D

$c=substr($k_mas,0,28);
$d=substr($k_mas,28,28);


//Paso 1: Crear 16 sub-claves de 48 bits cada una

$iteraciones = array(1,1,2,2,2,2,2,2,1,2,2,2,2,2,2,1);
$c_iteraciones = array("c0" => $c);
$d_iteraciones = array("d0" => $d);

for ($i=1, $j=0;$i<=count($iteraciones);$i++,$j++) {
    $a = substr($c_iteraciones["c".$j],0, $iteraciones[$j]);
    $b = substr($c_iteraciones["c".$j],$iteraciones[$j],28);
    $c_iteraciones["c".$i] = $b.$a;



    $a = substr($d_iteraciones["d".$j],0, $iteraciones[$j]);
    $b = substr($d_iteraciones["d".$j],$iteraciones[$j],28);
    $d_iteraciones["d".$i] = $b.$a;


}


//permutación 2

//Concatenamos las claves de la forma CnDn

$cndn=array();

for ($i=0;$i<count($c_iteraciones);$i++) {
    $cndn['c'.$i.'d'.$i] = $c_iteraciones['c'.$i].$d_iteraciones['d'.$i];

}

//Realizamos permutación 2
$tabla = array(14,17,11,24,1,5,3,28,15,6,21,10,23,19,12,4,26,8,16,7,27,20,13,2,41,52,31,37,47,55,30,40,51,45,33,48,44,49,39,56,34,53,46,42,50,36,29,32);
$k_iteraciones = array();

for($i=1;$i<=(count($cndn)-1);$i++) {
    $split=str_split($cndn['c'.$i.'d'.$i]);
    $k_iteraciones['k'.$i] ="";
    for ($c=0;$c<count($tabla);$c++){
        $k_iteraciones['k'.$i] = $k_iteraciones['k'.$i].$split[$tabla[$c]-1];
    }
}


$tabla = array(58,50,42,34,26,18,10,2,60,52,44,36,28,20,12,4,62,54,46,38,30,22,14,6,64,56,48,40,32,24,16,8,57,49,41,33,25,17,9,1,59,51,43,35,27,19,11,3,61,53,45,37,29,21,13,5,63,55,47,39,31,23,15,7);
$ip="";
$split=str_split($m_bin);

for($i=0;$i<count($tabla);$i++) {
    $ip=$ip.$split[$tabla[$i]-1];
}

//Se permuta usando la tabla E
$kn=array("izq"=>array(substr($ip,0,32)), "der"=> array(substr($ip,32,32)));

for ($h=0, $z=1;$h<16;$h++,$z++) {

$tabla = array(32,1,2,3,4,5,4,5,6,7,8,9,8,9,10,11,12,13,12,13,14,15,16,17,16,17,18,19,20,21,20,21,22,23,24,25,24,25,26,27,28,29,28,29,30,31,32,1);

$split=str_split($kn["der"][$h]);


    $split=str_split($kn["der"][$h]);
    $r="";
for($i=0;$i<count($tabla);$i++) {
    $r=$r.$split[$tabla[$i]-1];
}


    //Operación XOR kn y r

    $r_split=str_split($r);
    $k_iteraciones_split=str_split($k_iteraciones["k".$z]);
    $xor="";
    for($i=0;$i<count($r_split);$i++) {
        $a="";
        if($r_split[$i] != $k_iteraciones_split[$i]) {
            $a=1;
        }else {
            $a=0;
        }
        $xor=$xor.$a;
    }


    //s-cajas

    $eses = array(
        's1' => array(array(14,4,13,1,2,15,11,8,3,10,6,12,5,9,0,7),
            array(0,15,7,4,14,2,13,1,10,6,12,11,9,5,3,8),
            array(4,1,14,8,13,6,2,11,15,12,9,7,3,10,5,0),
            array(15,12,8,2,4,9,1,7,5,11,3,14,10,0,6,13)
        ),
        's2' => array(array(15,1,8,14,6,11,3,4,9,7,2,13,12,0,5,10),
            array(3,13,4,7,15,2,8,14,12,0,1,10,6,9,11,5),
            array(0,14,7,11,10,4,13,1,5,8,12,6,9,3,2,15),
            array(13,8,10,1,3,15,4,2,11,6,7,12,0,5,14,9)
        ),
        's3' => array(array(10,0,9,14,6,3,15,5,1,13,12,7,11,4,2,8),
            array(13,7,0,9,3,4,6,10,2,8,5,14,12,11,15,1),
            array(13,6,4,9,8,15,3,0,11,1,2,12,5,10,14,7),
            array(1,10,13,0,6,9,8,7,4,15,14,3,11,5,2,12)
        ),
        's4' => array(array(7,13,14,3,0,6,9,10,1,2,8,5,11,12,4,15),
            array(13,8,11,5,6,15,0,3,4,7,2,12,1,10,14,9),
            array(10,6,9,0,12,11,7,13,15,1,3,14,5,2,8,4),
            array(3,15,0,6,10,1,13,8,9,4,5,11,12,7,2,14)
        ),
        's5' => array(array(2,12,4,1,7,10,11,6,8,5,3,15,13,0,14,9),
            array(14,11,2,12,4,7,13,1,5,0,15,10,3,9,8,6),
            array(4,2,1,11,10,13,7,8,15,9,12,5,6,3,0,14),
            array(11,8,12,7,1,14,2,13,6,15,0,9,10,4,5,3)
        ),
        's6' => array(array(12,1,10,15,9,2,6,8,0,13,3,4,14,7,5,11),
            array(10,15,4,2,7,12,9,5,6,1,13,14,0,11,3,8),
            array(9,14,15,5,2,8,12,3,7,0,4,10,1,13,11,6),
            array(4,3,2,12,9,5,15,10,11,14,1,7,6,0,8,13)
        ),
        's7' => array(array(4,11,2,14,15,0,8,13,3,12,9,7,5,10,6,1),
            array(13,0,11,7,4,9,1,10,14,3,5,12,2,15,8,6),
            array(1,4,11,13,12,3,7,14,10,15,6,8,0,5,9,2),
            array(6,11,13,8,1,4,10,7,9,5,0,15,14,2,3,12)
        ),
        's8' => array(array(13,2,8,4,6,15,11,1,10,9,3,14,5,0,12,7),
            array(1,15,13,8,10,3,7,4,12,5,6,11,0,14,9,2),
            array(7,11,4,1,9,12,14,2,0,6,10,13,15,3,5,8),
            array(2,1,14,7,4,10,8,13,15,12,9,0,3,5,6,11)
        ));

    $completo=array();
        $s="";
        for($c=1, $j=0;$c<=8;$c++,$j+=6) {
            $grupos=substr($xor,$j,6);
            $fila=bindec(substr($grupos,0,1).substr($grupos,5,1));
            $columna=bindec(substr($grupos,1,4));
            $s=$s.str_pad(decbin($eses['s'.$c][$fila][$columna]),4,0, STR_PAD_LEFT);
        }



    //Tabla de permutación S

    $tabla=array(16,7,20,21,29,12,28,17,1,15,23,26,5,18,31,10,2,8,24,14,32,27,3,9,19,13,30,6,22,11,4,25);
    $split=str_split($s);
    $f="";

    for($i=0;$i<count($tabla);$i++) {
        $f=$f.$split[$tabla[$i]-1];
    }
    //Xor f kn[izq]
    $f_split=str_split($f);
    $kn_split=str_split($kn["izq"][$h]);

    $xor="";
    $a="";
    for ($i = 0; $i<count($f_split);$i++) {
        if ($f_split[$i] != $kn_split[$i]) {
            $a=1;
        }else {
            $a=0;
        }
        $xor=$xor.$a;
    }

    array_push($kn["izq"],$kn["der"][$h]);
    array_push($kn["der"],$xor);

}

//Permutamos con IP-1
$rn= $kn["der"][16].$kn["izq"][16];
$split=str_split($rn);
$tabla=array(40,8,48,16,56,24,64,32,39,7,47,15,55,23,63,31,38,6,46,14,54,22,62,30,37,5,45,13,53,21,61,29,36,4,44,12,52,20,60,28,35,3,43,11,51,19,59,27,34,2,42,10,50,18,58,26,33,1,41,9,49,17,57,25);
$ip1="";
for($i=0;$i<count($tabla);$i++) {
    $ip1=$ip1.$split[$tabla[$i]-1];
}
$conversion=array('0000'=>'0','0001'=>'1','0010'=>'2','0011'=>'3','0100'=>'4','0101'=>'5','0110'=>'6', '0111'=>'7', '1000'=>'8','1001'=>'9','1010'=>'A', '1011' => 'B', '1100'=>'C', '1101'=>'D', '1110'=>'E','1111'=>'F');
$resultado="";
for($i=0,$j=0;$i<16;$i++,$j+=4){
    $resultado=$resultado.$conversion[substr($ip1,$j,4)];
}
echo $resultado;