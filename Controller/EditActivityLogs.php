<?php declare(strict_types=1);

namespace FacturaScripts\Plugins\ActivityLogs\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditActivityLogs extends EditController
{
    /** @return array<string, string> */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'activitylogs';
        $data['showonmenu'] = false;
        return $data;
    }

    public function getModelClassName(): string
    {
        return 'ActivityLogs';
    }

    protected function createViews(): void
    {
        $this->addHtmlView('EditActivityLogs', 'EditActivityLogs', 'ActivityLogs', 'activitylogs');
        $this->setSettings('EditActivityLogs', 'btnNew', false);
    }
}
