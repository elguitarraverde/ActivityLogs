<?php declare(strict_types=1);

namespace FacturaScripts\Plugins\ActivityLogs;

use Exception;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Base\InitClass;
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
    }

    private function getNickUser(): ?string
    {
        $cookiesNick = $this->request->cookies->get('fsNick', '');
        return !empty($cookiesNick) ? (string)$cookiesNick : null;
    }

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
        if (!empty($urlsSettings)){
            $urls = array_merge($urlsSettings, $urls);
        }

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
     * @param array $params
     *
     * @return array
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
}
