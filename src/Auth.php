<?php namespace Uspdev\Webservice;

use \RedBeanPHP\R;

class Auth
{
    public static function salvarUsuario($user_novo)
    {
        SELF::abreDB();
        $user = R::findOrCreate('usuario', [
            'username' => $user_novo['username'],
        ]);
        $user->import($user_novo, 'admin,allow');
        if (!empty($user_novo['pwd'])) {
            $user->pwd = password_hash($user_novo['pwd'], PASSWORD_DEFAULT);
        }
        R::store($user);
    }

    public static function removerUsuario($user)
    {
        SELF::abreDB();
        R::hunt('usuario', 'username = ?', [$user['username']]);
    }

    public static function listarUsuarios($pwdfile = '')
    {
        SELF::abreDB();
        $users = R::exportAll(R::findAll('usuario'));
        $ret = [];
        foreach ($users as $user) {
            unset($user['id']);
            $user['pwd'] = 'shhh, não posso mostrar';
            $ret[] = $user;
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

    protected static function autenticaUsuarioSenha()
    {
        // se não houver usuário vamos negar acesso
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            return false;
        }

        $auth_user = $_SERVER['PHP_AUTH_USER'];
        $auth_pwd = $_SERVER['PHP_AUTH_PW'];

        SELF::abreDB();
        if ($user = R::findOne('usuario', ' username = ? ', [$auth_user])) {
            if (password_verify($auth_pwd, $user->pwd)) {
                return $user;
            }
        }
        return false;
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

    protected static function abreDB()
    {
        //echo 'sqlite:' . getenv('USPDEV_WEBSERVICE_LOCAL') . '/Auth.db3';
        if (!R::testConnection()) {
            R::setup('sqlite:' . getenv('USPDEV_WEBSERVICE_LOCAL') . '/Auth.db3');
        }
    }
}
