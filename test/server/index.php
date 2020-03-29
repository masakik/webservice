<?php
if (is_file(__DIR__ . '/../../vendor/autoload.php')) {
    // clonou o git e rodou composer install
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    // instalou via composer
    require_once __DIR__ . '/../../../../../vendor/autoload.php';
}

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Webservice\Webservice as WS;

// sem cache nos testes
putenv('USPDEV_CACHE_DISABLE=1');

// Vamos limitar o acesso à máquina local
putenv('USPDEV_IP_CONTROL=localhost');

// O dominio vamos tentar adivinhar
putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . Flight::request()->base); # sem / no final

// local onde o webservice colocará arquivos sqlite, logs, etc.
putenv('USPDEV_WEBSERVICE_LOCAL=' . __DIR__ . '/..');

// faz o navegador solicitar as credenciais do usuário. Default 0
putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=1');

// Rota para admin. Default 'ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

// ----------------------------

// vamos carregar alguns dados para testes
require_once __DIR__ . '/../mock_data.php';

// vamos limitar o acesso por IP
Ipcontrol::proteger();


$controllers['minhaclasse2'] = 'Minhaclasse2';
$controllers['run'] = 'Minhaclasse1::meumetodo1';
//Rota::raiz('mensagem');
WS::raiz($controllers);

WS::rota(['run', 'Minhaclasse1::meumetodo1']);
// Flight::route('/run(/@param1)', function($param1) {
//     $out = Minhaclasse1::meumetodo1($param1);
//     Flight::json($out);
// });


# Controlador de gerencia do webservice (opcional)
WS::admin();

// aqui chamamos como http://servidor/controlador/metodo/parametro
//WS::controladorMetodo($controllers);

WS::iniciar();
