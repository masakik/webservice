<?php namespace Uspdev\Webservice;

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Cache\Cache;

class Ws
{
    public static function status()
    {
        $out['meu ip'] = $_SERVER['REMOTE_ADDR'];
        $out['meu user'] = Auth::obterUsuarioAtual();

        $c = new Cache();
        $out['cache'] = $c->status();
        
        $out['ip_control'] = Ipcontrol::status();

        $out['usuarios'] = Auth::listarUsuarios();

        return $out;
    }

    public static function users() {
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
