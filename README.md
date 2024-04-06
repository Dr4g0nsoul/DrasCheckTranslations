# Shopware 6 Plugin: Check for missing translations in snippets
- Adds a new symfony command which scans through varios Snippets in the Storefront (both .json snippets and database snippets).
- Adds a new symfony command which scans through varios Entity Translations (custom fields are not yet implemented).

## Installation:
- Upload the plugin as .zip file.
- Install and activate the plugin

## Usage Snippet Check:
```
bin/console dras:missing-snippet-translations [options]
```
### Options:
- -m, --max-charcters=MAX-CHARCTERS  Truncate text after n characters and add a '...' ellipsis [default: 0]
- -h, --help                         Display help for the given command. When no command is given display help for the list command

### Example:
```
bin/console dras:missing-snippet-translations -m 30
```

## Usage Entity Translations Check:
```
bin/console dras:missing-snippet-translations [options]
```
### Options:
- -m, --max-charcters=MAX-CHARCTERS     Truncate text after n characters and add a '...' ellipsis [default: 0]
- -i, --include-tables[=INCLUDE-TABLES] Array of table names to include. (Use DB Table names, '_translation' can be omitted) (multiple values allowed)
- -h, --help                            Display help for the given command. When no command is given display help for the list command

### Example:
```
bin/console dras:missing-entity-translations -m 30 -i product -i category
```

## Features that eventually, maybe, perhaps will be added later on
- Include administration snippets. Not only storefront snippets.
- Administration module to show missing snippets (might be done by adding a filter in the snippet set view)
- Edit missing snippets directly in administration module (superfluos if done with filter)
- API endpoit to check for missing snippets (e.g. for usage in a monitoring tool)
