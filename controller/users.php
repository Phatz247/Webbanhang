<?php

require_once '../Model/user.php';

class users{
    private $userModel;

    public function __construct(){
        $this->userModel = new user();
    }

    public function register(){
        //xử lý form

        //sanitize POST data
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        //init data
        $data = [
            'usersName' => trim($_POST['usersName']),
            'userspwd' => trim($_POST['usersPwd']),
            'pwdRepeat' => trim($_POST['pwdRepeat']),
        ];

        //validate data
        if(empty($data['userName']) || empty($data['usersPwd']) || empty($data['pwdRepeat'])){
            // flash('register', 'Please fill in all fields');
            // header('location: ../View/signup.php');
            // exit();
        }
    }
}

$init = new users;

//check user
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    switch($_POST['type']){
        case 'register':
            $init->register();
            break;
       
    }
}
