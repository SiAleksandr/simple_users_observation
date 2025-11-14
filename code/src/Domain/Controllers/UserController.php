<?php

namespace Geekbrains\Application1\Domain\Controllers;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Application\Render;
use Geekbrains\Application1\Application\Auth;
use Geekbrains\Application1\Domain\Models\User;

class UserController extends AbstractController {

    protected array $actionsPermissions = [
        'actionSave' => ['admin'],
        'actionDelete' => ['admin'],
        'actionIndex' => ['admin', 'user'],
        'actionIndexRefresh' => ['admin', 'user'],
        'actionEdit' => ['admin'],
        'actionLogin' => ['visitor'],
        'actionAuth' => ['visitor'],
        'actionLogout' => ['admin', 'user'],
        'actionUpdate' => ['admin']
    ];

    public function actionIndex(): string {
        $users = User::getAllUsersFromStorage();

        $isCurentAdmin = false;

        if(isset($_SESSION['auth']['id_user'])) {
            $isCurentAdmin = User::isAdmin($_SESSION['auth']['id_user']);
        }
        
        $render = new Render();

        if(!$users){
            return $render->renderPage(
                'user-empty.tpl', 
                [
                    'title' => 'Список пользователей в хранилище',
                    'message' => "Список пуст или не найден"
                ]);
        }
        else{
            return $render->renderPage(
                'user-index.tpl', 
                [
                    'title' => 'Список пользователей в хранилище',
                    'users' => $users,
                    'isAdmin' => $isCurentAdmin
                ]);
        }
    }

    public function actionIndexRefresh(){

        $users = User::getAllUsersFromStorage();
        $usersData = [];

        if(count($users) > 0) {
            foreach($users as $user){
                $usersData[] = $user->getUserDataAsArray();
            }
        }

        return json_encode($usersData);
    }

    public function actionSave(): string {
        $new = !isset($_POST['id']);

        if(User::validateRequestData($new)) {
            $message = "Ошибка. Поступил некорректный ID пользователя";

            $user = new User();
            $user->setParamsFromRequestData();

            if(!$new) {
                if(User::exists($_POST['id'])) {
                    $user->updateSelfInStorage($_POST['id']);

                    $message = "Обновлён пользователь с ID = " . $_POST['id'];
                }
            }
            else {
                $user->saveToStorage();

                $message = "Создан пользователь " . $user->getUserName() . " " . $user->getUserLastName();
            }
            $render = new Render();

            return $render->renderPage(
                'user-message.tpl', 
                [
                    'title' => 'Новые изменения',
                    'message' => $message
                ]);
        }
        else {
            throw new \Exception("Переданные данные некорректны");
        }
    }

    public function actionUpdate(): string {
        $render = new Render();

        if(isset($_GET['id']) && User::exists($_GET['id'])) {
            
            $userData = User::getUserDataFromDb($_GET['id']);

            $user = new User(
                $userData['id_user'],
                $userData['user_name'],
                $userData['user_lastname'],
                $userData['user_birthday_timestamp']
            );
            $user->setUserLogin($userData['login']);

            return $render->renderPageWithForm(
                'user-form.tpl',
                [
                    'title' => 'Форма редактирования пользователя', 
                    'need_update' => true,
                    'user' => $user
                ]
                );
        }
        else {
            throw new \Exception("Ошибка. Неверный ID пользователя");
        }
    }

    public function actionDelete(): string {
        if(
            (isset($_POST['id_user']) 
            && !isset($_GET['id'])) || 
            (isset($_GET['id'])
            && !isset($_POST['id_user']))
        ) {
            if(isset($_GET['id'])) {
                $target = $_GET['id'];
            }
            else {
                $target = $_POST['id_user'];
            }
            $allowDelete = false;

            $message = "Ошибка. Удаление не выполнено";
            if(
                User::isAdmin($_SESSION['auth']['id_user']) 
                && User::exists($target) 
                && !($target == $_SESSION['auth']['id_user'])
             ) {
                    $allowDelete = true;

                    if(isset($_GET['id'])) {
                        $userData = User::getUserDataFromDb($target);
                        $message = "Удалён пользователь " . 
                            $userData['user_name'] . " " . 
                            $userData['user_lastname'];
                    }
                    User::deleteFromStorage($target);
            }
            if(isset($_POST['id_user'])) {
                $answer = [];
                $answer[] = ['consent' => $allowDelete];

                return json_encode($answer);
            }
            $render = new Render();

            return $render->renderPage(
                'user-message.tpl',
                [
                    'title' => 'Удаление пользователя',
                    'message' => $message
                ]);
        } 
        else {
            throw new \Exception("Ошибка. Удаление невозможно или запрещено");
        }
    }

    public function actionEdit(): string {
        $render = new Render();
        
        return $render->renderPageWithForm(
                'user-form.tpl', 
                [
                    'title' => 'Форма создания пользователя', 
                    'need_update' => false
                ]);
    }

    public function actionAuth(): string {
        $render = new Render();
        
        return $render->renderPageWithForm(
                'user-auth.tpl', 
                [
                    'title' => 'Форма логина'
                ]);
    }

    public function actionLogin(): string {
        $result = false;

        if(isset($_POST['login']) && isset($_POST['password'])){
            $result = Application::$auth->proceedAuth($_POST['login'], $_POST['password']);
        } 
        
        if(!$result){
            $render = new Render();

            return $render->renderPageWithForm(
                'user-auth.tpl', 
                [
                    'title' => 'Форма логина',
                    'auth_success' => false,
                    'auth_error' => 'Неверные логин или пароль'
                ]);
        }
        else{
            header('Location: /');
            return "";
        }
    }

    public function actionLogout(): string {
        if(isset($_SESSION['auth']['user_name'])){
            session_destroy();
            
            unset($_SESSION['auth']);
        }
        $render = new Render();

        return $render->renderPage();
    }
}