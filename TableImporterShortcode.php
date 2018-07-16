<?php

namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Grav\Common\Utils;
use Symfony\Component\Yaml\Yaml;
use RocketTheme\Toolbox\File\File;
// use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use League\Csv\Reader;

class TableImporterShortcode extends Shortcode
{
    protected $outerEscape = null;

    public function init()
    {
        $this->shortcode->getHandlers()->add('ti', array($this, 'process'));
    }

    public function process(ShortcodeInterface $sc) {
        $fn = $sc->getParameter('file', null);
        if ($fn === null) {
            $fn = $sc->getShortcodeText();
            $fn = str_replace('[ti=', '', $fn);
            $fn = str_replace('/]', '', $fn);
            $fn = trim($fn);
        }
        if ( ($fn === null) && ($fn === '') ) {
            return "<p>Table Importer: Malformed shortcode (<tt>".htmlspecialchars($sc->getShortcodeText())."</tt>).</p>";
        }
        $raw = $sc->getParameter('raw', null);
        if ($raw === null) {
            $raw = false;
        } else {
            $raw = true;
        }
        $type = $sc->getParameter('type', null);
        $delim = $sc->getParameter('delimiter', ',');
        $encl = $sc->getParameter('enclosure', '"');
        $esc = $sc->getParameter('escape', '\\');
        $class = $sc->getParameter('class', null);
        $caption = $sc->getParameter('caption', null);
        $header = $sc->getParameter('header', null);
        if ($header === null) {
            $header = true;
        } else {
            $header = false;
        }

        // Get absolute file name
        $abspath = null;
        if ($fn !== null) {
            $abspath = $this->getPath(static::sanitize($fn));
        }
        if ($abspath === null) {
            return "<p>Table Importer: Could not resolve file name '$fn'.</p>";
        }
        if (! file_exists($abspath)) {
            return "<p>Table Importer: Could not find the requested data file '$fn'.</p>";
        }

        // Determine what type of file it is
        if ($type === null) {
            if ( (Utils::endswith(strtolower($fn), '.yaml')) || ((Utils::endswith(strtolower($fn), '.yml'))) ) {
                $type = 'yaml';
            } elseif (Utils::endswith(strtolower($fn), '.json')) {
                $type = 'json';
            } elseif (Utils::endswith(strtolower($fn), '.csv')) {
                $type = 'csv';
            } else {
                return "<p>Table Importer: Could not determine the type of the requested data file '$fn'. This plugin only supports YAML, JSON, and CSV.</p>";
            }
        }

        // Load the data
        $data = null;
        switch ($type) {
            case 'yaml':
                $data = Yaml::parse(file_get_contents($abspath));
                break;
            
            case 'json':
                $data = json_decode(file_get_contents($abspath));
                break;

            case 'csv':
                $reader = Reader::createFromPath($abspath, 'r');
                $reader->setDelimiter($delim);
                $reader->setEnclosure($encl);
                $this->outerEscape = $esc;
                $reader->setEscape($esc);
                // This func is to compensate for a bug in PHP's `SplFileObject` class.
                // https://bugs.php.net/bug.php?id=55413
                // This func strips out the extraneous escape character.
                $func = function ($row) {
                    $e = preg_quote($this->outerEscape);
                    foreach ($row as &$cell) {
                        $cell = preg_replace("/$e(?!$e)/", '', $cell);
                    }
                    unset($cell);
                    return $row;
                };
                $data = $reader->fetchAll($func);
                break;
        }

        // Build the table
        if ($data === null) {
            return "<p>Table Importer: Something went wrong loading '$type' data from the requested file '$fn'.</p>";
        }
        $output = '';
        
        // Table's id can be specified by adding an `id` attribute to the shortcode
        $id = $sc->getParameter('id', null);
        if ($id === null)
          $id = $this->shortcode->getId($sc);
        
        $output .= '<table id="'.$id.'"';
        if ($class !== null) {
            $output .= ' class="'.htmlspecialchars($class).'"';
        }
        $output .= '>';

        // Insert caption if given
        if ( ($caption !== null) && (strlen($caption) > 0) ) {
            $output .= '<caption>'.htmlspecialchars($caption).'</caption>';
        }

        if ($header) {
            $row = array_shift($data);
            $output .= '<thead><tr>';
            foreach ($row as $cell) {
                $output .= '<th>'.$cell.'</th>';
            }
            $output .= '</tr></thead>';
        }

        $output .= '<tbody>';
        foreach ($data as $row) {
            $output .= '<tr>';
            foreach ($row as $cell) {
                if ($raw) {
                    $output .= '<td>'.$cell.'</td>';
                } else {
                    $output .= '<td>'.htmlspecialchars($cell).'</td>';
                }
            }
            $output .= '</tr>';
        }
        $output .= '</tbody>';

        $output .= '</table>';
        return $output;
    }

    private function getPath($fn) {
        if (Utils::startswith($fn, 'data:')) {
            $path = $this->grav['locator']->findResource('user://data', true);
            $fn = str_replace('data:', '', $fn);
        } else {
            $path = $this->grav['page']->path();
        }
        if ( (Utils::endswith($path, DS)) || (Utils::startswith($fn, DS)) ) {
            $path = $path . $fn;
        } else {
            $path = $path . DS . $fn;
        }
        if (file_exists($path)) {
            return $path;
        }
        return null;
    }

    private static function sanitize($fn) {
        $fn = trim($fn);
        $fn = str_replace('..', '', $fn);
        $fn = ltrim($fn, DS);
        $fn = str_replace(DS.DS, DS, $fn);
        return $fn;
    }
}
