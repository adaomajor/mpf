<?php
    namespace MPF\Core\DB;
    use PDO;
    use Exception;

    require_once (__DIR__.'/../config.php');

    class DB{
        private static $instance = null;
        private $connection;
        
        private function __construct(){
            try{
                $this->connection =  new PDO("mysql:host=".HOST.";dbname=".DB, USER, PASSWD);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }catch(Exception $Ex){
                throw new Exception("Error: ".$Ex->getMessage());
            }
        }

        public static function getConnection(){
            if(self::$instance === null){
                self::$instance = new DB();
            }
            return self::$instance->connection;
        }
    }
?>