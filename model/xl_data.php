<?php
include "../model/database.php";
class xl_data extends database{
    //read data
    public function __construct(){}
    // hàm thực hiện câu sql có lấy giá trị trả về
    function readitem($sql){
        $result = $this->connection_database()->query($sql);
        $danhsach = $result->fetchAll();
        return $danhsach;
       }
    
    // execute data
     // hàm thực hiện câu sql không lấy giá trị trả về
    function execute_item($sql){
        $conn = new database();
        $conn->connection_database()->query($sql);
    }

}