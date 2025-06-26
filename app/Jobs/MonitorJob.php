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
        $config = [
            'settings' => [
                'displayErrorDetails' => true,
                'db' => [
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'user' => env('DB_USERNAME', 'root'),
                    'pass' => env('DB_PASSWORD', ''),
                    'dbname' => env('DB_DATABASE', 'laravel')
                ]
            ],
        ];

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

        $this->commonSlim->setSocketPort(env('SOCKET_PORT', '80'));
        $this->commonSlim->setSocketURL(env('SOCKET_URL', 'http://localhost:8000/graphql'));
        $this->commonSlim->setCONTA($data->INT_CTA);
        $this->commonSlim->setUSUARIO($data->INT_USU);
    }
}
