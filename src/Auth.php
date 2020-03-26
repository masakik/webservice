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
        if ($user = SELF::autenticaUsuarioSenha()) {
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
        if ($user = SELF::autenticaUsuarioSenha()) {
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

    public static function cadastrarUsuario($user)
    {

    }

    protected static function autenticaUsuarioSenha()
    {
        // se não houver usuário vamos negar acesso
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            return false;
        }

        $auth_user = $_SERVER['PHP_AUTH_USER'];
        $auth_pwd = $_SERVER['PHP_AUTH_PW'];

        if ($user = SELF::encontraUsuario($auth_user)) {
            if (password_verify($auth_pwd, $user['pwd'])) {
                return $user;
            }
        }
        return false;
    }

    protected static function encontraUsuario($username)
    {
        // username:password:admin:classes
        $users = SELF::carregaUsuariosDoArquivo();
        $key = array_search($username, array_column($users, 'username'));
        if ($key !== false) {
            return $users[$key];
        }
        return [];
    }

    protected static function autenticaAllow($user, $ctrl)
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

    protected static function autenticaAdmin($user)
    {
        return ($user['admin'] == 1) ? true : false;
    }

    protected static function carregaUsuariosDoArquivo($pwdfile = '')
    {
        $pwdfile = empty($pwdfile) ? getenv('USPDEV_WEBSERVICE_PWD_FILE') : $pwdfile;
        $users = [];
        // vamos ler o arquivo de senhas
        if (($handle = fopen($pwdfile, 'r')) !== false) {
            while (($linha = fgetcsv($handle, 1000, ':')) !== false) {
                $user['username'] = $linha[0];
                $user['pwd'] = $linha[1];
                $user['admin'] = $linha[2];
                $user['allow'] = $linha[3];
                $users[] = $user;
            }
            fclose($handle);
        }
        return $users;
    }

    protected static function gravaUsuariosNoArquivo($users, $pwdfile = '')
    {
        $pwdfile = empty($pwdfile) ? getenv('USPDEV_WEBSERVICE_PWD_FILE') : $pwdfile;
        if (($handle = fopen($pwdfile, 'w')) !== false) {
            foreach ($users as $user => $attrib) {
                $linha = [];
                $linha[0] = $user;
                foreach ($attrib as $val) {
                    $linha[] = $val;
                }
                fputcsv($handle, $linha, ':');
            }
            fclose($handle);
        }
    }
}
