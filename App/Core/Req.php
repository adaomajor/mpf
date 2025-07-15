<?php
    namespace MPF\Core;

    class Req{
        public static function get($key){
            return (isset($_GET[$key]) && !empty($_POST[$key])) ? $_GET[$key] : null; 
        }

        public static function post($key){
            return (isset($_POST[$key]) && !empty($_POST[$key])) ? $_POST[$key] : null;
        }

        public static function cookie($cookie){
            return (isset($_COOKIE[$cookie])) ? $_COOKIE[$cookie] : null;
        }

        public static function header($header){
            $headers = getallheaders();
            return (isset($headers[$header])) ? $headers[$header] : null;
        }

        public static function json(){
            $data = json_decode(file_get_contents("php://input", true), true);
            if(json_last_error() === JSON_ERROR_NONE){
                return $data;
            }
            return null;
        }

        public static function session($session){
            if(!isset($_SESSION)){ session_start(); }
            //session_start();
            return (isset($_SESSION[$session])) ? $_SESSION[$session] : null;
        }


        public static function file($filename){
            //if(isset($_FILE[])){

            return 0; 
        }
    }

?>