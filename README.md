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

Como o objetivo é criar webservices para serem consumidas por outros sistemas ele não se comporta bem com pessoas e navegadores. Se for o caso, pode torná-lo mais amigável com a variável abaixo. Com isso, ele irá solicitar credenciais sempre que as fornecidas não forem aceitas. O padrão é desabilitado.

    putenv('USPDEV_WEBSERVICE_USER_FRIENDLY=1');

Em ambiente de testes você pode não querer usar cache. Para isso desative o cache. Ele está ativado por padrão.

    putenv('USPDEV_CACHE_DISABLE=1');

Em geral uma api é limitada a um escopo local então faz sentido querer limitar por endereços IP. Em tese podem ser usados ipv6 mas foi testado com ipv4. para limitar a endereços específicos use a variável abaixo. Para saber como configurar o arquivo contendo os ips liberados consulte https://github.com/uspdev/ip-control

    putenv('USPDEV_IP_CONTROL=whitelist');

## Controle de acesso

O controle de acesso é por meio de usuario/senha usando http basic. Por isso é importante usar um HTTPS.

Os usuários podem ser:

* admin, que tem acesso à tudo, inclusive a interface de admin, se estiver ativada
* usuario, que possui uma lista de rotas autorizadas para acesso. Se o usuário estiver autorizado para *, poderá ter acesso a todas as rotas do sistema, menos a interface de admin.

Alguns exemplos de como gerenciar os usuários. Por enquanto não há uma interface mais amigável para isso.

```php
use Uspdev\Webservice\Auth;

Auth::salvarUsuario(['username'=>'admin', 'pwd'=>'admin', 'admin'=>'1', 'allow'=>'']);
Auth::salvarUsuario(['username'=>'gerente', 'pwd'=>'gerente', 'admin'=>'0', 'allow'=>'*']);
Auth::salvarUsuario(['username'=>'user1', 'pwd'=>'user', 'admin'=>'', 'allow'=>'minhaclasse1']);
Auth::salvarUsuario(['username'=>'user1', 'allow'=>'minhaclasse1, minhaclasse2']); // alterando o usuário user1
Auth::removerUsuario(['username'=>'gerente']);
Auth::listarUsuarios();
```

## Skeleton

Na pasta skeleton/ tem uma estrutura de pastas que pode ser usada como exemplo. Se quiser copie o seu conteúdo para a raiz do seu projeto.

A pasta public/ contém um index.php e um .htaccess para utilizar com o apache.

A pasta local/ é onde ficarão os arquivos gerados pelo webservice e deve estar no .gitignore da aplicação. Por enquanto é apenas o arquivo de senhas de acesso mas pode ser colocado o arquivo com os ips liberados.

A pasta src/ é onde você colocará suas classes.

Se você criar uma classe \Minha\ClasseDeDados para prover os dados ao webservice, você deve configurar a variável $controllers da seguinte forma:

    $controllers['classededados'] = '\Minha\ClasseDeDados';

Com isso os métodos públicos dessa classe ficarão disponíveis para consumo mais ou menos assim:

    http://servidor/dir_base/classededados/<metodos>/<parametros>

Os métodos devem ser estáticos e devem retornar um array ou uma string que serão formatados como json.

Você pode solicitar os seguintes formatos de saída:

* ?f=json - json formatado para humanos lerem
* ?f=csv - csv adequado para excel

Se não for passado parâmetro o json de saída será sem formatação adequado para outros sistemas ou para parsers de json.

Veja que $controllers é um array então você pode definir quantas fontes de dados quiser.

## Testes

Você pode rodar uns testes via shell em teste/teste.sh

Ele irá subir um servidor embutido do PHP e fará algumas consultas. 

