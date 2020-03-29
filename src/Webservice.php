<?php namespace Uspdev\Webservice;

use Uspdev\Cache\Cache;
use \Flight;

class Webservice
{
    # valores padrão para variáveis de ambiente
    const user_friendly = 0;
    const admin_route = 'ws';

    # classe para rota de admin
    const admin_class = 'Uspdev\Webservice\Ws';

    protected static function liberar($escopo = 'usuario', $allow = 0)
    {
        if ($auth = Auth::liberar($escopo, $allow)) {
            return true;
        } else {
            // para negar acesso, vamos ler se é user_friendly
            getenv('USPDEV_WEBSERVICE_USER_FRIENDLY') && Auth::logout();

            Flight::unauthorized('Acesso ' . $escopo . ' não autorizado para ' . Auth::obterUsuarioAtual());
        }
    }

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

    // vamos criar as rotas específicas de admininistação do webservice
    public static function admin()
    {
        $rota = getenv('USPDEV_WEBSERVICE_ADMIN_ROUTE');

        Flight::route('GET /' . $rota . '(/@metodo:[a-z]+(/@param1))', function ($metodo, $param1) use ($rota) {

            // vamos verificar se o usuário é valido
            SELF::liberar('admin');

            // a classe admin é uma constante
            $classe = SELF::admin_class;

            // se nao foi passado método vamos mostrar a lista de métodos públicos
            if (empty($metodo)) {
                $prefix = getenv('DOMINIO') . '/' . $rota . '/';
                // o preg_filter é para colocar a url completa nos metodos
                $out = preg_filter('/^/', $prefix, SELF::listarMetodos($classe));
                Flight::jsonf($out);
                exit;
            }

            // se o método não existe vamos abortar
            if (!method_exists($classe, $metodo)) {
                Flight::notFound('Metodo inexistente');
            }

            // vamos fazer a chamada aqui
            $callback = $classe . '::' . $metodo;
            $out = $callback($param1);

            // e mostrar a saída simples
            Flight::jsonf($out);
        });
    }

    public static function metodos($metodos)
    {
        $rotas = array_keys($metodos);

        foreach ($rotas as $rota) {
            $callback = $metodos[$rota];
            Flight::route('/' . $rota . '(/@param1(/@param2(/@param3)))',
                function ($p1, $p2, $p3) use ($rota, $callback) {

                    // vamos verificar se o usuário é valido
                    SELF::liberar('usuario', $rota);

                    // vamos gerar os parametros a serem passados
                    list($classe, $metodo) = explode('::', $callback);
                    $params = SELF::gerarArrayDeParametros($classe, $metodo, [$p1, $p2, $p3]);

                    // agora que está tudo certo vamos fazer a chamada usando cache
                    // $callback é um metodo estático no formato classe::metodo
                    $c = new Cache();
                    $out = $c->getCached($callback, $params);

                    SELF::formatarSaida($out);
                });
        }
    }

    public static function classes($controllers)
    {
        // vamos mapear todas as rotas para o controller selecionado, similar ao codeigniter
        // pode usar até 3 parametros
        $rotas = array_keys($controllers);
        foreach ($rotas as $rota) {
            $classe = $controllers[$rota];
            Flight::route('/' . $rota . '(/@metodo:[a-z0-9]+(/@param1(/@param2(/@param3))))',
                function ($metodo, $p1, $p2, $p3) use ($rota, $classe) {

                    // vamos verificar se o usuário é valido
                    SELF::liberar('usuario', $rota);

                    // se nao foi passado método vamos mostrar a lista de métodos públicos
                    if (empty($metodo)) {
                        $prefix = getenv('DOMINIO') . '/' . $rota . '/';
                        $out = preg_filter('/^/', $prefix, SELF::listarMetodos($classe));
                        SELF::formatarSaida($out);
                    }

                    // se o método passado não existe vamos abortar
                    if (!method_exists($classe, $metodo)) {
                        Flight::notFound('Metodo inexistente');
                    }

                    // vamos gerar os parametros a serem passados
                    $params = SELF::gerarArrayDeParametros($classe, $metodo, [$p1, $p2, $p3]);

                    // agora que está tudo certo vamos fazer a chamada usando cache
                    $callback = $classe . '::' . $metodo;
                    $c = new Cache();
                    $out = $c->getCached($callback, $params);

                    // vamos formatar a saída
                    SELF::formatarSaida($out);
                });
        }

    }

    protected static function gerarArrayDeParametros($class, $method, $params)
    {
        // vamos elimitar os parametros nulos (não foram passados)
        $params = array_filter($params, function ($v) {return !is_null($v);});
        $param_passed = count($params);

        // vamos contar os parametros do método
        $r = new \ReflectionMethod($class, $method);
        $param_allowed = $r->getNumberOfParameters();
        $param_required = $r->getNumberOfRequiredParameters();

        // se a quantidade de parametros for insuficiente vamos abortar com uma mensagem
        if ($param_passed < $param_required) {
            Flight::jsonf('Parâmetros insuficientes. Forneça de ' . $param_required . ' a ' . $param_allowed . ' parâmetros.');
            exit;
        }

        // vamos criar o array de parâmetros, limitando à quantidade permitida
        $ret = [];
        for ($i = 0; $i < $param_passed; $i++) {
            if ($i < $param_allowed) {
                $ret[] = $params[$i];
            }
        }
        return $ret;
    }

    protected static function formatarSaida($out)
    {
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
        exit;
    }

    public static function iniciar()
    {
        // vamos configurar o ambiente com valores padrão se necessário
        if (empty(getenv('USPDEV_WEBSERVICE_USER_FRIENDLY'))) {
            putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=' . SELF::user_friendly);
        }

        if (empty(getenv('USPDEV_WEBSERVICE_ADMIN_ROUTE'))) {
            putenv('USPDEV_WEBSERVICE_ADMIN_ROUTE=' . SELF::admin_route);
        }

        SELF::mapearFuncoes();
        Flight::start();
    }

    public static function listarMetodos($classe)
    {
        $metodos = get_class_methods($classe);

        foreach ($metodos as $m) {
            // para cada método vamos obter os parâmetros
            $r = new \ReflectionMethod($classe, $m);
            $params = $r->getParameters();

            // vamos listar somente os métodos publicos
            if ($r->isPublic()) {
                $p = '/';
                foreach ($params as $param) {
                    $o = $param->isOptional() ? '(opt)' : '';
                    $p .= '{' . $param->getName() . $o . '}/';
                }
                $p = substr($p, 0, -1);

                // vamos apresentar na forma de url
                //$api[$m] = getenv('DOMINIO') . '/' . $classe . '/' . $m . $p;
                $api[$m] = $m . $p;
            }
        }
        return $api;
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

            if (!is_array($data)) {
                $data = ['msg' => $data];
                // quebra galho para mensagem que nao é array
            }

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
            exit;
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
