<?php declare(strict_types=1);

namespace FacturaScripts\Plugins\ActivityLogs\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

class ActivityLogs extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $ip;

    /** @var string */
    public $nick;

    /** @var string */
    public $method;

    /** @var string */
    public $action;

    /** @var string */
    public $uri;

    /** @var string */
    public $context;

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'activitylogs';
    }

    public function test(): bool
    {
        $this->ip = Tools::noHtml($this->ip);
        $this->nick = Tools::noHtml($this->nick);
        $this->method = Tools::noHtml($this->method);
        $this->action = Tools::noHtml($this->action);
        $this->uri = Tools::noHtml($this->uri);

        return parent::test();
    }

    public function context()
    {
        return json_decode(Tools::fixHtml($this->context), true);
    }
}
