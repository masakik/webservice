<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Webservice\Rota;

// sem cache nos testes
putenv('USPDEV_CACHE_DISABLE=1');

// Vamos limitar o acesso à máquina local
putenv('USPDEV_IP_CONTROL=localhost');

// local onde o webservice colocará arquivos sqlite, logs, etc.
putenv('USPDEV_WEBSERVICE_LOCAL=' . __DIR__ . '/..');

// O dominio vamos tentar adivinhar
putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . Flight::request()->base); # sem / no final

// Rota e classe padrão para admin
putenv('USPDEV_WEBSERVICE_MGMT_ROUTE=ws');
putenv('USPDEV_WEBSERVICE_MGMT_CLASS=Uspdev\Webservice\Ws');

// ----------------------------

// vamos carregar alguns dados para testes
require_once __DIR__ . '/../mock_data.php';

// vamos limitar o acesso por IP
Ipcontrol::proteger();

//Rota::raiz('mensagem');
Rota::raiz($controllers);

# Controlador de gerencia do webservice (opcional)
Rota::admin();

// aqui chamamos como http://servidor/controlador/metodo/parametro
Rota::controladorMetodo($controllers);

Rota::iniciar();
