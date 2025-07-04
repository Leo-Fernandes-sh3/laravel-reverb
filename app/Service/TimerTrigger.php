<?php
namespace App\Service;

date_default_timezone_set('America/Sao_Paulo'); //importante nao apagar

$texto = 'timer triiger path '.$argv[7]. PHP_EOL; //quebra de linha

\file_put_contents($argv[7].'/public/Saida.txt',$texto,FILE_APPEND);

require $argv[7].'/vendor/autoload.php';

$texto = 'required ok '. PHP_EOL; //quebra de linha

\file_put_contents($argv[7].'/public/Saida.txt',$texto,FILE_APPEND);

use PDO;
use Sh3\Bibliotecas\Util\Util;
use Sh3\Bibliotecas\Util\Common;
use Sh3\Bibliotecas\Monitor\Monitor;
use Slim\App;

class TimerTrigger
{
   
    public mixed $commonSlim;
    public mixed $utilSlim;
    public mixed $Monitor;
    public mixed $params;

    public function __construct($argv){
       
        $this->params['INT_FASE_ITEM'] = $argv[1];    
        $this->params['CNPJ_PREF']     = $argv[2];    
        $this->params['INT_CTA']       = $argv[3];    
        $this->params['INT_USU ']      = $argv[4];    
        $this->params['LG_EMPATE ']    = $argv[5];
        $this->params['LG_REIN_ITEM']  = $argv[6];
        $this->params['PATH']          = $argv[7];
    }

    public function run(){

        $this->carregarBibliotecaSh3();

          // Definindo a closure
          
          $this->Monitor->setTipoLogin(3);
          $this->Monitor->setCNPJPREF($this->params['CNPJ_PREF']);
          $this->Monitor->setCONTA($this->params['INT_CTA']);
          $this->Monitor->setUSUARIO($this->params['INT_USU']);
          $this->Monitor->setPath($this->params['path']);
          $this->Monitor->setEmpate($this->params['LG_EMPATE']);
          $this->Monitor->setReinicioItem($this->params['LG_REIN_ITEM']);
          //Monitor->stdclass->LG_EMPATE = $LG_EMPATE;
          
          $app = require_once '/home/desenv_web/desenv/laravel-reverb/bootstrap/app.php';
          $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

          \Illuminate\Support\Facades\Facade::setFacadeApplication($app);

          $chamadaLaravel = function ($info) {
  
              $acaoDTO = new \App\Dtos\AcaoDTO([
                  'id'   => $info['INT_PROC'],
                  'tipo' => $info['tipo'],
                  'data' => \json_encode($info['data'])  
              ]);
  
              \Illuminate\Support\Facades\Event::dispatch(new \App\Events\AcaoExecutadaEvent($acaoDTO));
          };

          $this->Monitor->monitorarFase($this->params['INT_FASE_ITEM'],$chamadaLaravel);
        }
        
        
        public function carregarBibliotecaSh3(): void{

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
        $this->Monitor = new Monitor($this->commonSlim);

        $STR_SERV = $this->utilSlim->getServidor($this->params['CNPJ_PREF']);

        $texto = 'server  '.$STR_SERV. PHP_EOL; //quebra de linha

        \file_put_contents($this->params['PATH'].'/public/Saida.txt',$texto,FILE_APPEND);

        $_SERVER["SERVER_NAME"] = $STR_SERV;

        $serverInfo = $this->commonSlim->obterDadosServidorSocket(true);

        $this->commonSlim->setSocketPort($serverInfo->SOCKETPORT);
        $this->commonSlim->setSocketURL( $serverInfo->SOCKETURL);
        $this->commonSlim->setCONTA($this->params['INT_CTA']);
        $this->commonSlim->setUSUARIO($this->params['INT_USU']);
    }
}

$timerTrigger = new TimerTrigger($argv);
$timerTrigger->run();
