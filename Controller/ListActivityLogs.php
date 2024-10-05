<?php declare(strict_types=1);

namespace FacturaScripts\Plugins\ActivityLogs\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

class ListActivityLogs extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'activitylogs';
        $pageData['icon'] = 'fas fa-archive';
        return $pageData;
    }

    protected function createViews(): void
    {
        $this->addView('ListActivityLogs', 'ActivityLogs', 'activitylogs', 'fas fa-archive')
            ->addFilterAutocomplete('ListActivityLogs', 'nick', 'user', 'nick', 'activitylogs')
            ->addFilterAutocomplete('ListActivityLogs', 'ip', 'ip', 'ip', 'activitylogs')
            ->addFilterPeriod('fecha', 'period', 'fecha', true);

        $this->setSettings('ListActivityLogs', 'btnNew', false);
        $this->setSettings('ListActivityLogs', 'btnDelete', false);
    }
}
