<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PDO;
use Sh3\Bibliotecas\Util\Util;
use Sh3\Bibliotecas\Util\Common;
use Sh3\Bibliotecas\Monitor\Monitor;
use Slim\App;

class MonitorJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 0;
    public mixed $commonSlim;
    public mixed $utilSlim;
    public mixed $monitorSlim;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $args)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = json_decode($this->args['data']);
        $intFaseItem = (int)$data->INT_FASE_ITEM;

        $this->carregarBibliotecaSh3($data);
        $this->monitorSlim->monitorarFase($intFaseItem);
    }

    public function carregarBibliotecaSh3($data): void
    {
        $config = $this->getDataBaseConfig();
        $appSlim = new App($config);
        $container = $appSlim->getContainer();

        $container['db'] = function ($c) {
            $db = $c['settings']['db'];

            $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'] . ';charset=utf8', $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $pdo;
        };

        $this->utilSlim = new Util($container);
        $this->commonSlim = new Common($this->utilSlim);
        $this->monitorSlim = new Monitor($this->commonSlim);

        $this->commonSlim->setSocketPort(config('reverb.servers.reverb.port', '80'));
        $this->commonSlim->setSocketURL(config('reverb.servers.reverb.host', '0.0.0.0'));
        $this->commonSlim->setCONTA($data->INT_CTA);
        $this->commonSlim->setUSUARIO($data->INT_USU);
    }

    /**
     * Obtém as configurações de banco de dados definidas no arquivo de configuração.
     * @return array[]
     */
    protected function getDataBaseConfig(): array
    {
        return [
            'settings' => [
                'displayErrorDetails' => true,
                'db' => [
                    'host' => config('database.connections.mariadb.host', '127.0.0.1'),
                    'user' => config('database.connections.mariadb.username', 'root'),
                    'pass' => config('database.connections.mariadb.password', ''),
                    'dbname' => config('database.connections.mariadb.database', 'laravel')
                ]
            ],
        ];
    }
}
