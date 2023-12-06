# Shopware 6 Plugin: Check for missing translations in snippets
Adds a new symfony command which scans through varios Snippets in the Storefront (both .json snippets and database snippets).

## Installation:
- Upload the plugin as .zip file.
- Install and activate the plugin

## Usage:
```
bin/console dras:missing-translations [options]
```
### Options:
- -l, --base-locale=BASE-LOCALE      REQUIRED: Language the Shop is based on (Contains all Snippet Keys)
- -m, --max-charcters=MAX-CHARCTERS  Truncate text after n characters and add a '...' ellipsis [default: 0]
- -h, --help                         Display help for the given command. When no command is given display help for the list command

## Features that eventually, maybe, perhaps will be added later on
- Include administration snippets. Not only storefront snippets.
- Administration module to show missing snippets
- Edit missing snippets directly in administration module
- API endpoit to check for missing snippets (e.g. for usage in a monitoring tool)

## TODO:
- Remove boilerplate code
- Check and add compatibility for Shopware 6.4.*
