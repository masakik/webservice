<?php namespace Uspdev\Webservice;

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Cache\Cache;

class Ws
{
    public static function metodos($obj)
    {
        $metodos = get_class_methods($obj);

        $classe = get_class($obj);
        if ($pos = strrpos($classe, '\\')) {
            $classe = substr($classe, $pos + 1);
        }
        $classe = strtolower($classe);

        foreach ($metodos as $m) {
            // para cada método vamos obter os parâmetros
            $r = new \ReflectionMethod($obj, $m);
            $params = $r->getParameters();

            // vamos listar somente os métodos publicos
            if ($r->isPublic()) {
                $p = '/';
                foreach ($params as $param) {
                    $p .= '{' . $param->getName() . '}, ';
                }
                $p = substr($p, 0, -2);

                // vamos apresentar na forma de url
                $api[$m] = getenv('DOMINIO') . '/' . $classe . '/' . $m . $p;
            }
        }
        return $api;
    }

    public static function status()
    {
        $out['meu ip'] = $_SERVER['REMOTE_ADDR'];
        $out['meu user'] = Auth::getUsuarioAtual();

        $c = new Cache();
        $out['cache'] = $c->status();
        
        $out['ip_control'] = Ipcontrol::status();

        $out['usuarios'] = Auth::getUsuarios();

        return $out;
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
