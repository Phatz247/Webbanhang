<?php

    include "../model/xl_data.php";
class  danhmuc {
        private $id_dm = 0; // thuộc tính id_dm
        private $Name = ""; // thuộc tính tên danhmuc

        public function setId($id_dm){
            return $this->id_dm = $id_dm;
        }
        public function getId(){
            return $this->id_dm;
        }
        public function setName($Name){
            return  $this->Name = $Name;
        }
        public function getName(){
            return  $this->Name;
        }

        public function getDS_Danhmuc(){
            $xl = new xl_data();
            $sql = "SELECT * FROM `danhmuc`";
            $results = $xl->readitem($sql);
            return $results;
        }




    }



?>