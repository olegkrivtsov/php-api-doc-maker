# PHP API Documentation Maker

A simple yet powerful PHP API documentation generator.

## Prerequisites

First of all, you need PHP 5.6 or later to run this tool.

## Installation

Clone or download the source code of this tool to some directory, then install dependencies:

```
php composer.phar self-update
php composer.phar update
```

## Generating the API Reference

Assume you would like to generate the API reference of some library consisting of components. Let's take a real-life example (Zend Framework 3 API reference): https://github.com/olegkrivtsov/zf3-api-reference

Create a directory, for example, `zf3-api-doc-reference`.

Inside that directory, install all components you need with `composer require <component-name>`.

Create the `php-api-doc-maker.json` file with the content like below:

```
{
    "title": "Zend Framework 3 API Reference",
    "copyright": "(c) 2018 by Oleg Krivtsov",
    "license": "https://creativecommons.org/licenses/by-nc-sa/4.0/",
    "website": "https://olegkrivtsov.github.io/zf3-api-reference/html",
    "keywords": [
        "php",
        "zend framework",
        "api",
        "reference"
    ],
    "links": {
        "Home": "https://olegkrivtsov.github.io/zf3-api-reference/html",
        "Contribute": "https://github.com/olegkrivtsov/zf3-api-reference"
    },
    "components": [
        "zendframework/zend-authentication",
        "zendframework/zend-barcode",
        "zendframework/zend-cache",
        "zendframework/zend-captcha",
        "zendframework/zend-code",
        "zendframework/zend-config",
        "zendframework/zend-console",
        "zendframework/zend-crypt",
        "zendframework/zend-db",
        "zendframework/zend-dom",
        "zendframework/zend-escaper",
        "zendframework/zend-eventmanager",
        "zendframework/zend-filter",
        "zendframework/zend-form",
        "zendframework/zend-http",
        "zendframework/zend-hydrator",
        "zendframework/zend-inputfilter",
        "zendframework/zend-i18n",
        "zendframework/zend-json",
        "zendframework/zend-log",
        "zendframework/zend-mail",
        "zendframework/zend-modulemanager",
        "zendframework/zend-mvc",
        "zendframework/zend-view"
    ],
    "google_analytics": {
        "enabled": true,
        "account_id": "<your-ga-id>"
    },
    "google_adsence": {
        "enabled": true, 
        "upper_ad": "data/upper_ad.js"
    },
    "disqus": {
        "enabled": false,
        "src": "//zf3-api-reference.disqus.com/embed.js"
    }
}
```

The most important part is the `components` subkey where you should list the components you want to document.

When this is ready, generate the API reference with the command:

`php php-api-doc-maker.php /path/to/zf3-api-doc-reference`.

If everything is OK, you should find the HTML files inside the `/path/to/zf3-api-doc-reference/html` directory.

That's all, enjoy!
