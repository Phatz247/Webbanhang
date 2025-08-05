<?php
include "../model/danhmuc.php";
class controller{

    public function hienthidm(){
        $dm = new danhmuc();
        return $dm->getDS_Danhmuc();
    }

}


?>
