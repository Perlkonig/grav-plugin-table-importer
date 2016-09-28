# Table Importer Plugin

The **Table Importer** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It imports tables from JSON, YAML, and CSV formats into Markdown tables within a page.

## Installation

Installing the Table Importer plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install table-importer

This will install the Table Importer plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/table-importer`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `table-importer`. You can find these files on [GitHub](https://github.com/Perlkonig/grav-plugin-table-importer) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/table-importer
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

Here's the default configuration. To override, first copy `table-importer.yaml` from the `user/plugins/table-importer` folder to your `user/config/plugins` folder and only edit that copy. All of these parameters (except `enabled`) can be overridden on a page-by-page basis in the page header.

```yaml
enabled: true
active: false
datadir: tables    #relative to `user/data` folder
csv_delimiter: ','
csv_enclosure: '"'
csv_escape: '\'
```

* `enabled` lets you turn the plugin off and on globally.

* The `active` field is usually set to false globally (to save processing time) and then enabled in a page header when needed:

  ```yaml
  table-importer:
    active: true
  ```

  If you want to activate the plugin by taxonomy, take a look at the [Header by Taxonomy plugin](https://github.com/Perlkonig/grav-plugin-header-by-taxonomy).

* `datadir` is where your tables are stored. It is relative to your `user/data` folder and should contain no leading or trailing slashes. The path is sanitized so that the only possible root is `user/data`. This is to avoid someone from creating a page that dumps arbitrary files.

  When you call the plugin (see the "Usage" section below), you can specify a path as well. So you could conceivably leave this blank and pull from tables anywhere in the `user/data` folder.

* The fields `csv_delimiter`, `csv_enclosure`, and `csv_escape` let you modify how the plugin parses your CSV files. The hard-coded defaults are `,`, `"`, and `\`, respectively.

## Usage

This plugin converts JSON, YAML, and CSV files into Markdown tables. **It does *not* generate HTML code itself!** This makes it easy to use in conjunction with other table plugins like [Tablesorter](https://github.com/Perlkonig/grav-plugin-tablesorter).

If you wish to accomplish something more complex, then consider combining [the Import plugin](https://github.com/Deester4x4jr/grav-plugin-import) with some custom twig code.

### Formatting Your Data

The plugin is naive and assumes that your data is well formed and that your tables have even row lengths (the table is rectangular). The only exceptions this plugin throws are if you pass it a file whose type it can't recognize or if one of the parsers explodes. Otherwise, the only evidence something is wrong will be the shortcode still showing or weird table rendering.

The only requirement is that your data file parse to a two-dimensional array: an array of rows from top to bottom, each row containing cells from left to right. Each cell must be a value PHP can natively render as a string.

Some samples—json, then yaml, then CSV:

```json
[
  ["Col1", "Col2", "Col3"],
  ["Val1", "Val2", "Val3"],
  ["Val4", "Val5", "Val6"],
  ["Val7", "Val8", "Val9"]
]
```

```yaml
-
  - Col1
  - Col2
  - Col3
-
  - Val1
  - Val2
  - Val3
-
  - Val4
  - Val5
  - Val6
-
  - Val7
  - Val8
  - Val9
```

```csv
Col1,Col2,Col3
Val1,Val2,Val3
Val4,Val5,Val6
Val7,Val8,Val9
```

### Inserting a Table

Tables are inserted using a special shortcode of the following structure:

```
[TableImporter>{optional/path/mytable.whatever}|{OPTIONS}]
```

* The code starts with `[TableImporter>`. This is case insensitive. `[TaBlEiMpOrTeR>` works just as well.

* What follows is the name of the file, preceded optionally with a path relative to the `datadir` specified in the config file. This is sanitized to prevent arbitrary file access. No matter what, the absolute path of the requested file should be under the `user/data` folder.

* Separate the file from the options with a pipe symbol (`|`).

* The options should be separated by commas and be in the form `{KEY}={VALUE}`.

  * The plugin looks at the file extension to determine the type of data it contains (`.json`, `.yaml`, `.yml`, `.csv`). You can force the plugin to use a specific parser by including an option called `type` and pass it one of the following: `json`, `yaml`, `csv`.

  * If you want to customize the CSV parser, you can pass any of the following options followed by the single characters: `delimiter`, `enclosure`, `escape`. The only restriction is that you can't use a comma as input, but that's usually fine because the comma is the default `delimiter` anyway.

  * To right align columns, add the option `right` and pass a string of column numbers (with the leftmost column being `1`) separated by a forward slash (e.g., `1/2`).

* The code ends with the closing bracket (`]`).

### Example Codes

* `[TableImporter>test.json]` (basic import of json table)

* `[tableimporter>my/path/json-as-yaml.json|type=yaml]` (parse a file as yaml regardless of extension)

* `[TABLEIMPORTER>file.csv|enclosure=']` (parse a CSV file that uses a single quote to enclose items)

* `[TableImporter>test.yaml|right=3/4]` (basic yaml table with columns 3 and 4 right aligned)

## Credits

Because PHP's builtin CSV support is...let's just say inelegant, this plugin incorporates the most excellent [PHPLeague CSV library](http://csv.thephpleague.com/).
