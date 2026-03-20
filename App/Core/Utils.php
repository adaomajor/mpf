<?php
    namespace MPF\core;
    use Exception;

    function Debug($message){
        $env = getenv("DEBUG");
        if(isset($env) && $env == "DEBUG" || $env == "TRUE"){
            throw new Exception($message);
        }else{
            //echo "PROBABLY IN PRODUCTION";
            //return ;
        }
    }
?>
