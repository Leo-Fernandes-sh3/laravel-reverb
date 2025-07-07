<?php
namespace App\Service;

date_default_timezone_set('America/Sao_Paulo'); //importante nao apagar

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
       
        // $this->params['intFaseItem'] = $argv[1];    
        // $this->params['cnpjPref']     = $argv[2];    
        // $this->params['intCta']       = $argv[3];    
        // $this->params['intUsu ']      = $argv[4];    
        // $this->params['lgEmpate ']    = $argv[5];
        // $this->params['lgReinItem']  = $argv[6];
        // $this->params['path']          = $argv[7];
    }

    public function run($argv){

        $this->carregarBibliotecaSh3($argv);

          // Definindo a closure

          $intFaseItem  = $argv[1];    
          $cnpjPref     = $argv[2];    
          $intCta       = $argv[3];    
          $intUsu       = $argv[4];    
          $lgEmpate     = $argv[5];
          $lgReinItem   = $argv[6];
          $path         = $argv[7];
          
          $this->Monitor->setTipoLogin(3);
          $this->Monitor->setCNPJPREF($cnpjPref);
          $this->Monitor->setCONTA($intCta);
          $this->Monitor->setUSUARIO($intUsu);
          $this->Monitor->setpath($path);
          $this->Monitor->setEmpate($lgEmpate);
          $this->Monitor->setReinicioItem($lgReinItem);
          //Monitor->stdclass->lgEmpate = $lgEmpate;
          
          $app = require_once $path .'/bootstrap/app.php';
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

          $this->Monitor->monitorarFase($intFaseItem,$chamadaLaravel);
        }
        
        
        public function carregarBibliotecaSh3($argv): void{

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


        $cnpjPref = $argv[2]; 

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

        $STR_SERV = $this->utilSlim->getServidor($cnpjPref);

        $_SERVER["SERVER_NAME"] = $STR_SERV;

        $serverInfo = $this->commonSlim->obterDadosServidorSocket(true);

        $this->commonSlim->setSocketPort($serverInfo->SOCKETPORT);
        $this->commonSlim->setSocketURL( $serverInfo->SOCKETURL);
      //  $this->commonSlim->setCONTA($this->params['intCta']);
       // $this->commonSlim->setUSUARIO($this->params['intUsu']);
    }
}

$timerTrigger = new TimerTrigger($argv);
$timerTrigger->run($argv);
