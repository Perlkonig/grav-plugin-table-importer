<?php
namespace Grav\Plugin;

require_once __DIR__ . '/classes/Reader.php';

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Yaml\Yaml;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use League\Csv\Reader;

/**
 * Class TableImporterPlugin
 * @package Grav\Plugin
 */
class TableImporterPlugin extends Plugin
{
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
            'onPageInitialized' => ['onPageInitialized', 0]
        ]);
    }

    public function onPageInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }
        $defaults = (array) $this->config->get('plugins.table-importer');
        /** @var Page $page */
        $page = $this->grav['page'];
        if (isset($page->header()->{'table-importer'})) {
            $this->config->set('plugins.table-importer', array_merge($defaults, $page->header()->{'table-importer'}));
        }
        if ($this->config->get('plugins.table-importer.active')) {
            $this->enable([
                'onPageContentRaw' => ['onPageContentRaw', 0],
            ]);
        }
    }

    public function onPageContentRaw(Event $e)
    {
        $config = $this->grav['config'];
        $page = $this->grav['page'];
        $locator = $this->grav['locator'];
        $markdown = $page->rawMarkdown();
        preg_match_all('/\[TableImporter\>(.+?)(\|(.+)){0,1}\]/i', $markdown, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $fullmatch = $match[0];
            $filename = $match[1];
            $options = $match[3];
            if (options !== null) {
                $options = str_replace(' ', '', $options);
                $options = explode(',', $options);
                $passed = [];
                foreach ($options as $option) {
                    $option = explode('=', $option);
                    $passed[$option[0]] = $option[1];
                }
                $options = $passed;
            }
            
            // Fetch the data
            $filepath = $config->get('plugins.table-importer.datadir');
            if ($filepath === null) {
                $filepath = '';
            }
            $filepath = DATA_DIR.$filepath.'/'.$filename;
            $exists = file_exists($filepath);
            $filecontents = null;
            if ($exists) {
                dump('Processing '.$filepath);
                $type = null;
                if (array_key_exists('type', $options)) {
                    $type = $options['type'];
                }
                if ($type === null) {
                    if (self::endsWith(strtolower($filepath), '.json')) {
                        $type = 'json';
                    } elseif ( (self::endsWith(strtolower($filepath), '.yaml')) || (self::endsWith(strtolower($filepath), '.yml')) ) {
                        $type = 'yaml';
                    } elseif (self::endsWith(strtolower($filepath), '.csv')) {
                        $type = 'csv';
                    }
                }
                if ($type === null) {
                    throw new \RuntimeException('TableImporter Plugin: Could not determine the file type. Either use a supported filename extension or pass a valid `type` option.');
                }
                // parse the contents based on file extension
                $fh = File::instance($filepath);
                if ($type === 'json') {
                    $content = $fh->content();
                    $filecontents = json_decode($content);
                } elseif ($type === 'yaml') {
                    $filecontents = Yaml::parse($fh->content());
                } elseif ($type === 'csv') {
                    $delimiter = $config->get('plugins.table-importer.csv_delimiter');
                    if (array_key_exists('delimiter', $options)) {
                        $delimiter = $options['delimiter'];
                    }
                    if ($delimiter === null) {
                        $delimiter = ',';
                    }
                    $enclosure = $config->get('plugins.table-importer.csv_enclosure');
                    if (array_key_exists('enclosure', $options)) {
                        $enclosure = $options['enclosure'];
                    }
                    if ($enclosure === null) {
                        $enclosure = '"';
                    }
                    $escape = $config->get('plugins.table-importer.csv_escape');
                    if (array_key_exists('escape', $options)) {
                        $escape = $options['escape'];
                    }
                    if ($escape === null) {
                        $escape = '\\';
                    }
                    dump("Delimter: $delimiter, Enclosure: $enclosure, Escape: $escape");
                    $filecontents = str_getcsv($fh->content(), $delimiter, $enclosure, $escape);
                } else {
                    throw new \RuntimeException('TableImporter Plugin: Only JSON, YAML, and CSV files are supported.');
                }
                $fh->free();
                if ($type === 'csv') {
                    dump($filecontents);
                }

                if ($filecontents !== null) {
                    // Now generate the table markdown
                    $toinsert = '';
                    $numcols = count($filecontents[0]);
                    if ( (array_key_exists('headers', $options)) && ($options['headers'] === 'false') ) {
                        for ($i=0; $i<$numcols; $i++) {
                            $toinsert .= '|   ';
                        }
                        $toinsert .= "|\n";
                        for ($i=0; $i<$numcols; $i++) {
                            $toinsert .= '| - ';
                        }
                        $toinsert .= "|\n";
                    } else {
                        $header = array_shift($filecontents);
                        foreach ((array) $header as $val) {
                            $toinsert .= '| '.$val.' ';
                        }
                        $toinsert .= "|\n";
                        foreach ((array) $header as $val) {
                            $toinsert .= '| --- ';
                        }
                        $toinsert .= "|\n";
                    }
                    foreach ((array) $filecontents as $row) {
                        foreach ((array) $row as $cell) {
                            $toinsert .= '| '.$cell.' ';
                        }
                        $toinsert .= "|\n";
                    }
                    if ($type === 'csv') {
                        dump($toinsert);
                    }
                }
            }
        }
    }

    private static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

}
