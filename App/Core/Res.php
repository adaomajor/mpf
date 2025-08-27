<?php
    namespace MPF\Core;

    use Exception;

    class Res{
        public static function cookie ($cookies, $time = null , $path = "/"){
            if(!is_array($cookies)){
                throw new Exception("the cookies argumet must be an array: ['name' => 'adaomajor']");
            }
            foreach($cookies as $cookiename => $cookievalue){
                if(!isset($time)){ $time = time() + 3600; }
                setcookie($cookiename, $cookievalue, $time, $path);
            }
        }
        public static function delcookie($cookiename){
            setcookie($cookiename, "", time() - 3600);
        }
        public static function header($STRING_HEADER){
            header($STRING_HEADER);
        }
        public static function json($data){
            header("Content-Type: application/json; charset=utf8");
            echo json_encode($data);
            return;
        }
        public static function session($sessions){
            if(!is_array($sessions)){ throw new Exception("the sessions arguments must be an array: ['name' => 'adaomajor']");}
            session_start();
            foreach($sessions as $sessionKey => $sessionValue){
                $_SESSION[$sessionKey] = $sessionValue;
            }
        }
        public static function delsession($session){
            session_start();
            session_unset();
            session_destroy();
        }
        public static function status($code){
            http_response_code($code);
        }
        public static function redirect($path){
            self::status(302);
            $path = 'Location: '.$path;
            self::header($path);
        }
    }

?>