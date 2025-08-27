<?php
    namespace MPF\Core\DBTRAIT;
    use PDO;
    use Exception;

    trait DB{
        private static $DB;
        private static $connection;
        private static function getConnection(){
            try{
                self::$connection =  new PDO("mysql:host=".getenv('DB_HOST').";dbname=".getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWD'));
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                return self::$connection;
            }catch(Exception $Ex){
                echo "[!] verify your database credentials or if the DB server is really up";
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