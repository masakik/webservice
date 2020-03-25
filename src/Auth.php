<?php namespace Uspdev\Webservice;

class Auth
{
    public static function listarUsuarios($pwdfile = '')
    {
        $users = SELF::carregaUsuariosDoArquivo($pwdfile);
        $ret = [];
        foreach ($users as $user => $prop) {
            $prop['pwd'] = 'shhh, não posso mostrar';
            $ret[$user] = $prop;
        }
        return $ret;
    }

    public static function obterUsuarioAtual()
    {
        return empty($_SERVER['PHP_AUTH_USER']) ? 'anônimo' : $_SERVER['PHP_AUTH_USER'];
    }

    public static function liberarUsuario($ctrl = 0)
    {
        $users = SELF::carregaUsuariosDoArquivo();
        if ($user = SELF::autenticaUsuarioSenha($users)) {
            if (SELF::autenticaAdmin($user)) {
                return true;
            }
            if (empty($ctrl) || SELF::autenticaAllow($user, $ctrl)) {
                return true;
            }
        }

        // vamos fazer o navegador enviar credenciais
        SELF::logout();
        \Flight::unauthorized('Acesso não autorizado para ' . SELF::obterUsuarioAtual());
    }

    public static function liberarAdmin()
    {
        $users = SELF::carregaUsuariosDoArquivo();
        if ($user = SELF::autenticaUsuarioSenha($users)) {
            if (SELF::autenticaAdmin($user)) {
                return true;
            }
        }

        // vamos fazer o navegador enviar credenciais
        SELF::logout();
        \Flight::unauthorized('Acesso admin não autorizado para ' . SELF::obterUsuarioAtual());
    }

    public static function logout()
    {
        // ao enviar este header o navegador vai solicitar novas credenciais
        header('WWW-Authenticate: Basic realm="use this hash key to encode"');
    }

    private static function autenticaUsuarioSenha($users)
    {
        // se não houver usuário vamos negar acesso
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            return false;
        }

        $user = $_SERVER['PHP_AUTH_USER'];
        $pwd = $_SERVER['PHP_AUTH_PW'];

        // vamos negar acesso
        if (
            !isset($users[$user]) // usuario invalido
             or !password_verify($pwd, $users[$user]['pwd']) // senha invalida
        ) {
            return false;
        }
        return $users[$user];
    }

    private static function autenticaAllow($user, $ctrl)
    {
        // vamos permitir wildcard
        if ($user['allow'] == '*') {
            return true;
        }

        // vamos negar acesso se controller nao autorizado
        if (!empty($ctrl) && strpos($user['allow'], $ctrl) === false) {
            return false;
        } else {
            return true;
        }
    }

    private static function autenticaAdmin($user)
    {
        return ($user['admin'] == 1) ? true : false;
    }

    private static function carregaUsuariosDoArquivo($pwdfile = '')
    {
        $pwdfile = empty($pwdfile) ? getenv('USPDEV_WEBSERVICE_PWD_FILE') : $pwdfile;
        $users = [];
        // vamos ler o arquivo de senhas
        if (($handle = fopen($pwdfile, 'r')) !== false) {
            while (($linha = fgetcsv($handle, 1000, ':')) !== false) {
                $users[$linha[0]] = [
                    'pwd' => $linha[1],
                    'admin' => $linha[2],
                    'allow' => empty($linha[3]) ? 0 : $linha[3],
                ];
            }
            fclose($handle);
        }
        return $users;
    }

    private static function gravaUsuariosNoArquivo($users, $pwdfile = '')
    {
        $pwdfile = empty($pwdfile) ? getenv('USPDEV_WEBSERVICE_PWD_FILE') : $pwdfile;
        if (($handle = fopen($pwdfile, 'w')) !== false) {
            foreach ($users as $linha) {
                fputcsv($handle, $linha, ':');
            }
            fclose($handle);
        }
    }

    // public static function login()
    // {
    //     header('Access-Control-Allow-Origin: *');
    //     header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    //     header('Access-Control-Allow-Headers: authorization');

    //     if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
    //         //$this->msg = 'OK';
    //         return true;
    //     }

    //     if (!isset($_SERVER['PHP_AUTH_USER'])) {
    //         header('WWW-Authenticate: Basic realm="use this hash key to encode"');
    //         //header('HTTP/1.0 401 Unauthorized');
    //         //$this->msg = 'Você deve digitar um login e senha válidos para acessar este recurso';
    //         return false;
    //     }

    //     if (SELF::liberar()) {
    //         //$this->msg = 'Login com successo';
    //         return true;
    //     }

    //     //$this->msg = 'Usuário ou senha inválidos';
    //     return false;
    // }
}
