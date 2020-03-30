<?php
if (is_file(__DIR__ . '/../../vendor/autoload.php')) {
    // clonou o git e rodou composer install
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    // instalou via composer
    require_once __DIR__ . '/../../../../../vendor/autoload.php';
}

$amb = 'dev';

if ($amb == 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    # 1-faz o navegador solicitar as credenciais do usuário;
    # 0-nega acesso se for o caso sem solictar novas credenciais (default)
    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=1');

    # 1-cache desabilitado
    # 0-cache normal (default)
    # veja https://github.com/uspdev/cache
    putenv('USPDEV_CACHE_DISABLE=1');
}

if ($amb == 'prod') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

# Vamos limitar o acesso à máquina local
# ''-desabilitado (default)
# 'localhost'-limita somente à máquina local
# 'whitelist'-carrega um arquivo com as regras de acesso
# veja https://github.com/uspdev/ip-control
putenv('USPDEV_IP_CONTROL=localhost');

// O dominio vamos tentar adivinhar
putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . Flight::request()->base); # sem / no final

// local onde o webservice colocará arquivos sqlite, logs, etc.
putenv('USPDEV_WEBSERVICE_LOCAL=' . realpath(__DIR__ . '/..'));

# Rota para gerencimaneto do webservice .
# default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

// ------------------------------------------------
use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Webservice\Webservice as WS;

// vamos carregar alguns dados para testes
require_once __DIR__ . '/../mock_data.php';

// vamos limitar o acesso por IP
Ipcontrol::proteger();

# Ativa rota de gerência do webservice (opcional)
WS::admin();

// aqui vamos associar classes a rotas disponibilizando assim todos os métodos publicos.
// os métodos devem ser estáticos
// em geral o nome da classe é igual o nome da rota
// http://servidor/rota/metodo/{param1/param2/param3}
// Os parâmetros são opcionais determinado pelos parâmetros do método

$classes['rota1'] = 'Minhaclasse1';
$classes['rota2'] = 'Minhaclasse2';
WS::classes($classes);

// aqui vamos associar rotas a métodos
// http://servidor/controlador/rota/{param1/param2/param3}

$metodos['rota3'] = 'Minhaclasse1::meumetodo1';
WS::metodos($metodos);

// o que irá mostrar na raiz do webservice
// http://servicor/
WS::raiz(array_merge($classes, $metodos));

// vamos fazer a mágica acontecer
WS::iniciar();
