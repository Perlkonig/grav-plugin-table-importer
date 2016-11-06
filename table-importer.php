<?php
namespace Grav\Plugin;

require_once __DIR__ . '/vendor/csv/autoload.php';

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class TableImporterPlugin
 * @package Grav\Plugin
 */
class TableImporterPlugin extends Plugin
{

    protected $outerEscape = null;

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        // Enable the main event we are interested in
        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0]
        ]);
    }

    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerShortcode('TableImporterShortcode.php', __DIR__);
    }
}
