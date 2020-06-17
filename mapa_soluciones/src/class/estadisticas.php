<?php

class Proyecto {


    function porcentajes4variables($orden, $var1, $var2, $var3, $var4){

        function regla($var1 , $total){

            $resultado = ($var1 * 100) / $total;
            return $resultado;

        }

        $Porvar3 = null;
        $Porvar4 = null;
        
        $total = $var1 + $var2 + $var3 + $var4;

        $Porvar1 = regla($var1,$total);

        $Porvar2 = regla($var2,$total);

        if (!empty($var3)) {
            $Porvar3 = regla($var3,$total);
        }

        if (!empty($var4)) {
            $Porvar4 = regla($var4,$total);
        }

        return $json = ([
            "Total General" => $total,
            "Orden de Porcentajes" => $orden,
            "Porcentaje Uno" => $Porvar1,
            "Porcentaje Dos" => $Porvar2,
            "Porcentaje Tres" => $Porvar3,
            "Porcentaje Cuatro" => $Porvar4
        ]);


    }   
    
}


?>