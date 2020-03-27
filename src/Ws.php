<?php namespace Uspdev\Webservice;

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Cache\Cache;

class Ws
{
    public static function status()
    {
        $out['meu ip'] = $_SERVER['REMOTE_ADDR'];
        $out['meu user'] = Auth::obterUsuarioAtual();
        
        $amb['local'] = getenv('USPDEV_WEBSERVICE_LOCAL');
        $amb['admin_route'] = getenv('USPDEV_WEBSERVICE_ADMIN_ROUTE');
        $amb['user_friendly'] = getenv('USPDEV_WEBSERVICE_USER_FRIENDLY');

        $out['ambiente'] = $amb;
        $c = new Cache();
        $out['cache'] = $c->status();
        
        $out['ip_control'] = Ipcontrol::status();

        $users = Auth::listarUsuarios();
        $out['usuarios']['total'] = count($users);
        $out['usuarios']['url'] = getenv('DOMINIO').'/'.getenv('USPDEV_WEBSERVICE_ADMIN_ROUTE').'/auth';

        return $out;
    }

    public static function auth() {
        return Auth::listarUsuarios();
    }

    public static function login()
    {
        // if (Auth::login()) {
        //     //return ['msg' => $auth->msg];
        //     return 'ok';
        // } else {
        //     \Flight::unauthorized('Acesso não autorizado');
        // }
    }

    public static function logout()
    {
        Auth::logout();
        //\Flight::unauthorized($auth->msg);
        \Flight::unauthorized('Você deve digitar um login e senha válidos para acessar este recurso');
    }
}
