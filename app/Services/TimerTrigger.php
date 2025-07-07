<?php

namespace App\Services;

date_default_timezone_set('America/Sao_Paulo');
require $argv[7] . '/vendor/autoload.php';


use App\Dtos\AcaoDTO;
use App\Events\AcaoExecutadaEvent;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use PDO;
use Sh3\Bibliotecas\Util\Util;
use Sh3\Bibliotecas\Util\Common;
use Sh3\Bibliotecas\Monitor\Monitor;
use Slim\App;

/**
 * Classe TimerTrigger
 *
 * Responsável por inicializar e gerenciar o monitoramento do timer de fases de itens
 * dentro de um processo licitatório. Essa classe integra o sistema legado (Sh3) com o Laravel,
 * permitindo a execução de eventos em tempo real e o disparo de ações conforme o avanço das fases.
 *
 * ### Funcionalidades principais:
 * - Inicializa bibliotecas SH3 e configura o monitoramento de fases.
 * - Integra com o Laravel para disparar eventos (`AcaoExecutadaEvent`) ao detectar ações do monitor.
 * - Carrega as dependências Slim necessárias para comunicação com o sistema legado.
 * - Permite passar parâmetros externos via CLI para controle dinâmico do monitoramento.
 *
 * ### Parâmetros esperados:
 * - `INT_FASE_ITEM`: ID da fase do item a ser monitorado.
 * - `CNPJ_PREF`: CNPJ da prefeitura associada ao processo.
 * - `INT_CTA`: ID da conta do usuário autenticado.
 * - `INT_USU`: ID do usuário autenticado.
 * - `LG_EMPATE`: Flag para controle de empate.
 * - `LG_REIN_ITEM`: Flag para reinício do item.
 * - `PATH`: Caminho absoluto para a aplicação Laravel.
 *
 * ### Exemplo de uso via CLI:
 * ```bash
 * php TimerTrigger.php 123456789 12345678000199 1 42 true false /var/www/licitacao
 * ```
 *
 * @package App\Service
 * @author  Equipe LicitApp
 */
class TimerTrigger
{
    public mixed $commonSlim;
    public mixed $utilSlim;
    public mixed $monitor;
    public mixed $params;

    public function __construct($argv)
    {
        $this->params['INT_FASE_ITEM'] = $argv[1];
        $this->params['CNPJ_PREF'] = $argv[2];
        $this->params['INT_CTA'] = $argv[3];
        $this->params['INT_USU '] = $argv[4];
        $this->params['LG_EMPATE '] = $argv[5];
        $this->params['LG_REIN_ITEM'] = $argv[6];
        $this->params['PATH'] = $argv[7];
    }

    public function run(): void
    {
        $this->carregarBibliotecaSh3();

        // Definindo a closure
        $this->monitor->setTipoLogin(3);
        $this->monitor->setCNPJPREF($this->params['CNPJ_PREF']);
        $this->monitor->setCONTA($this->params['INT_CTA']);
        $this->monitor->setUSUARIO($this->params['INT_USU']);
        $this->monitor->setPath($this->params['path']);
        $this->monitor->setEmpate($this->params['LG_EMPATE']);
        $this->monitor->setReinicioItem($this->params['LG_REIN_ITEM']);

        $app = require_once $this->params['PATH'] . '/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        Facade::setFacadeApplication($app);

        $chamadaLaravel = function ($info) {
            $acaoDTO = new AcaoDTO([
                'id' => $info['INT_PROC'],
                'tipo' => $info['tipo'],
                'data' => json_encode($info['data'])
            ]);

            Event::dispatch(new AcaoExecutadaEvent($acaoDTO));
        };

        $this->monitor->monitorarFase($this->params['INT_FASE_ITEM'], $chamadaLaravel);
    }

    public function carregarBibliotecaSh3(): void
    {
        $config = [
            'settings' => [
                'displayErrorDetails' => true,
                'db' => [
                    'db' => [
                        'host' => config('database.connections.mariadb.host', '127.0.0.1'),
                        'user' => config('database.connections.mariadb.username', 'root'),
                        'pass' => config('database.connections.mariadb.password', ''),
                        'dbname' => config('database.connections.mariadb.database', 'laravel')
                    ]
                ]
            ],
        ];

        $appSlim = new App($config);
        $container = $appSlim->getContainer();

        $container['db'] = function ($c) {
            $db = $c['settings']['db'];

            $pdo = new PDO(
                'mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'] . ';charset=utf8', $db['user'], $db['pass']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $pdo;
        };

        $this->utilSlim = new Util($container);
        $this->commonSlim = new Common($this->utilSlim);
        $this->monitor = new Monitor($this->commonSlim);

        $servidor = $this->utilSlim->getServidor($this->params['CNPJ_PREF']);
        $_SERVER["SERVER_NAME"] = $servidor;
        $serverInfo = $this->commonSlim->obterDadosServidorSocket(true);

        $this->commonSlim->setSocketPort($serverInfo->SOCKETPORT);
        $this->commonSlim->setSocketURL($serverInfo->SOCKETURL);
        $this->commonSlim->setCONTA($this->params['INT_CTA']);
        $this->commonSlim->setUSUARIO($this->params['INT_USU']);
    }
}

$timerTrigger = new TimerTrigger($argv);
$timerTrigger->run();
