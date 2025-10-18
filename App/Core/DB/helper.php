<?php 
    use MPF\Models;

    function Debug($message){
            $env = getenv("DEBUG");
            if(isset($env) && $env == "DEBUG" || $env == "TRUE"){
                return throw new Exception($message);
            }else{
                //echo "PROBABLY IN PRODUCTION";
                //return ;
            }
        }
    class Helper{

        
        public static function loadclass($className){
            $path = realpath(__DIR__."/../../Models/".$className.".php");
            if(!file_exists($path)){
                Debug("No model file ".$className.".php : check the models folders");
            }

            $x = "MPF\\Models\\$className";
            if(!class_exists($x)){
                Debug("No Model Class ".$className.": in the file:".$path);
            }
            return new $x();
        }
    }
?>