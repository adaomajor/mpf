<?php 
    use MPF\Models;
    class Helper{

        public static function loadclass($className){
            $path = realpath(__DIR__."/../../Models/".$className.".php");
            if(!file_exists($path)){
                throw new Exception("No model file ".$className.".php : check the models folders");
            }

            $x = "MPF\\Models\\$className";
            if(!class_exists($x)){
                throw new Exception("No Model Class ".$className.": in the file:".$path);
            }
            return new $x();
        }
    }
?>