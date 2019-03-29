# PHP API Documentation Maker

This tool is an API documentation generator for any PHP library or framework consisting of Composer-compatible components. The tool scans the `composer.json` and `*.php` class files of each component you specify and produces a nice looking static HTML documentation which you host anywhere (even on GitHub pages).

## Background

I wrote this tool to generate the [API reference](https://github.com/olegkrivtsov/zf3-api-reference) for Zend Framework 3. 

## Features:
 
 * Runs from command line.
 * Highly configurable - you can idenfity which components you want to document, skipping all others.
 * Generates HTML5 static documentation viewable on any-sized screen. Take a look [here](https://olegkrivtsov.github.io/zf3-api-reference/html/) for an example.
 * Embed a Google Analytics tag to track visitors of the site.
 * Embed Google Adsense Ads to get revenue from visitors of the site.
 * Embed a Disqus thread if you want to enable visitors to leave comments on the site.

## Installation

You need PHP 5.6 or later to run this tool.

Clone or download the source code of this tool to some directory, then install dependencies:

```
php composer.phar self-update
php composer.phar update
```

## Generating the API Reference

Assume you would like to generate the API reference of some library consisting of components. Let's take a real-life example (Zend Framework 3 API reference): https://github.com/olegkrivtsov/zf3-api-reference

Create a directory, for example, `zf3-api-reference`.

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

`php php-api-doc-maker.php /path/to/zf3-api-reference`

If everything is OK, you should find the HTML files inside the `/path/to/zf3-api-reference/html` directory.

That's all, enjoy!
