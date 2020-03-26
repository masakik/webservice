<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Uspdev\Webservice\Rota;
use Uspdev\Ipcontrol\Ipcontrol;

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
