<?php

     /**
    * ┌─────────────────────────────────────────────────────────────────
    * │                             MPF - MODEL                      
    * ├─────────────────────────────────────────────────────────────────
    * │ 📌 Desc      : Autogenerated model by the command: create model 
    * │ 👨 Author    : Adão Major (adaomajor)
    * │ 🐱‍👤 github    : https://github.com/adaomajor                   
    * │ 🔄 Version   : 1.0.0                                     
    * │ 🧱 Framework : MPF Framework                                                                     
    * └──────────────────────────────────────────────────────────────────
    *
    * FOR MORE INFORMATION READ THE DOCUMENTATION
    */

    namespace MPF\Models;
    use MPF\Core\DB\Model;

    Class Users extends Model{
        protected $table = "Users";
        protected $fields = [];
            
        public function __construct(){
            $this->fields = [
                "id" => Model::PK(),
                "name" => Model::char(30, $nullable = null),
                "email" => Model::char(70, $nullable = null),
                "password" => Model::char(255, $nullable = null),
            ];
        }
    }
?>