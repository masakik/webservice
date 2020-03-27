<?php
# ---------------------------------------------------------------------
# CONFIGURAÇÕES GERAIS, AS VEZES FICAM EM UM ARQUIVO DE CONFIG  -------

# 1-cache desabilitado
# 0-cache normal (default)
# veja https://github.com/uspdev/cache
putenv('USPDEV_CACHE_DISABLE=1');

# Vamos limitar o acesso à máquina local
# ''-desabilitado (default)
# 'localhost'-limita somente à máquina local
# 'whitelist'-carrega um arquivo com as regras de acesso
# veja https://github.com/uspdev/ip-control
putenv('USPDEV_IP_CONTROL=localhost');

# O dominio vamos tentar adivinhar.
# Qualquer problema pode ser atribuído manualmente
putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . Flight::request()->base); # sem / no final

# Local onde o webservice colocará arquivos sqlite, logs, etc.
# Mandatário
putenv('USPDEV_WEBSERVICE_LOCAL=' . __DIR__ . '/../local');

# 1-faz o navegador solicitar as credenciais do usuário;
# 0-nega acesso (default)
putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=0');

# Rota para gerencimaneto do webservice . default='ws'
putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=ws');

# ------------------------------------------------------------------
# COISAS QUE GERALMENTE FICAM NO INDEX.PHP -------------------------

require_once __DIR__ . '/../../vendor/autoload.php';

use Uspdev\Ipcontrol\Ipcontrol;
use Uspdev\Webservice\Rota;

// vamos limitar o acesso por IP
Ipcontrol::proteger();

# Rota raiz - O que será mostrado na raiz do webservice.
# Para exibir somente uma mensagem:
# Rota::raiz('Mensagem');
# Para listar os controladores disponíveis
Rota::raiz($controllers);

# Controlador de gerencia do webservice (opcional).
# Se comentar essa linha não haverá uma interface de gerenciamento web.
# Para alterar o caminho veja USPDEV_WEBSERVICE_ADMIN_ROUTE
Rota::admin();

# Aqui chamamos como http://servidor/controlador/metodo/parametro
Rota::controladorMetodo($controllers);

# Vamos carregar tudo o que é necessário.
Rota::iniciar();
