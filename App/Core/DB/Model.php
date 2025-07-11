<?php
    namespace MPF\Core\DB;
    require_once 'DatabaseTrait.php';
    require_once 'helper.php';
    
    use MPF\Database\DatabaseTrait;
    use Exception;
    use Helper;
    use PDO;

    class Model extends Helper{
        use DatabaseTrait;
        protected $table = "";
        protected $fields = [];

        private $query;
        private $Params = [];

        private $pk = false;

        private $find = false;
        private $where = false;
        private $or = false;
        private $order = false;
        private $limit = false;

        private $update = false;

        private $joined = false;
        private $jTable;
        private $join = "";

        private $plus = 0;

        public static function int($pk = null, $nullable = null, $default = null){
            return ((isset($pk) && $pk == true ? "INT PRIMARY KEY AUTO_INCREMENT NOT NULL" : ((isset($nullable)) ? "INT" : "INT NOT NULL").(isset($default) ? " DEFAULT '".$default."'" : "") ));
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
            $type = $object->fields[$col];
            if($type){
                return $type;
            }else{
                throw new Exception("the Model ".$object->table." has no field ".$col);
            }
        }
        public static function FK($args){
            if(!is_array($args) || count($args) != 2){
                throw new Exception("the argument for Model::FK must an array of the elements: ['Model','Column']");
            }

            $referenceTable = self::loadclass($args[0]);
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
        public function up(){
            self::init();
            self::$DB->exec('SET FOREIGN_KEY_CHECKS = 0');
            self::$DB->exec('DROP TABLE IF EXISTS '.$this->table);
            self::$DB->exec('SET FOREIGN_KEY_CHECKS = 1');

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

        public function down(){
            self::init();
            self::$DB->exec('SET FOREIGN_KEY_CHECKS = 0');
            self::$DB->exec('DROP TABLE IF EXISTS '.$this->table);
            self::$DB->exec('SET FOREIGN_KEY_CHECKS = 1');
            return ;
        }

        public function delete($values){
            if($this->find){
                throw new Exception("you can not call ".__METHOD__." after find has been called");
            }
            if(!is_array($values)){
                throw new Exception("the arguments for ".__METHOD__." or must be an array ['id' => 1]");
            }
            
            $this->query = "DELETE FROM ".$this->table." WHERE ";
            if(count($values) > 1){
                $ands = (count($values) - 1);
                foreach( $values as $key => $value){
                    $this::GETCOL($key);
                    $this->query .= "".$key."=:".$key.(($ands > 0) ? " AND ": "");
                    $this->Params[':'.$key] = $value;
                    $ands--;
                }
            }else{
                foreach( $values as $key => $value){
                    $this::GETCOL($key);
                    $this->query .= " ".$key."=:".$key;
                    $this->Params[":".$key] = $value;
                }
            }

            $query = str_replace("DELETE FROM" , "SELECT * FROM ", $this->query);

            self::init();
            $select = self::$DB->prepare($query);
            $select->execute($this->Params);
            $record = $select->fetch(PDO::FETCH_ASSOC);
            
            if(!$record){
                return null;
            }

            $stmt = self::$DB->prepare($this->query);
            $stmt->execute($this->Params);
            return $record;
        }

        public function find($KEYWORDS = "*"){
            if($this->find){
                throw new Exception("you should just call ".__METHOD__." once in an execution");
            }
            $this->query = "SELECT ";
            $this->join .= "SELECT ";
            if(is_array($KEYWORDS)){
                foreach($KEYWORDS as $key){
                    $this::GETCOL($key);
                    $this->query .= " ".$this->table.".".$key.",";
                    $this->join .= " ".$this->table.".".$key.",";
                }
                $this->query .= "FROM ".$this->table;
            }else{
                $this->query .= $this->table.".".$KEYWORDS." FROM ".$this->table;
                $this->join .= $this->table.".".$KEYWORDS.","; 
            }
            $this->query = str_replace(",FROM", " FROM", $this->query);
            $this->join = str_replace(",X", " ", $this->join);      
            $this->find = true;
            return $this;
        }
        public function where($values){
            if(!$this->find && !$this->update && !$this->joined){
                throw new Exception("you should call ".__METHOD__." after App\Core\DB\Model::{find, update, join} has been called");
            }
            if($this->order){
                throw new Exception("you can not call ".__METHOD__." after order");
            }
            if($this->where){
                throw new Exception("you should just call ".__METHOD__." once in an execution");
            }
            if(!is_array($values)){
                throw new Exception("the arguments for where must be an array ['id' => 1]");
            }

            if($this->joined){
                foreach($values as $Model => $fields){
                    $this->join = str_replace("<END>", " AND ", $this->join);
                    $classModel = self::loadclass($Model);
                    if(!is_array($values[$Model])){
                        throw new Exception("the argument for where must be an array of arrays when calling join: [ 'Model' => ['field' => value...], ...]");
                    }

                    if(count($values[$Model]) > 1){
                        $ands = (count($values[$Model]) - 1);
                        foreach($values[$Model] as $key => $value){
                            $classModel::GETCOL($key);
                            $keyVal = $key;
                            if(isset($this->Params[":".$key]) || isset($this->Params[":".$key.$this->plus])){
                                $this->plus++;
                                $keyVal = $key.$this->plus;
                            }
                            $this->join .= $Model.".".$key."=:".$keyVal.(($ands > 0) ? " AND ": "");
                            $this->Params[':'.$keyVal] = $value;
                            $ands--;
                        }
                    }else{
                        foreach($values[$Model] as $key => $value){
                            $classModel::GETCOL($key);
                            $keyVal = $key;
                            if(isset($this->Params[":".$key]) || isset($this->Params[":".$key.$this->plus])){
                                $this->plus++;
                                $keyVal = $key.$this->plus;
                            }
                            $this->join .= $Model.".".$key."=:".$keyVal;
                            $this->Params[':'.$keyVal] = $value;
                        }
                    }
                    $this->join .= " <END> ";
                }
                $this->join = str_replace("<END>", "  ", $this->join);
                $this->where = true;
                return $this;
            }

            $this->query .= " WHERE ";
            if(count($values) > 1){
                $ands = (count($values) - 1);
                foreach( $values as $key => $value){
                    $this::GETCOL($key);
                    $plus = 0;
                    if($this->update){
                        $plus++;
                    }
                    if(isset($this->Params[":".$key])){
                        $this->Params[":".$key.$plus] = $value;
                        $keyVal = $key.$plus;
                        $this->query .= $this->table.".".$key."=:".$keyVal.(($ands > 0) ? " AND ": "");
                        $this->Params[':'.$keyVal] = $value;
                    }else{
                        $this->query .= $this->table.".".$key."=:".$key.(($ands > 0) ? " AND ": "");
                        $this->Params[':'.$key] = $value;
                    }
                    $ands--;
                }
            }else{
                foreach( $values as $key => $value){
                    $this::GETCOL($key);
                    $plus = 0;
                    if($this->update){
                        $plus++;
                    }
                    if(isset($this->Params[":".$key])){
                        $this->Params[":".$key.$plus] = $value;
                        $keyVal = $key.$plus;
                        $this->query .= $this->table.".".$key."=:".$keyVal;
                        $this->Params[':'.$keyVal] = $value;
                    }else{
                        $this->query .= " ".$this->table.".".$key."=:".$key;
                        $this->Params[":".$key] = $value;
                    }
                }
            }
            $this->where = true;
            return $this;
        }
        public function or($values){
            if($this->or){
                throw new Exception("you should just call ".__METHOD__." once in an execution");
            }
            if($this->order){
                throw new Exception("you can not call ".__METHOD__." after order");
            }
            if(!$this->where){
                throw new Exception("you should call ".__METHOD__." after App\Core\DB\Model::where has been called");
            }
            if(!is_array($values)){
                throw new Exception("the arguments for or must be an array ['id' => 1]");
            }

            if($this->joined){
                foreach($values as $Model => $fields){
                    $classModel = self::loadclass($Model);
                    if(!is_array($values[$Model])){
                        throw new Exception("the argument for where must be an array of arrays when calling join: [ 'Model' => ['field' => value...], ...]");
                    }
                    if(count($values[$Model]) > 1){
                        $ands = (count($values[$Model]) - 1);
                        foreach($values[$Model] as $key => $value){
                            $classModel::GETCOL($key);
                            $keyVal = $key;
                            if(isset($this->Params[":".$key]) || isset($this->Params[":".$key.$this->plus])){
                                $this->plus++;
                                $keyVal = $key.$this->plus;
                            }
                            $this->join .= " OR ".$Model.".".$key."=:".$keyVal;
                            $this->Params[':'.$keyVal] = $value;
                            $ands--;
                        }
                    }else{
                        foreach($values[$Model] as $key => $value){
                            $classModel::GETCOL($key);
                            $keyVal = $key;
                            if(isset($this->Params[":".$key]) || isset($this->Params[":".$key.$this->plus])){
                                $this->plus++;
                                $keyVal = $key.$this->plus;
                            }
                            $this->join .= " OR ".$Model.".".$key."=:".$keyVal;
                            $this->Params[':'.$keyVal] = $value;
                        }
                    }
                }
                $this->or = true;
                return $this;
            }
            foreach( $values as $key => $value){
                $this::GETCOL($key);
                $plus = 1;
                if($this->update){
                    $plus++;
                }
                if(isset($this->Params[":".$key])){
                    $this->Params[":".$key.$plus] = $value;
                    $keyVal = $key.$plus;
                    $this->query .= " OR ".$this->table.".".$key."=:".$keyVal;
                }else{
                    $this->query .= " OR ".$this->table.".".$key."=:".$key;
                    $this->Params[":".$key] = $value;
                }
            }
            $this->or = true;
            return $this;
        }

        public function order($column, $asc_desc = "DESC"){
            if(!$this->find){
                throw new Exception("you should just call ".__METHOD__." after App\Core\DB\Model::find() has been called");
            }
            if($this->order){
                throw new Exception("you should just call ".__METHOD__." once in an execution");
            }
            if($this->limit){
                throw new Exception("you can not call ".__METHOD__." after App\Core\DB\Model::limit() has been called");
            }
            if(strtoupper($asc_desc) != "ASC" && strtoupper($asc_desc) != "DESC"){
                throw new Exception(" the second argument for order must be either asc or desc");
            }
            $this::GETCOL($column);
            $this->query .= " ORDER BY ".$column." ".strtoupper($asc_desc);
            $this->order = true;
            return $this;
        }

        public function limit(...$args){
            if($this->limit){
                throw new Exception("you should just call ".__METHOD__." once in an execution");
            }
            // if(!$this->where){
            //     throw new Exception("you should call ".__METHOD__." after App\Core\DB\Model::where has been called");
            // }
            $argsCount = count($args);
            if($argsCount == 2){
                $this->query .= " LIMIT ".$args[0].",".$args[1];
                $this->join .= " LIMIT ".$args[0].",".$args[1];
            } else if($argsCount == 1) {
                $this->query .= " LIMIT ".$args[0];
                $this->join .= " LIMIT ".$args[0];
            }else {
                throw new Exception("limit must have one or two args: limit(lim, offset) || limit(lim)");
            }
            $this->limit = true;
            return $this;
        }

        public function update($values){
            if($this->update){throw new Exception("you should just call ".__METHOD__." once in an execution");}
            if($this->find){ throw new Exception("you shouldn't call ".__METHOD__." after App\Core\DB\Model::find has been called");}
            if($this->where){throw new Exception("you shouldn't call ".__METHOD__." after App\Core\DB\Model::where has been called");}
            if($this->or){throw new Exception("you shouldn't call ".__METHOD__." after App\Core\DB\Model::or has been called");}
            if($this->order){throw new Exception("you shouldn't call ".__METHOD__." after App\Core\DB\Model::order has been called");}
            if($this->limit){throw new Exception("you shouldn't call ".__METHOD__." after App\Core\DB\Model::limit has been called");}
            if(!is_array($values)){ throw new Exception("the arguments for where must be an array ['id' => 1]");}
            $this->query .="UPDATE ".$this->table." SET ";
            
            if(count($values) > 1){
                $commas = (count($values) - 1);
                foreach( $values as $key => $value){
                    $this::GETCOL($key);
                    $this->query .= $this->table.".".$key."=:".$key.(($commas > 0) ? " , ": "");
                    $this->Params[':'.$key] = $value;
                    $commas--;
                }
            }else{
                foreach( $values as $key => $value){
                    $this::GETCOL($key);
                    $this->query .= " ".$this->table.".".$key."=:".$key;
                    $this->Params[":".$key] = $value;
                }
            }
            $this->update = true;
            return $this;
        }

        public function join($table, $columns){
            if(!$this->find){ throw new Exception("you should call ".__METHOD__." after App\Core\DB\Model::find() has been called");}
            if($this->joined){ throw new Exception("you can only call ".__METHOD__." once in an execution");}
            if($this->update){ throw new Exception("you can not call ".__METHOD__." after App\Core\DB\Model::update() has been called");}
            if($this->where){ throw new Exception("you can not call ".__METHOD__." after App\Core\DB\Model::where() has been called");}
            if($this->or){ throw new Exception("you can not call ".__METHOD__." after App\Core\DB\Model::or() has been called");}
            if($this->order){ throw new Exception("you can not call ".__METHOD__." after App\Core\DB\Model::order() has been called");}
            if($this->limit){ throw new Exception("you can not call ".__METHOD__." after App\Core\DB\Model::limit() has been called");}

            $jModel = self::loadclass(ucfirst($table));
            $this->jTable = $table;

            if(!is_array($columns)){
                throw new Exception(__METHOD__." second argument must be array ['col','col'...]");
            }

            $join = "FROM ".$this->table." INNER join ".$this->jTable." ON ";
            $JOINS = "";
            foreach($columns as $col){
                $jModel::GETCOL($col);
                $JOINS .= " ".$this->jTable.".".$col.",";
            }

            $this->join .= str_replace(",FROM ", " FROM ", $JOINS.$join);
            $this->joined = true;
            return $this;
        }

        public function exec(){
            if(!$this->find && !$this->update && !$this->joined){
                throw new Exception("you should just can call exec() after run find() or update()");
            }
            self::init();
            
            if($this->update){
                try{
                    $stmt = self::$DB->prepare($this->query);
                    return $stmt->execute($this->Params);
                }catch(Exception $e){
                    return null;
                }
            }

            if($this->joined){
                try{
                    $stmt = self::$DB->prepare($this->join);
                    $stmt->execute($this->Params);
                    return $stmt->fetchall();
                }catch(Exception $e){
                    return null;
                }
            }
            try{   
                $stmt = self::$DB->prepare($this->query);
                if(empty($this->Params)){ 
                    $stmt->execute(); 
                }else{
                    $stmt->execute($this->Params);
                }
                $this->find = false;
                return $stmt->fetchall();
            }catch(Exception $e){
                return null;
            }
        }
        public function save($values){
            $SQL = "INSERT INTO ".$this->table."(";
            $VAL = "VALUES(";
            foreach($values as $key => $val){
                if(!isset($this->fields[$key])){
                    throw new Exception("undefined column $key in database model('".$this->table."')");
                }
                $type = explode(" ",$this->fields[$key])[0];
                $type = trim($type);

                if($type == "boolean"){
                   if(!is_bool($val)){
                        throw new Exception("the value for the field: ".$key." must be either true or false");
                   }
                }
                
                if(str_contains($type, "enum")){
                    echo "temos um enum";
                    preg_match_all("/'([^']+)'/",$type, $enum);
                    var_dump($enum);
                    $en_ok = false;
                    foreach($enum[1] as $enumVal){
                        if($enumVal == $val){
                            $en_ok = true;
                            break;
                        }
                    }
                    if($en_ok == false){
                        throw new Exception("the value: ".$val." does not match any values for enumeration field: \n\t".$this->fields[$key]);
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
                return $this->find("*")->where(["id" => self::$DB->lastInsertId()])->exec()[0];
            }catch(Exception $e){
                return $e->getMessage();
                //return null;
            }
        }

        public function RawQuery($query){
            self::init();
            $stmt = self::$DB->query($query);
            return $stmt->fetchall();
        }
    }
?>