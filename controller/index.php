<?php 

include "../controller/controller.php";

if(isset($_REQUEST['act'])){
    $act = $_REQUEST['act'];
} else {
    $act = 'index';
}
 $controller = new controller();
 $danhmuc = $controller->hienthidm();
// Khởi tạo đối tượng controller
    include "../view/header.php";

?>