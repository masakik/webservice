<?php namespace Uspdev\Webservice;

use \RedBeanPHP\R as DB;

class Auth
{
    // arquivo onde será guardado a base de usuários
    const auth_file = 'uspdev_webservice_auth.db3';

    public static function salvarUsuario($user_novo)
    {
        SELF::abreDB();
        $user = DB::findOrCreate('usuario', [
            'username' => $user_novo['username'],
        ]);
        $user->import($user_novo, 'admin,allow');
        if (!empty($user_novo['pwd'])) {
            $user->pwd = password_hash($user_novo['pwd'], PASSWORD_DEFAULT);
        }
        DB::store($user);
        SELF::fechaDB();
    }

    public static function removerUsuario($user)
    {
        SELF::abreDB();
        DB::hunt('usuario', 'username = ?', [$user['username']]);
        SELF::fechaDB();
    }

    public static function listarUsuarios()
    {
        SELF::abreDB();
        $users = DB::exportAll(DB::findAll('usuario'));
        SELF::fechaDB();
        
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

    public static function liberar($escopo = 'usuario', $ctrl = 0)
    {
        if ($user = SELF::autenticaUsuarioSenha()) {

            // se for admin já libera sem verificar escopo
            if (SELF::autenticaAdmin($user)) {
                return true;
            }

            switch ($escopo) {
                case 'usuario':
                    if (empty($ctrl) || SELF::autenticaAllow($user, $ctrl)) {
                        return true;
                    }
                    break;
                case 'admin':
                    // aqui não precisa fazer nada
                    break;
            }
        }

        return false;
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
        $user = DB::findOne('usuario', ' username = ? ', [$auth_user]);
        SELF::fechaDB();

        if ($user) {
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
        if (!DB::testConnection()) {
            DB::addDatabase('webservice_auth', 'sqlite:' . getenv('USPDEV_WEBSERVICE_LOCAL') . '/' . SELF::auth_file);
            DB::selectDatabase('webservice_auth');
            DB::useFeatureSet( 'novice/latest' );
            DB::freeze(true);
        }
        DB::selectDatabase('webservice_auth');
    }

    protected static function fechaDB()
    {
        DB::close();
    }
}
