<?php
function consultaParserCorreu($servicios) {
        $mail="";
        $nomail="sinmail";
        $pattern_str = "#([a-zA-Z0-9._%-]+@[a-zA-Z0-9._%-]+\.[a-zA-Z]{2,4})#";
        if (preg_match($pattern_str, implode($servicios) , $regs)>0)
        {
                $mail = $regs[1];
                if(stripos($mail,"@uab.cat")) return $mail;
                if(stripos($mail,"@uab.es")) return $mail;
                if(stripos($mail,"@campus.uab.cat")) return $mail;
                if(stripos($mail,"@campus.uab.es")) return $mail;
                if(stripos($mail,"@e-campus.uab.cat")) return $mail;
                if(stripos($mail,"@e-campus.uab.es")) return $mail;
        }
        return $nomail;
}
?>
