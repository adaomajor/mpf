<?php
    namespace MPF\Database;
    require_once 'DB.php';
    
    use MPF\Core\DB\DB;

    trait DatabaseTrait{
        private static $DB = null;

        private static function init(){
            if(self::$DB === null){
                self::$DB = DB::getConnection();
            }
        }
    }
?>