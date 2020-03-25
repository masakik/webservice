<?php

// sem cache nos testes
putenv('USPDEV_CACHE_DISABLE=1');

putenv('USPDEV_IP_CONTROL=localhost');

// O dominio vamos tentar adivinhar
putenv('DOMINIO=http://' . $_SERVER['HTTP_HOST'] . Flight::request()->base); # sem / no final

// gerar usuarios
putenv('USPDEV_WEBSERVICE_PWD_FILE=' . __DIR__ . '/users.txt');
$users[] = ['admin', password_hash('admin', PASSWORD_DEFAULT), '1', ''];
$users[] = ['user1', password_hash('user', PASSWORD_DEFAULT), '', 'minhaclasse1'];
$users[] = ['user2', password_hash('user', PASSWORD_DEFAULT), '', 'minhaclasse1, minhaclasse2'];
$users[] = ['user3', password_hash('user', PASSWORD_DEFAULT), '', '*'];
//print_r($users);exit;
$fp = fopen(getenv('USPDEV_WEBSERVICE_PWD_FILE'), 'w');
foreach ($users as $linha) {
    fputcsv($fp, $linha, ':');
}
fclose($fp);

// controlador teste com algumas classes
$controllers['minhaclasse1'] = 'Minhaclasse1'; //'Uspdev\Evasao\Evasao';

class Minhaclasse1
{
    public static function meuMetodo1($param = '')
    {
        return 'Este é o resultado do metodo 1 com o parametro ' . $param;
    }

    public static function meuMetodo2()
    {
        return 'Este é o resultado do metodo 2 que não aceita parametros';
    }
}

$controllers['minhaclasse2'] = 'Minhaclasse2'; //'Uspdev\Evasao\Evasao';

class Minhaclasse2
{
    public static function meuMetodo1($param = '')
    {
        return 'Classe2 => metodo1 com o parametro ' . $param;
    }

    public static function meuMetodo2()
    {
        return 'Classe2 => metodo2 que não aceita parametros';
    }
}
