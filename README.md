# Webservice

Biblioteca que permite criar um webservice básico com controle de usuário e controle por IPs.

Não foi pensado para grandes quantidades de usuários pois o gerenciamento é simplificado.

A biblioteca contém as estruturas básicas para começar um webservice, incluindo as rotas, interface de administração.

## Dependências

* PHP 7.0 ou superior
* uspdev/cache
* uspdev/ip-control

Para o cache funcionar você precisa ter instalado o memcached.

## Instalação e configuração

Instale via composer
    
    composer require uspdev/webservice

Configure a pasta onde ficará o arquivo de banco de dados. Sim, ele usa sqlite.

    putenv('USPDEV_WEBSERVICE_LOCAL=' . __DIR__ . '/../local');

Como o objetivo é criar webservices para serem consumidas por outros sistemas ele não se comporta bem com pessoas e navegadores. Se for o caso, pode torná-lo mais amigável com a variável abaixo.Com isso, ele irá solicitar credenciais sempre que as fornecidas não forem aceitas. O padrão é desabilitado.

    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=1');

Em ambiente de testes você pode não querer usar cache. Para isso desative o cache. Ele está ativado por padrão.

    putenv('USPDEV_CACHE_DISABLE=1');

Em geral uma api é limita da a um escopo local então faz sentido querer limitar por endereços IP. Em tese podem ser usados ipv6 mas foi testado com ipv4. para limitar a endereços específicos use a variável abaixo. Para saber como configurar o arquivo contendo os ips liberados consulte https://github.com/uspdev/ip-control

    putenv('USPDEV_IP_CONTROL=whitelist');

Na pasta skeleton/ tem uma estrutura de pastas que pode ser usada como exemplo. 

A pasta public/ contém um index.php e um .htaccess para utilizar com o apache. 

A pasta local/ é onde ficarão os arquivos gerados pelo webservice e deve estar no .gitignore da aplicação.

A pasta src/ é onde você colocará suas classes.

Se você criar uma classe \Minha\ClasseDeDados para prover os dados ao webservice, você deve configurar a variável $controllers da seguinte forma:

    $controllers['classededados'] = '\Minha\ClasseDeDados'; //'Uspdev\Evasao\Evasao';

Com isso os métodos públicos dessa classe ficarão disponíveis para consumo.

Os métodos devem ser estáticos e devem retornar um array ou uma string.

Veja que $controllers é um array então você pode definir quantas fontes de dados que quiser.

## Testes

Você pode rodar uns testes via shell em teste/teste.sh

Ele irá subir um servidor embutido do PHP e fará algumas consultas. 

