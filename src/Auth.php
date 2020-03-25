<?php

namespace Uspdev\Webservice;

class Auth
{
    public $msg;

    // formato: ['username' => ['pwd'=>pwd_hash, 'admin'=>'0|1']];
    private $users = [];

    public function __construct($pwdfile = '')
    {
        if (empty($pwdfile)) {
            $pwdfile = getenv('USPDEV_WEBSERVICE_PWD_FILE');
        }

        // vamos ler o arquivo de senhas
        if (($handle = fopen($pwdfile, 'r')) !== false) {
            while (($user = fgetcsv($handle, 1000, ':')) !== false) {
                $this->users[$user[0]] = [
                    'pwd' => $user[1], 
                    'admin' => empty($user[2]) ? 0 : $user[2]
                ];
            }
            fclose($handle);
        }
    }

    public function getUsers()
    {
        $ret = [];
        foreach ($this->users as $user=>$prop) {
            $prop['pwd'] = 'hidden password';
            $ret[$user] = $prop;
        } 
        return $ret;
    }

    public function auth()
    {
        // se não houver usuário vamos negar acesso
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            return false;
        }

        $user = $_SERVER['PHP_AUTH_USER'];
        $pwd = $_SERVER['PHP_AUTH_PW'];

        //echo $user,$pwd;exit;

        // se o usuario não existir ou se a senha não conferir vamos negar acesso
        if (!isset($this->users[$user]) or !password_verify($pwd, $this->users[$user]['pwd'])) {
            return false;
        }

        // tudo certo, acesso liberado
        return true;
    }

    public function login()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: authorization');

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $this->msg = 'OK';
            return true;
        }

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="use this hash key to encode"');
            //header('HTTP/1.0 401 Unauthorized');
            $this->msg = 'Você deve digitar um login e senha válidos para acessar este recurso';
            return false;
        }

        if ($this->auth()) {
            $this->msg = 'Login com successo';
            return true;
        }

        $this->msg = 'Usuário ou senha inválidos';
        return false;
    }

    public static function logout()
    {
        header('WWW-Authenticate: Basic realm="use this hash key to encode"');
        //header('HTTP/1.0 401 Unauthorized');
        //exit;
        //die('logout');
    }
}
