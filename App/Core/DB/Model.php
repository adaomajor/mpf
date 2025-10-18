<?php
    namespace MPF\Core\DB;
    require_once 'DB.php';
    require_once 'helper.php';
    
    use Exception;
    use Helper;
    use MPF\Core\DBTRAIT\DB;
    use PDO;

    class Model extends Helper {
        use DB;
        protected $table = "";
        protected $fields = [];

        private $query;
        private $Params = [];

        private $find = false;
        private $where = false;
        private $or = false;
        private $order = false;
        private $limit = false;
        private $count = false;
        private $update = false;
        private $delete = false;
        private $on = false;
        private $joined = false;
        private $plus = 0;
        private $jTable;
        private $join = "";

        private $SELECT = [];
        private $SELECT_JOIN = [];
        private $JOINS = [];
        private $WHERE = "";
        private $UPDATE = "";

        private $ORDER = "";
        private $LIMIT = "";


         private function Debug($message){
            $env = getenv("DEBUG");
            if(isset($env) && $env == "DEBUG" || $env == "TRUE"){
                return throw new Exception($message);
            }else{
                //echo "PROBABLY IN PRODUCTION";
                //return ;
            }
        }

        public static function PK(){
            return "INT PRIMARY KEY AUTO_INCREMENT NOT NULL ";
        }
        public static function int($nullable = null, $default = null){
            return (isset($nullable) ? "INT " : "INT NOT NULL ").(isset($default) ? " DEFAULT '".$default."'" : "");
        }
        public static function char($length = 50, $nullable = null, $default = null){
            return "VARCHAR(".$length.")".(isset($nullable) ? "" : " NOT NULL").(isset($default) ? " DEFAULT '".$default."'" : "");
        }
        public static function decimal($int = 10, $decimal = 2, $nullable = null, $default = null){
            return "DECIMAL(".$int.",". $decimal.")".(isset($nullable) ? "" : " NOT NULL").(isset($default) ? " DEFAULT '".$default."'" : "");
        }
        public static function text($nullable = null){
            return "TEXT".(isset($nullable) ? "" : " NOT NULL");
        }
        public static function bool($nullable = null, $default=false){
            if(isset($default)){
                $def = "FALSE";
                if($default == false){
                    $def = "FALSE";
                }else if($default == true){
                    $def = "TRUE";
                }
            }
            return "boolean".(isset($nullable) ? "" : " NOT NULL").(isset($default) ? " DEFAULT ".$def : "");
        }
        public static function enum($choices, $nullable = null, $default = null){
            if(is_array($choices)){
                $enum = "enum(";
                foreach($choices as $choice){
                    $enum .= "'".$choice."',";
                }
                $enum .= ")";
                $enum = str_replace(",)",")",$enum);
                return $enum.(isset($nullable) ? "" : " NOT NULL").(isset($default) ? " DEFAULT '".$default."'" : "");
            }

            throw new Exception("\$choices must be an array ['A','B','C'...]");
            
        }
        public static function date($now = null, $nullable = null){
            return "DATE".(isset($now) ? " DEFAULT (CURRENT_DATE)" : "").(isset($nullable) ? "" : " NOT NULL");
        }     
        public static function datetime($now = null, $nullable = null){
            return "DATETIME".(isset($now) ? " DEFAULT CURRENT_TIMESTAMP" : "" ).(isset($nullable) ? "" : " NOT NULL");
        }
        public static function GETCOL($col){
            $object = new static();
            return isset($object->fields[$col]) ? $object->fields[$col] : $object->Debug("the Model ".$object->table." has no field ".$col);
        }
        public static function FK($args){
            if(!is_array($args) || count($args) != 2){
                throw new Exception("the argument for Model::FK must an array of the elements: ['Model','Column']");
            }

            $referenceTable = self::loadclass(ucfirst($args[0]));
            $type = $referenceTable::GETCOL($args[1]);
            $type = explode(" ",$type)[0];
            $type = trim($type);

            if($type){
                if($type == "INT"){
                    return "INT NOT NULL |fk_table_".$args[0]."| |fk_column_".$args[1]."|";
                }else{
                    throw new Exception("you should just create FK with INT type");
                }
            }
        }

        public function getTable(){
            return $this->table;
        }
        private function migration(){
            $time = date("Y_m_d_H_i_s");
            $MODEL_PATH = realpath(__DIR__."/../../Models/");
            $MIGRATIONS_PATH = realpath(__DIR__."/../../Models/Migrations/".$this->table);
            $mcontent = file_get_contents($MODEL_PATH."/".$this->table.".php");
            $mfile = $MIGRATIONS_PATH."/".$this->table."_migration_".$time.".php";
            $class = $this->table."_migration_".$time;
            echo "[+] creating migration: ".$this->table."_migration_".$time."\n";
            $ucontent = preg_replace("/namespace.+;/","namespace MPF\\Models\\Migrations\\".$this->table.";", $mcontent);
            $ucontent = preg_replace("/Class ".$this->table." extends Model/","Class ".$class." extends Model", $ucontent);
            file_put_contents($mfile, $ucontent);
        }
        public function up(){

            date_default_timezone_set("UTC");

            $MIGRATIONS_PATH = __DIR__."/../../Models/Migrations";

            if(!file_exists($MIGRATIONS_PATH)){
                 if($_SERVER['OS'] == "Windows_NT"){
                    // for windows
                    system("md ".realpath(__DIR__."/../../Models")."\\Migrations");
                }else{
                    // linux, MacOS
                    system("mkdir ".realpath(__DIR__."/../../Models")."/Migrations");
                }
            }

            if(!file_exists($MIGRATIONS_PATH."/".$this->table)){
                $time = date("Y_m_d_H_i_s");
                if($_SERVER['OS'] == "Windows_NT"){
                    system("md ".realpath(__DIR__."/../../Models")."\\Migrations\\".$this->table);
                }else{
                    system("mkdir ".realpath(__DIR__."/../../Models")."/Migrations/".$this->table);
                }

                $MODEL_PATH = realpath(__DIR__."/../../Models/");
                $MIGRATIONS_PATH = realpath(__DIR__."/../../Models/Migrations/".$this->table);

                $mcontent = file_get_contents($MODEL_PATH."/".$this->table.".php");
                $mfile = $MIGRATIONS_PATH."/".$this->table."_migration_".$time.".php";
                $class = $this->table."_migration_".$time;
                echo "[+] saving migration: ".$this->table."_migration_".$time."\n";
                $ucontent = preg_replace("/namespace.+;/","namespace MPF\\Models\\Migrations\\".$this->table.";", $mcontent);
                $ucontent = preg_replace("/Class ".$this->table." extends Model/","Class ".$class." extends Model", $ucontent);
                file_put_contents($mfile, $ucontent);

                if($this->verifyTable()){
                    // if yes, skip it -> return here
                    echo "[+] everything is up to date\n";
                }else{
                    // if not, run the sql to create the full table with this->fields;
                    $this->createTable();
                    echo "[+] table: ".$this->table.", created sucessfully\n" ;
                    return;
                }
            }else{
                $MIGRATIONS_PATH = realpath(__DIR__."/../../Models/Migrations/".$this->table);
                $f = glob($MIGRATIONS_PATH . "/*");
                if(empty($f)){
                    $this->migration();
                    // verifify if the table alredy exists into the database
                    if($this->verifyTable()){
                         // if yes, skip it -> return here
                        echo "[+] everything is up to date\n";
                    }else{
                         // if not, run the sql to create the full table with this->fields;
                        $this->createTable();
                        echo "[+] table: ".$this->table.", created sucessfully\n" ;
                        return;
                    }
                }else{
                    // verify if the table already
                    //  if not, create the table using the $this->fields;
                    //  if yes, just update the table letting everything below run
                    
                    if(!$this->verifyTable()){
                         // if yes, skip it -> return here
                        $this->migration();
                        $this->createTable();
                         echo "[+] table: ".$this->table.", created sucessfully\n" ;
                        return;
                    }

                    preg_match("/\/(([A-Z-az].+).+)php$/", $f[(count($f) -1)], $mm);
                    $class = "MPF\\Models\\Migrations\\".$this->table."\\".$mm[2];

                    if(!class_exists($class)){
                        throw new Exception("No Model Class ".$class.": found, please check out your migrations folder");
                    }    

                    $mmodel = new $class();
                    $add = [];
                    $edit = [];
                    $remove = [];
                    if($mmodel->fields != $this->fields){
                        // editing and adding fields to the model
                        foreach($this->fields as $k => $v){
                            if(array_key_exists($k , $mmodel->fields)){
                                if($this->fields[$k] != $mmodel->fields[$k]){
                                    echo "[*] ".$k." : will be changed\n";
                                    $edit[$k] = $v;
                                }
                            }else{
                                echo "[+] ".$k." : will be added\n";
                                $add[$k] = $v;
                            }
                        }
                        // removing old oudated fields
                        foreach($mmodel->fields as $k => $v){
                            if(!array_key_exists($k, $this->fields)){
                                echo "[-] ".$k." : will be removed\n";
                                $remove[$k] = $v;
                            }
                        }
                        if(!empty($add)){
                            $this->editTable($add, $action=0);
                        }

                        if(!empty($edit)){
                            $this->editTable($edit, $action=1);
                        }

                        if(!empty($remove)){
                            $this->editTable($remove, $action=2);
                        }

                        $this->migration();
                    }else{
                       echo "[+] everything's up to date";
                    }

                }
            }  
        }

        private function createTable(){
            self::init();

            echo "[+] creating table: ".$this->table."...\n" ;
            $F_KEYS = [];
            try{
                $this::GETCOL('id');
            }catch(Exception $e){
                throw new Exception("the Model ".$this->table." has no field 'id'\n Every model must have one field id");
            }
            $SQL = "CREATE TABLE ".$this->table."(";
            foreach($this->fields as $key => $val){
                preg_match_all('/\|fk_(table|column)_([A-Za-z0-9_]+)\|/', $val, $matches);

                if (count($matches[0]) === 2) {
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        if ($matches[1][$i] === 'table') {
                            $table = $matches[2][$i];
                        } elseif ($matches[1][$i] === 'column') {
                            $column = $matches[2][$i];
                        }
                    }
                
                    $fk = "FOREIGN KEY (".$key.") REFERENCES ".$table."(".$column.") ON DELETE CASCADE";
                    array_push($F_KEYS, $fk);
                    $val = preg_replace('/\|fk_(table|column)_([A-Za-z0-9_]+)\|/', "", $val);
                    $SQL .= $key." ".$val.", ";
                }else{
                    $SQL .= $key." ".$val.", ";
                }
            }
            
            foreach($F_KEYS as $k){
                $SQL.= $k.", ";  
            }

            $SQL .= ")";
            $SQL = str_replace(", )", ");", $SQL);
            return $stmt = self::$DB->query($SQL);
        }
        private function verifyTable(){
            self::init();
            if(self::$DB->query("SHOW TABLES LIKE \"".$this->table."\"")->fetch()){
                return true;
            }else{
                return null;
            }
        }
        public function down(){
            self::init();
            self::$DB->exec('SET FOREIGN_KEY_CHECKS = 0');
            self::$DB->exec('DROP TABLE IF EXISTS '.$this->table);
            self::$DB->exec('SET FOREIGN_KEY_CHECKS = 1');
            return ;
        }

        private function editTable($values, $action){
            if(!is_array($values)){
                throw new Exception("the argument for Model::editTable must be an array");
            }
            $tmp_table = $this->table."_test";
            self::init();

            $SQL = "";
            $SQL_TEST = "";

            try{
                self::$DB->query("DROP TABLE IF EXISTS ".$tmp_table." ; CREATE TABLE ".$tmp_table." LIKE ".$this->table);

                // add columns
                if($action == 0){
                    foreach($values as $k => $v){

                        preg_match_all('/\|fk_(table|column)_([A-Za-z0-9_]+)\|/', $v, $matches);

                        if (count($matches[0]) === 2) {
                            for ($i = 0; $i < count($matches[0]); $i++) {
                                if ($matches[1][$i] === 'table') {
                                    $table = $matches[2][$i];
                                } elseif ($matches[1][$i] === 'column') {
                                    $column = $matches[2][$i];
                                }
                            }
                        
                            $fk = "FOREIGN KEY (".$k.") REFERENCES ".$table."(".$column.") ON DELETE CASCADE";
                            
                            $val = preg_replace('/\|fk_(table|column)_([A-Za-z0-9_]+)\|/', "", $v);
                           
                            $SQL_TEST .= "ALTER TABLE ".$tmp_table." ADD COLUMN ".$k." ".$val."; ";
                            $SQL_TEST .= "ALTER TABLE ".$tmp_table." ADD CONSTRAINT fk_".$table."_".$column." ".$fk.";";
                            
                            
                            $SQL .= "ALTER TABLE ".$this->table." ADD COLUMN ".$k." ".$val."; ";
                            $SQL .= "ALTER TABLE ".$this->table." ADD CONSTRAINT fk_".$table."_".$column." ".$fk.";";
                            
                        }else{
                            $SQL_TEST .= "ALTER TABLE ".$tmp_table." ADD COLUMN ".$k." ".$v.";";
                            $SQL .= "ALTER TABLE ".$this->table." ADD COLUMN ".$k." ".$v.";";
                        }
                    }
                }

                // edit columns
                if($action == 1){
                    foreach($values as $k => $v){
                        $SQL_TEST .= "ALTER TABLE ".$tmp_table." MODIFY COLUMN ".$k." ".$v."; ";
                        $SQL .= "ALTER TABLE ".$this->table." MODIFY COLUMN ".$k." ".$v."; ";
                    }
                }

                // remove columns
                if($action == 2){
                    foreach($values as $k => $v){
                        $SQL_FK = "SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = '".$this->table."' and COLUMN_NAME ='".$k."' ;"; 

                        if(isset(self::$DB->query($SQL_FK)->fetch()["CONSTRAINT_NAME"])){
                            $FK_NAME = self::$DB->query($SQL_FK)->fetch()["CONSTRAINT_NAME"];
                            $SQL_TEST .= "ALTER TABLE ".$tmp_table." DROP FOREIGN KEY IF EXISTS ".$FK_NAME." ; ";
                            $SQL_TEST .= "ALTER TABLE ".$tmp_table." DROP COLUMN IF EXISTS ".$tmp_table.".".$k."; ";

                            $SQL .= "ALTER TABLE ".$this->table." DROP FOREIGN KEY IF EXISTS ".$FK_NAME." ; ";
                            $SQL .= "ALTER TABLE ".$this->table." DROP COLUMN IF EXISTS ".$this->table.".".$k."; ";
                        }else{
                            $SQL_TEST .= "ALTER TABLE ".$tmp_table." DROP COLUMN IF EXISTS ".$tmp_table.".".$k."; ";
                            $SQL .= "ALTER TABLE ".$this->table." DROP COLUMN IF EXISTS ".$this->table.".".$k."; ";
                        }
                    }

                    //return;
                }

                //echo $SQL."\n";
                self::$DB->query($SQL_TEST);
                //return;
            }catch(Exception $ex){
                self::$DB->query("DROP TABLE IF EXISTS ".$tmp_table);
                throw new Exception("[x] something is wrong: ".$ex->getMessage());
            }

            try{
                self::$DB->query("DROP TABLE IF EXISTS ".$tmp_table.";");
                self::$DB->query($SQL);
            }catch(Exception $ex){
                self::$DB->query("DROP TABLE IF EXISTS ".$tmp_table.";");
                throw new Exception("[+] something went wrong while trying to change the the table definition: ".$ex->getMessage());
            }
            
        }

        public static function delete($values){
            $instance = new static;
            if($instance->find){
                return $instance->Debug("you can not call ".__METHOD__." after find has been called");
            }
            if(!is_array($values)){
                return $instance->Debug("the arguments for ".__METHOD__." or must be an array ['id' => 1]");
            }
            $ands = count($values) - 1; 
            foreach( $values as $key => $val){
                $instance::GETCOL($key);
                $qkey = $instance->table.".".$key;
                $key = ":".$instance->table."_".$key.(($instance->plus > 0) ? $instance->plus : "");

                if(is_array($val)){
                    if($val[0] === "%"){
                        $instance->WHERE .= $qkey." LIKE \"%".$val[1]."%\" ";
                    }else{
                        $instance->WHERE .= $qkey." ".$val[0]." ".$key;
                        $instance->Params[$key] = $val[1];
                    }
                }else{
                    $instance->Params[$key] = $val;
                    $instance->WHERE .= $qkey." = ".$key;
                }
                $instance->WHERE .= ($ands > 0 ? " AND " : "");
                $ands--;    
            }

            $instance->plus++;
            $instance->where = true;
            $instance->delete = true;
            return $instance;
        }

        public static function find($KEYWORDS = "*"){
            $instance = new static;
            if($instance->find){
                return $instance->Debug("you should just call ".__METHOD__." once in an execution");
            }
            if(is_array($KEYWORDS)){
                foreach($KEYWORDS as $key){
                    $instance::GETCOL($key);
                    //$instance->query .= " ".$instance->table.".".$key.",";
                    array_push($instance->SELECT, $instance->table.".".$key);
                }
            }else{
                //$instance->query .= $instance->table.".".$KEYWORDS." FROM ".$instance->table;
                array_push($instance->SELECT, $instance->table.".".$KEYWORDS);
            }
            $instance->find = true;
            return $instance;
        }

        public static function count($KEYWORDS = "*"){
            $instance = new static;
            if($instance->find){
                return $instance->Debug("you should just call ".__METHOD__." once in an execution");
            }
            if(is_array($KEYWORDS)){
                foreach($KEYWORDS as $key){
                    $instance::GETCOL($key);
                    //$instance->query .= " ".$instance->table.".".$key.",";
                    array_push($instance->SELECT, "count(".$key.")");
                }
            }else{
                //$instance->query .= $instance->table.".".$KEYWORDS." FROM ".$instance->table;
                array_push($instance->SELECT, "count(".$KEYWORDS.")");
            }
            $instance->count = true;
            $instance->find = true;
            return $instance;
        }

        public function where($values){
            if(!$this->find && !$this->update && !$this->joined){
                return $this->Debug("you should call ".__METHOD__." after App\Core\DB\Model::{find, update, join} has been called");
            }
            if($this->order){
                return $this->Debug("you can not call ".__METHOD__." after order");
            }
            if($this->where){
                return $this->Debug("you should just call ".__METHOD__." once in an execution");
            }
            if(!is_array($values)){
                return $this->Debug("the arguments for where must be an array ['id' => 1]");
            }
            $this->WHERE .= " WHERE ";
            $ands = count($values) - 1; 
            foreach( $values as $key => $val){
                $this::GETCOL($key);
                $qkey = $this->table.".".$key;
                $key = ":".$this->table."_".$key.(($this->plus > 0) ? $this->plus : "");

                if(is_array($val)){
                    if($val[0] === "%"){
                        $this->WHERE .= $qkey." LIKE \"%".$val[1]."%\" ";
                    }else{
                        $this->WHERE .= $qkey." ".$val[0]." ".$key;
                        $this->Params[$key] = $val[1];
                    }
                }else{
                    $this->Params[$key] = $val;
                    $this->WHERE .= $qkey." = ".$key;
                }
                $this->WHERE .= ($ands > 0 ? " AND " : "");
                $ands--;    
            }

            $this->plus++;
            $this->where = true;
            return $this;
        }
        public function and($values){
            if($this->order){
                return $this->Debug("you can not call ".__METHOD__." after order");
            }
            if(!$this->where || $this->or){
                return $this->Debug("you should call ".__METHOD__." after App\Core\DB\Model::{where | or} has been called");
            }
            if(!is_array($values)){
                return $this->Debug("the arguments for or must be an array ['id' => 1]");
            }
            $this->WHERE .= " AND ";
            $ands = count($values) - 1;
            foreach( $values as $key => $val){
                $this::GETCOL($key);
                $qkey = $this->table.".".$key;
                $key = ":".$this->table."_".$key.(($this->plus > 0) ? $this->plus : "");

                if(is_array($val)){
                    if($val[0] === "%"){
                        $this->WHERE .= $qkey." LIKE \"%".$val[1]."%\" ";
                    }else{
                        $this->WHERE .= $qkey." ".$val[0]." ".$key;
                        $this->Params[$key] = $val[1];
                    }
                }else{
                    $this->Params[$key] = $val;
                    $this->WHERE .= $qkey." = ".$key;
                }
                $this->WHERE .= ($ands > 0 ? " AND " : "");
                $ands--;
            }
            $this->plus++;
            return $this;
        }
        public function or($values){
            if($this->or){
                return $this->Debug("you should just call ".__METHOD__." once in an execution");
            }
            if($this->order){
                return $this->Debug("you can not call ".__METHOD__." after order");
            }
            if(!$this->where){
                return $this->Debug("you should call ".__METHOD__." after App\Core\DB\Model::where has been called");
            }
            if(!is_array($values)){
                return $this->Debug("the arguments for or must be an array ['id' => 1]");
            }
            $this->WHERE .= " OR ";
            $ands = count($values) - 1;
            foreach( $values as $key => $val){
                $this::GETCOL($key);
                $qkey = $this->table.".".$key;
                $key = ":".$this->table."_".$key.(($this->plus > 0) ? $this->plus : "");

                if(is_array($val)){
                    if($val[0] === "%"){
                        $this->WHERE .= $qkey." LIKE \"%".$val[1]."%\" ";
                    }else{
                        $this->WHERE .= $qkey." ".$val[0]." ".$key;
                        $this->Params[$key] = $val[1];
                    }
                }else{
                    $this->Params[$key] = $val;
                    $this->WHERE .= $qkey." = ".$key;
                }
                $this->WHERE .= ($ands > 0 ? " AND " : "");
                $ands--;
            }
            $this->plus++;
            return $this;
        }

        public function order($model_column, $ord = -1){
            if(!$this->find){
                return $this->Debug("you should just call ".__METHOD__." after App\Core\DB\Model::find() has been called");
            }
            if($this->order){
                return $this->Debug("you should just call ".__METHOD__." once in an execution");
            }
            if($this->limit){
                return $this->Debug("you can not call ".__METHOD__." after App\Core\DB\Model::limit() has been called");
            }
            if($ord != 1 && $ord != -1){
                return $this->Debug(" the second argument for order must be either 1 or -1");
            }
        
            if(is_array($model_column)){
                $model = $model_column[0];
                $col = $model_column[1];
                $classModel = self::loadclass(ucfirst($model));
                $classModel::GETCOL($col);
                $st = $model.".".$col;

            }else{
                $this::GETCOL($model_column);
                $st = $this->table.".".$model_column;
            }

            $this->ORDER .= " ORDER BY ".$st." ".(($ord == 1) ? " ASC " : " DESC ");
            $this->order = true;
            return $this;
        }

        public function limit(...$args){
            if($this->limit){
                return $this->Debug("you should just call ".__METHOD__." once in an execution");
            }
            $argsCount = count($args);
            if($argsCount == 2){
                $this->LIMIT .= " LIMIT ".$args[0].",".$args[1];
            } else if($argsCount == 1) {
                $this->LIMIT .= " LIMIT ".$args[0];
            }else {
                return $this->Debug("limit must have one or two args: limit(lim, offset) || limit(lim)");
            }
            $this->limit = true;
            return $this;
        }

        public static function update($values){
            $instance = new static;
            if($instance->update){return $instance->Debug("you should just call ".__METHOD__." once in an execution");}
            if($instance->find){ return $instance->Debug("you shouldn't call ".__METHOD__." after App\Core\DB\Model::find has been called");}
            if($instance->where){return $instance->Debug("you shouldn't call ".__METHOD__." after App\Core\DB\Model::where has been called");}
            if($instance->or){return $instance->Debug("you shouldn't call ".__METHOD__." after App\Core\DB\Model::or has been called");}
            if($instance->order){return $instance->Debug("you shouldn't call ".__METHOD__." after App\Core\DB\Model::order has been called");}
            if($instance->limit){return $instance->Debug("you shouldn't call ".__METHOD__." after App\Core\DB\Model::limit has been called");}
            if(!is_array($values)){ return $instance->Debug("the arguments for where must be an array ['id' => 1]");}
            $instance->UPDATE =" UPDATE ".$instance->table." SET ";
            
            $commas = count($values) - 1 ;
            foreach( $values as $key => $value){
                $instance::GETCOL($key);
                $instance->UPDATE .= $instance->table.".".$key."=:".$key.($commas > 0 ? " , ": "");
                $instance->Params[':'.$key] = $value;
                $commas--;
            }
            $instance->plus++;
            $instance->update = true;
            return $instance;
        }

        public function join($table, $columns){
            if(!$this->find){ return $this->Debug("you should call ".__METHOD__." after App\Core\DB\Model::find() has been called");}
            // if($this->joined){ return $this->Debug("you can only call ".__METHOD__." again after where has been called again");}
            if($this->update){ return $this->Debug("you can not call ".__METHOD__." after App\Core\DB\Model::update() has been called");}
            if($this->where){ return $this->Debug("you can not call ".__METHOD__." after App\Core\DB\Model::where() has been called");}
            if($this->or){ return $this->Debug("you can not call ".__METHOD__." after App\Core\DB\Model::or() has been called");}
            if($this->order){ return $this->Debug("you can not call ".__METHOD__." after App\Core\DB\Model::order() has been called");}
            if($this->limit){ return $this->Debug("you can not call ".__METHOD__." after App\Core\DB\Model::limit() has been called");}

            $jModel = self::loadclass(ucfirst($table));
            $this->jTable = $table;

            if(!is_array($columns)){
                return $this->Debug(__METHOD__." second argument must be array ['col','col'...]");
            }

            foreach($columns as $col){
                $jModel::GETCOL($col);
                if($col == "id"){
                    $col = $col." AS ".$this->jTable."_".$col;
                    array_push($this->SELECT_JOIN, $this->jTable.".".$col);
                }else{
                    array_push($this->SELECT_JOIN, $this->jTable.".".$col);
                }
            }
            $JOIN = " INNER JOIN ".$this->jTable." ON ";
            $this->JOINS = array_merge($this->JOINS, [$JOIN]);
            $this->joined = true;
            return $this;
        }

        public function on($values){
            if(!is_array($values)){  return $this->Debug(__METHOD__." second argument must be array ['table'=> ['col' => 'val'...]"); }
            
            $JOIN = " ";
            if($this->joined){
                $ands = count($values) - 1;
                foreach($values as $Model => $vals){
                    $classModel = self::loadclass(ucfirst($Model));
                    $ands_ = count($vals) - 1;
                    foreach($vals as $key => $val){
                        if(is_array( $val)){

                            if($val[0] === "<" || $val[0] === "<=" || $val[0] === ">" ||
                               $val[0] === ">=" || $val[0] === "%"){
                                $classModel::GETCOL($key);
                                $qkey = $classModel->table.".".$key;
                                $key = ":".$classModel->table."_".$key.(($this->plus > 0) ? $this->plus : "");
                                $JOIN .= $qkey." ".$val[0]." ".$key." ";
                                $this->Params[$key] = $val[1];

                            }else if(count($val) == 2){
                                $aclassModel = self::loadclass(ucfirst($val[0]));
                                $aclassModel::GETCOL($val[1]);
                                $qkey = $classModel->table.".".$key;
                                $JOIN .= $qkey." = ".$val[0].".".$val[1];
                            }else{
                                $this->Debug("When relating tables the arguments for fields must be an array or two elemetns => ['Model', 'Col']");
                            }
                        }else{
                            $classModel::GETCOL($key);
                            $qkey = $classModel->table.".".$key;
                            $key = ":".$classModel->table."_".$key.(($this->plus > 0) ? $this->plus : "");
                            $this->Params[$key] = $val;
                            $JOIN .= $qkey." = ".$key." ";         
                        }
                        $JOIN .= ( $ands_ > 0 ? " AND " : "");
                        $ands_--;
                    }

                    $JOIN .= ($ands > 0 ? " AND " : "");
                    $ands--;
                    $this->plus++;
                }
                $this->JOINS = array_merge($this->JOINS, [$JOIN]);

                $this->join = false;
                $this->on = true;
                $this->plus++;
                return $this;
            }
        }

        public function onor($values){
            if(!is_array($values)){  return $this->Debug(__METHOD__." second argument must be array ['col'=>'val'...]"); }
            
            $JOIN = "";
            if($this->joined){
                $JOIN .= " OR ";
                $ands = count($values) - 1;
                foreach($values as $Model => $vals){
                    $classModel = self::loadclass(ucfirst($Model));
                    $ands_ = count($vals) - 1;
                    foreach($vals as $key => $val){
                        if(is_array( $val)){
                            if($val[0] === "<" || $val[0] === "<=" || $val[0] === ">" ||
                               $val[0] === ">=" || $val[0] === "%"){
                                $classModel::GETCOL($key);
                                $qkey = $classModel->table.".".$key;
                                $key = ":".$classModel->table."_".$key.(($this->plus > 0) ? $this->plus : "");
                                $JOIN .= $qkey." ".$val[0]." ".$key." ";
                                $this->Params[$key] = $val[1];

                            }else if(count($val) == 2){
                                $aclassModel = self::loadclass(ucfirst($val[0]));
                                $aclassModel::GETCOL($val[1]);
                                $qkey = $classModel->table.".".$key;
                                $JOIN .= $qkey." = ".$val[0].".".$val[1];
                            }else{
                                $this->Debug("When relating tables the arguments for field must be an array or two elemetns => ['Model', 'Col']");
                            }
                        }else{
                            $classModel::GETCOL($key);
                            $qkey = $classModel->table.".".$key;
                            $key = ":".$classModel->table."_".$key.(($this->plus > 0) ? $this->plus : "");
                            $this->Params[$key] = $val;
                            $JOIN .= $qkey." = ".$key." ";         
                        }
                        $JOIN .= ( $ands_ > 0 ? " AND " : "");
                        $ands_--;
                    }

                    $JOIN .= ($ands > 0 ? " AND " : "");
                    $ands--; 
                    $this->plus++;
                }
                $this->JOINS = array_merge($this->JOINS, [$JOIN]);

                $this->join = false;
                $this->on = true;
                $this->plus++;
                return $this;
            }
        }

        public function exec(){
            if(!$this->count && !$this->find && !$this->update && !$this->joined && !$this->delete){
                return $this->Debug("you should just can call exec() after run find() or update()");
            }

            $QUERY = "";
            if($this->count){
                $QUERY = "SELECT ";
                $commas = (count($this->SELECT) - 1);
                foreach ($this->SELECT as $col) {
                    $QUERY .= $col.(($commas > 0) ? ", " : "");
                    $commas--;
                }
                $QUERY .= " FROM ".$this->table." ";
                $QUERY .= $this->WHERE;
            }else if($this->joined){
                $ALL_SELECT = array_merge($this->SELECT, $this->SELECT_JOIN);
                $QUERY = "SELECT ";
                $commas = (count($ALL_SELECT) - 1);
                foreach ($ALL_SELECT as $col) {
                    $QUERY .= $col.(($commas > 0) ? ", " : "");
                    $commas--;
                }
                $QUERY .= " FROM ".$this->table." "; 

                if(count($this->JOINS) > 1){
                    foreach ($this->JOINS as $val){
                        $QUERY .= " ".$val." ";
                    }
                }
                else{
                    $QUERY .= " INNER JOIN ".$this->jTable;
                }
                
                if($this->where){
                    $QUERY .= $this->WHERE;
                }
                if($this->order){
                    $QUERY .= $this->ORDER;
                }
                if($this->limit){
                    $QUERY .= $this->LIMIT;
                } 
                echo $QUERY;     
            }else if($this->delete){
                $QUERY .= "DELETE FROM ".$this->table." WHERE ";
                $QUERY .= $this->WHERE;
                $query = str_replace("DELETE FROM" , "SELECT * FROM ", $QUERY);

                self::init();

                $select = self::$DB->prepare($query);
                $select->execute($this->Params);
                $record = $select->fetchall(PDO::FETCH_ASSOC);
                
                if(!$record){
                    return null;
                }

                $stmt = self::$DB->prepare($QUERY);
                $stmt->execute($this->Params);
                return $record;
            }else if($this->update){
                $QUERY .= $this->UPDATE;
                if(!$this->where){ return $this->Debug("Filters are needed [ where | or ]"); }
                $QUERY .= $this->WHERE;
            }else{
                $QUERY .= " SELECT ";
                $commas = (count($this->SELECT) - 1);
                foreach ($this->SELECT as $col) {
                    $QUERY .= $col.(($commas > 0) ? ", " : "");
                    $commas--;
                }
                $QUERY .= " FROM ".$this->table." ";
                $QUERY .= $this->WHERE;
            }

            self::init();
            try{   
                $stmt = self::$DB->prepare($QUERY);
                if(empty($this->Params)){ 
                    $stmt->execute(); 
                }else{
                    $stmt->execute($this->Params);
                }
                if($this->update){
                    return true;

                }
                return  $stmt->fetchall(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                return $this->Debug($e->getMessage());
                //return null;
            }
        }
        public static function save($values){
            $instance = new static;
            $SQL = "INSERT INTO ".$instance->table."(";
            $VAL = "VALUES(";
            foreach($values as $key => $val){
                if(!isset($instance->fields[$key])){
                    $instance->Debug("undefined column $key in database model('".$instance->table."')");
                }
                $type = explode(" ",$instance->fields[$key])[0];
                $type = trim($type);

                if($type == "boolean"){
                   if(!is_bool($val)){
                        $instance->Debug("the value for the field: ".$key." must be either true or false");
                   }
                }
                
                if(str_contains($type, "enum")){
                    preg_match_all("/'([^']+)'/",$type, $enum);
                    $en_ok = false;
                    foreach($enum[1] as $enumVal){
                        if($enumVal == $val){
                            $en_ok = true;
                            break;
                        }
                    }
                    if($en_ok == false){
                        return $instance->Debug("the value: ".$val." does not match any values for enumeration field: \n\t".$instance->fields[$key]);
                    }
                }
                $SQL .= $key.", ";
                $VAL .= ":".$key.", ";
            }
            $SQL .=")";
            $VAL .=")";
            $SQL = str_replace(", )", ") ", $SQL);
            $VAL = str_replace(", )", ") ", $VAL);
            self::init();
            try{
                $stmt = self::$DB->prepare($SQL.$VAL);
                foreach($values as $key => $val){
                    $stmt->bindValue(":".$key , $val );
                }
                $stmt->execute();
                return $instance::find()->where(["id" => self::$DB->lastInsertId()])->exec()[0];
            }catch(Exception $e){
                return $instance->Debug($e->getMessage());
            }
        }
        public static function RawQuery($query){
            self::init();
            $stmt = self::$DB->query($query);
            return $stmt->fetchall();
        }
        public static function gt($number){
            return [">", $number];
        }
        public static function gte($number){
            return [">=", $number];
        }
        public static function lt($number){
            return ["<", $number];
        }
        public static function lte($number){
            return ["<=", $number];
        }
        public static function like($string){
            return ["%", $string]; ;
        }
    }
?>