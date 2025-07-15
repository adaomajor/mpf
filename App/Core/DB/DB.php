<?php
    namespace MPF\Core\DBTRAIT;
    use PDO;
    use Exception;

    trait DB{
        private static $DB;
        private static $connection;
        
        private static function getConnection(){
            try{
                self::$connection =  new PDO("mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASSWD']);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                return self::$connection;
            }catch(Exception $Ex){
                throw new Exception("Error: ".$Ex->getMessage());
            }
        }

        public static function init(){
            if(self::$DB === null){
                self::$DB = self::getConnection();
            }
            //return self::$DB;
        }
    }
?>