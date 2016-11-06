# Table Importer Plugin

The **Table Importer** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It imports tables from JSON, YAML, and CSV formats into a page.

For a demo, [visit my blog](https://perlkonig.com/demos/table-importer).

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
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error), [Problems](https://github.com/getgrav/grav-plugin-problems) and [Shortcode Core](https://github.com/getgrav/grav-plugin-shortcode-core) plugins to operate.

## Configuration

To edit the configuratino, first copy `table-importer.yaml` from the `user/plugins/table-importer` folder to your `user/config/plugins` folder and only edit that copy. 

The only configuration setting is `enabled`, which turns the plugin off and on.

This plugin extends the [Shortcode Core](https://github.com/getgrav/grav-plugin-shortcode-core) infrastructure. See that documentation to learn how to disable/enable shortcode processing on a page-by-page basis.

## Usage

This plugin converts JSON, YAML, and CSV files into HTML code and can be used in conjunction with other table plugins like [Tablesorter](https://github.com/Perlkonig/grav-plugin-tablesorter). It only works with simple, even data (see the next section for details). If you wish to accomplish something more complex, then consider combining [the Import plugin](https://github.com/Deester4x4jr/grav-plugin-import) with some custom twig code.

### Formatting Your Data

The plugin is naive and assumes that your data is well formed and that your tables have even row lengths (the table is rectangular). If the plugin can't find the data file or can't understand its format, then the shortcode will be replaced by an error message. Otherwise, the only evidence something is wrong will be the shortcode still showing or weird table rendering.

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

This plugin uses the [Shortcode Core](https://github.com/getgrav/grav-plugin-shortcode-core) infrastructure. Read those docs for the nitty gritty of how shortcodes work.

The Table Importer shortcode is a self-closing `[ti option1="value1" option2="value2" ... /]`, and it accepts the following options:

* `file` is the only required parameter. It points to the datafile you wish to load. By default, the plugin looks in the same folder as the page file. This is adequate for most usage. You can also load files from the `user/data` folder by prefixing your file name with `data:` (e.g., `file=data:tables/mytable.yaml`). 

  If all you're passing is the file name, then you can shorten the code to the form `[ti=mytable.yaml/]`.

* `type` is usually unnecessary. It tells the plugin what format the data file is in. The only acceptable values are `yaml`, `json`, and `csv`. However, the plugin looks at the file name extension first. If it's `yaml`, `yml`, `json`, or `csv`, then there's no need to use the `type` option. 

* `header` tells the plugin whether you want a header row or not. By default, the first row is rendered as a header. Passing *any* value to `header` will disable the header row.

* `class` lets you assign class definitions to the table itself. Whatever you put here will be escaped (via PHP's `htmlspecialchars`) and placed into the opening `<table>` tag.

* By default, the content of each cell is escaped using PHP's `htmlspecialchars` function. If the `raw` option is set to anything at all, the escaping will be disabled. **Only do this if you trust the incoming data!**

* Finally, for CSV files only, you can customize how it will be parsed using any of the following three options:

  * `delimiter` defines how columns are separated. By default, the value is a comma (`,`).

  * `enclosure` defines how cells with special characters are contained. By default, the value is a double quotation mark (`"`).

  * `escape` defines how special characters can be escaped. By default, the value is a backslash (`\`).

### Example Codes

* `[ti=test.json]` (basic import of json table in the same folder as the page itself)

* `[ti=data:test.yaml]` (basic import of yaml table in the `user/data` folder)

* `[ti file=json-as-yaml.json type=yaml]` (parse a file as yaml regardless of extension)

* `[ti file=file.csv enclosure=']` (parse a CSV file that uses a single quote to enclose items)

* `[ti file=file.yaml header="false" class="imported"]` (basic yaml table with no header and a class of `imported`)

## Credits

Because PHP's builtin CSV support is...let's just say inelegant, this plugin incorporates the most excellent [PHPLeague CSV library](http://csv.thephpleague.com/).
