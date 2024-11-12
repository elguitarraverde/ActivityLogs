<?php declare(strict_types=1);

namespace FacturaScripts\Plugins\ActivityLogs;

use DateMalformedStringException;
use DateTime;
use Exception;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\ActivityLogs\Model\ActivityLogs;
use Symfony\Component\HttpFoundation\Request;
use Twig\TwigFunction;

class Init extends InitClass
{
    /** @var Request */
    private $request;

    public function init(): void
    {
        $this->request = Request::createFromGlobals();

        if(false === $this->urlExcluded()){
            if ($this->getNickUser()) {
                $this->logRequest();
                $this->saveLogs();
            }
        }

        Html::addFunction(new TwigFunction('printArray', function (array $data) {
            return print_r($data, true);
        }));

        if (Tools::settings('activitylogs', 'guardarenarchivo', false)){
            $this->guardarEnArchivo();
        }
    }

    private function getNickUser(): ?string
    {
        // si se está haciendo login devolvemos el usuario del POST en lugar de la cookie
        if($this->request->request->has('fsNick') && $this->request->request->get('action') == 'login'){
            return $this->request->request->get('fsNick');
        }

        $cookiesNick = $this->request->cookies->get('fsNick', '');
        return !empty($cookiesNick) ? (string)$cookiesNick : null;
    }

    /** @return array<string, mixed> */
    private function logRequest(): array
    {
        return [
            'fecha' => Tools::dateTime(),
            'ip' => Session::getClientIp(),
            'nick' => $this->getNickUser(),
            'method' => $this->request->getMethod(),
            'action' => $this->request->request->get('action', $this->request->query->get('action', '')),
            'uri' => $this->request->getUri(),
            'context' => json_encode($this->getRequestData()),
        ];
    }

    /** @return array<string, mixed> */
    private function getRequestData(): array
    {
        return [
            'payload' => [
                'query' => $this->limpiarPasswords($this->request->query->all()),
                'request' => $this->limpiarPasswords($this->request->request->all()),
            ],
        ];
    }

    private function saveLogs(): void
    {
        try {
            $log = new ActivityLogs($this->logRequest());
            $log->save();
        } catch (Exception $e) {
            Tools::log()->error('record-save-error');
        }
    }

    public function update(): void
    {
        //
    }

    public function uninstall(): void
    {
        //
    }

    private function urlExcluded(): bool
    {
        // excluimos las llamadas por ajax
        if($this->request->isXmlHttpRequest()){
            return true;
        }

        // excluimos por defecto estas urls
        $urls = ['ListActivityLogs', 'Notification'];

        // añadimos las urls a excluir del usuario
        $urlsSettings = explode(',', Tools::settings('activitylogs', 'excludedurls', ''));
        $urls = array_merge($urlsSettings, $urls);

        foreach ($urls as $url){
            if (stripos($this->request->getRequestUri(), trim($url))){
                return true;
            }
        }

        return false;
    }


    /**
     * Evitamos guardar los passwords en el log
     *
     * @param array<string, string> $params
     *
     * @return array<string, string>
     */
    private function limpiarPasswords(array $params): array
    {
        foreach ($params as $key => $value) {
            if(stripos($key, 'password')){
                unset($params[$key]);
            }
        }

        return $params;
    }

    /** @throws DateMalformedStringException */
    private function guardarEnArchivo(): void
    {
        // si no existe la fecha de ultima comprobacion, la creamos
        if(!Cache::get('activity-logs-last-save-to-file')){
            Cache::set('activity-logs-last-save-to-file', date('Y-m-d'));
        }

        // Obtener la fecha actual
        $now = new DateTime();

        // Crear un objeto DateTime con la fecha de la última comprobación
        $lastCheck = Cache::get('activity-logs-last-save-to-file') ?? 'now';
        $lastCheckDate = new DateTime($lastCheck);

        // Calcular la diferencia entre las dos fechas
        $interval = $now->diff($lastCheckDate);

        // Verificar si ha pasado más de un día
        if ($interval->days > 0) {
            $activityLogs = ActivityLogs::all();
            $logsAgrupados = [];
            foreach ($activityLogs as $log) {
                // si la fecha no es correcta, asignamos una
                // esto lo hacemos porque inicialmente el plugin no registraba las fechas
                // y al añadir el campo fecha, los logs antiguos estan sin fecha
                if(empty($log->fecha) || false === strtotime($log->fecha)){
                    $log->fecha = '2024-01-01';
                    $log->save();
                }

                if (strtotime($log->fecha)){
                    $logsAgrupados[date('Y-m-d', strtotime($log->fecha))][] = $log;
                }
            }

            // excluimos los logs de hoy
            unset($logsAgrupados[date('Y-m-d')]);

            // preparamos directorio
            $folderPath = Tools::folder('MyFiles', 'activity-logs');
            Tools::folderCheckOrCreate($folderPath);

            // Guardamos en disco y eliminamos de la base de datos.
            foreach ($logsAgrupados as $fecha => $logs) {
                $path = $folderPath . DIRECTORY_SEPARATOR . $fecha . '_activity_logs.json';
                if(false === file_put_contents($path, json_encode($logs, JSON_PRETTY_PRINT))){
                    continue;
                }

                // si se ha guardado en el archivo correctamente, entonces los eliminamos de la base de datos
                foreach ($logs as $log) {
                    /** @var ActivityLogs $log */
                    $log->delete();
                }

                // Actualizamos fecha ultima comprobación.
                Cache::set('activity-logs-last-save-to-file', date('Y-m-d'));
            }
        }
    }
}
