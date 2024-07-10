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
        Html::addFunction(new TwigFunction('printArray', function (array $data) {
            return print_r($data);
        }));

        $this->request = Request::createFromGlobals();

        if ($this->getNickUser()) {
            $this->logRequest();
            $this->saveLogs();
        }
    }

    private function getNickUser(): ?string
    {
        $cookiesNick = $this->request->cookies->get('fsNick', '');
        return !empty($cookiesNick) ? (string)$cookiesNick : null;
    }

    private function logRequest(): array
    {
        return [
            'ip' => Session::getClientIp(),
            'nick' => $this->getNickUser(),
            'method' => $this->request->getMethod(),
            'action' => $this->request->request->get('action'),
            'uri' => $this->request->getUri(),
            'context' => json_encode($this->getRequestData()),
        ];
    }

    private function getRequestData(): array
    {
        return [
            'payload' => [
                'query' => $this->request->query->all(),
                'request' => $this->request->request->all(),
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
}
