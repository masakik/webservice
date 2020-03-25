<?php namespace Uspdev\Webservice;

use Uspdev\Cache\Cache;
use \Flight;

class Rota
{
    public static function raiz($controllers = '')
    {
        Flight::route('/', function () use ($controllers) {

            if (is_array($controllers)) {
                foreach ($controllers as $key => $val) {
                    $out[$key] = getenv('DOMINIO') . '/' . $key;
                }
            } else {
                $out['msg'] = $controllers;
            }
            Flight::jsonf($out);

        });
    }

    public static function admin($mgmt_route = '', $mgmt_class = '')
    {
        // valores padrão
        $mgmt_route = empty($mgmt_route) ? 'ws' : $mgmt_route;
        $mgmt_class = empty($mgmt_class) ? 'Uspdev\Webservice\Ws' : $mgmt_class;

        // vamos criar as rotas específicas de admininistação do webservice
        Flight::route('GET /' . $mgmt_route . '(/@metodo:[a-z]+(/@param1))', function ($metodo, $param1) use ($mgmt_class) {

            // vamos verificar se o usuário é valido
            $auth = new Auth();
            if (!$auth->auth()) {
                $auth->logout();
                if (!$auth->login()) {
                    Flight::unauthorized($auth->msg);
                }
            }

            $ctrl = new $mgmt_class();
            if (empty($metodo)) {
                // se nao foi passado metodo vamos mostrar a lista de metodos publicos
                $out = Ws::metodos($ctrl);
            } else {
                // se foi passado vamos chama-lo
                $out = $ctrl->$metodo($param1);
            }
            Flight::jsonf($out);

        });
    }

    public static function controladorMetodo($controllers)
    {
        // vamos mapear todas as rotas para o controller selecionado
        Flight::route('GET /@controlador:[a-z0-9]+(/@metodo:[a-z0-9]+(/@param1))', function ($controlador, $metodo, $param1) use ($controllers) {

            // vamos verificar se o usuário é valido
            $auth = new Auth();
            if (!$auth->auth()) {
                $auth->logout();
                if (!$auth->login()) {
                    Flight::unauthorized($auth->msg);
                }
            }

            // se o controlador passado nao existir
            if (empty($controllers[$controlador])) {
                Flight::notFound('Controlador inexistente');
            }

            // como o controlador existe, vamos instanciar
            $ctrl = new $controllers[$controlador];

            // se nao foi passado metodo vamos mostrar a lista de metodos publicos
            if (empty($metodo)) {
                $out = Ws::metodos($ctrl);
                Flight::jsonf($out);
                exit;
            }

            // se o método não existe vamos abortar
            if (!method_exists($ctrl, $metodo)) {
                Flight::notFound('Metodo inexistente');
            }

            // agora que está tudo certo vamos fazer a chamada usando cache
            $c = new Cache($ctrl);
            $out = $c->getCached($metodo, [$param1]);

            // vamos formatar a saída de acordo com format=?
            $f = Flight::request()->query['format'];
            switch ($f) {
                case 'csv':
                    Flight::csv($out);
                    break;
                case 'json':
                    Flight::jsonf($out);
                    break;
                default:
                    Flight::json($out);
            }
        });
    }

    public static function iniciar()
    {
        SELF::mapearFuncoes();
        Flight::start();
    }

    private static function mapearFuncoes()
    {
        // vamos imprimir o json formatado para humanos lerem
        Flight::map('jsonf', function ($data) {
            Flight::json($data, 200, true, 'utf-8', JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Flight::stop();
        });

        // vamos imprimir a saida em csv
        Flight::map('csv', function ($data) {

            header("Content-type: text/csv");
            header("Content-Disposition: inline; filename=file.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if (!empty($data[0])) {
                // aqui se espera um array de arrays onde as chaves são a primeira linha da planilha
                $keys = array_keys($data[0]);
                fputcsv($out, $keys, ';');

                // e os dados vêm nas linhas subsequentes
                foreach ($data as $row) {
                    fputcsv($out, $row, ';');
                }
            } else {
                // se for um array simples vamos exportar linha a linha sem cabecalho
                foreach ($data as $key => $val) {
                    fputcsv($out, [$key, $val], ';');
                }
            }
            fclose($out);

        });

        // vamos sobrescrever a mensagem de not found para ficar mais compatível com a API
        // retorna 404 mas com mensagem personalizada opcional ou mensagem padrão
        Flight::map('notFound', function ($msg = null) {
            $data['message'] = empty($msg) ? 'Not Found' : $msg;
            $data['documentation_url'] = getenv('DOMINIO') . '/';
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            Flight::halt(404, $json);
        });

        // Interrompe a execução com 403 - Forbidden
        // usado quando negado acesso por IP
        Flight::map('forbidden', function ($msg = null) {
            $data['message'] = empty($msg) ? 'Forbidden' : $msg;
            $data['documentation_url'] = getenv('DOMINIO') . '/';
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Flight::halt(403, $json);
        });

        Flight::map('unauthorized', function ($msg = null) {
            $data['message'] = empty($msg) ? 'unauthorized' : $msg;
            $data['documentation_url'] = getenv('DOMINIO') . '/';
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Flight::halt(401, $json);
        });
    }
}
