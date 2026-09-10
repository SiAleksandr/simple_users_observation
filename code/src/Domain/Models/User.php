<?php

namespace Geekbrains\Application1\Domain\Models;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Infrastructure\Storage;
use Geekbrains\Application1\Application\Auth;

class User {

    private ?int $userId;

    private ?string $userName;

    private ?string $userLastName;

    private ?int $userBirthday;

    private ?string $userLogin;
    
    private ?string $userPasswordHash;

    public function __construct(int $id = null, string $name = null, string $lastName = null, int $birthday = null){
        $this->userId = $id;
        $this->userName = $name;
        $this->userLastName = $lastName;
        $this->userBirthday = $birthday;
    }

    public function setName(string $userName) : void {
        $this->userName = $userName;
    }

    public function setLastName(string $userLastName) : void {
        $this->userLastName = $userLastName;
    }

    public function getUserName(): ?string {
        return $this->userName;
    }

    public function getUserLastName(): ?string {
        return $this->userLastName;
    }

    public function setUserLogin(string $userLogin): void {
        $this->userLogin = $userLogin;
    }

    public function getUserLogin(): ?string {
        return $this->userLogin;
    }

    public function getUserBirthday(): ?int {
        return $this->userBirthday;
    }

    public function getUserId(): ?int {
        return $this->userId;
    }

    public function setBirthdayFromString(string $birthdayString) : void {
        $this->userBirthday = strtotime($birthdayString);
    }

    public static function getAllUsersFromStorage(): array {
        $sql = "SELECT * FROM users";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute();
        $result = $handler->fetchAll();

        $users = [];

        foreach($result as $item){
            $user = new User($item['id_user'], $item['user_name'], $item['user_lastname'], $item['user_birthday_timestamp']);
            $users[] = $user;
        }
        
        return $users;
    }

    public static function validateRequestData(bool $isNew): bool{
        $result = true;
        
        if(!(
            isset($_POST['name']) && !empty($_POST['name']) && 
            isset($_POST['lastname']) && !empty($_POST['lastname']) &&
            isset($_POST['login']) && !empty($_POST['login']) &&
            !preg_match('/<[^>]/', $_POST['name']) &&
            !preg_match('/<[^>]/', $_POST['lastname']) &&
            !preg_match('/<[^>]/', $_POST['login']) &&
            isset($_POST['birthday']) &&
            isset($_POST['password']) && 
            ((!empty($_POST['password']) && $isNew) || !$isNew)
        )){
            $result = false;
        }

        if(!preg_match('/^$|(\d{2}-\d{2}-\d{4})$/', $_POST['birthday'])){
            $result =  false;
        }

        if(!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] != $_POST['csrf_token']){
            $result = false;
        }

        return $result;
    }

    public function setParamsFromRequestData(bool $isNew): void {
        $this->userName = $_POST['name'];
        $this->userLastName = $_POST['lastname'];
        if(!empty($_POST['birthday'])) {
            $this->setBirthdayFromString($_POST['birthday']); 
        }
        $this->userLogin = $_POST['login'];
        if($isNew || (!empty($_POST['password']) && !$isNew)) {
            $this->userPasswordHash = Auth::getPasswordHash($_POST['password']);
        }
    }
    // htmlspecialchars($_POST['name']);

    public function saveToStorage(){
        $sql = "INSERT INTO users(user_name, user_lastname, user_birthday_timestamp, login, password_hash) VALUES (:user_name, :user_lastname, :user_birthday, :user_login, :user_password)";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'user_name' => $this->userName,
            'user_lastname' => $this->userLastName,
            'user_birthday' => $this->userBirthday,
            'user_login' => $this->userLogin,
            'user_password' => $this->userPasswordHash
        ]);
    }

    public function getUserDataAsArray(): array {
        $userArray = [
            'id' => $this->userId,
            'username' => $this->userName, 
            'userlastname' => $this->userLastName
        ];
        if($this->userBirthday == null) {
            $userArray['userbirthday'] = null;
        }
        else {
            $userArray['userbirthday'] = date('d-m-Y', $this->userBirthday);
        }

        return $userArray;
    }

    public static function exists($id): bool{
        $sql = "SELECT count(id_user) as user_count FROM users WHERE id_user = :id_user";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'id_user' => $id
        ]);

        $result = $handler->fetchAll();

        if(count($result) > 0 && $result[0]['user_count'] > 0){
            return true;
        }
        else {
            return false;
        }
    }

    public static function isAdmin($id): bool {
        $sql = "SELECT * FROM user_roles WHERE id_user = :id AND role = 'admin'";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id' => $id]);
        $result = $handler->fetchAll();

        if(empty($result)) {
            return false;

        } else {
            return true;
        }
    }

    public static function getUserDataFromDb(int $id): array {
        $sql = "SELECT * FROM users WHERE id_user = :id_user";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id_user' => $id]);

        $userData = $handler->fetchAll();
        if(!empty($userData)) {
            $dataArray = $userData[0];
        }
        else {
            $dataArray = [];
        }
        return $dataArray;
    }
            
    public function updateSelfInStorage($id): void {
        $sql = "UPDATE users SET user_name = :user_name, user_lastname = :user_lastname, 
            user_birthday_timestamp = :birthdate_timestamp, login = :user_login 
            WHERE id_user = :id_user";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute([
            'user_name' => $this->userName,
            'user_lastname' => $this->userLastName,
            'birthdate_timestamp' => $this->userBirthday,
            'user_login' => $this->userLogin,
            'id_user' => $id
        ]);
    }

    public function updateSelfPasswordInStorage($id, bool $isEmpty): void {
        if(!$isEmpty) {
            $sql = "UPDATE users SET password_hash = :password_hash 
                WHERE id_user = :id_user";
            $handler = Application::$storage->get()->prepare($sql);
            $handler->execute([
                'password_hash' => $this->userPasswordHash,
                'id_user' => $id
            ]);
        }  
    }

    public static function deleteFromStorage(int $user_id) : void {
        $sql = "DELETE FROM users WHERE id_user = :id_user";

        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id_user' => $user_id]);
    }
}