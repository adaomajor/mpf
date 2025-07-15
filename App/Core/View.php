<?php
    namespace MPF\Core;

    use Exception;
    define('VIEW_PATH','App/Views/');
    class View{
        private static $view;
        private static $data;

        public function __construct($viewName){
            if(file_exists(VIEW_PATH . $viewName.".view.php")){
                self::$view = VIEW_PATH.$viewName.".view.php";
            }else {
                throw new Exception("No view with name: ". $viewName . " found.");
            }
        }

        public static function set($key, $value){
            self::$data[$key] = $value;
        }

        public static function get($key) {
            return (isset(self::$data[$key])) ? self::$data[$key] : "";
        }

        public static function view($viewName){
            ob_start();
            $View = self::class;
            $view = self::class;
            require_once VIEW_PATH.$viewName.".view.php";
            echo ob_get_clean();
            return;
        }
        
        public static function render(){
            ob_start();
            $View = self::class;
            $view = self::class;
            require_once self::$view;
            echo ob_get_clean();
            return;
        }
    }

   //test
//    $view = new View("footer"); 
//    $view::data("post","hi I am Adam Major");
//    $view::data("user", ["Adam","Lil Wiez","Toloba"]);
//    $view::render();
   
//    //$view::json();

//    //var_dump($view);
//     View::view("404");

?>