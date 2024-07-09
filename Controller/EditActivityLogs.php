<?php declare(strict_types=1);

namespace FacturaScripts\Plugins\ActivityLogs\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditActivityLogs extends EditController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'activity-logs';
        $data['showonmenu'] = false;
        return $data;
    }

    public function getModelClassName(): string
    {
        return 'ActivityLogs';
    }

    protected function createViews()
    {
        $this->addHtmlView('EditActivityLogs', 'EditActivityLogs', 'ActivityLogs', 'activity-logs');
        $this->setSettings('EditActivityLogs', 'btnNew', false);
    }
}
