# yii2-sitemap
Simple library to generate sitemap xml files Using PHP and Yii2

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require aliobeidat/yii2-sitemap "*"
```

or add

```
"aliobeidat/yii2-sitemap": "*"
```

to the require section of your `composer.json` file.

Usage
------------
```php
use aliobeiat/sitemap/SitemapGenerator;

Yii::$app->urlManager->baseUrl = 'http://site.com'; // base url use in sitemap urls creation

$sitemap = new PostsSitemap(); // must implement a SitemapInterface
$sitemapGenerator = new SitemapGenerator([
  'sitemaps' => [$sitemap],
  'dir' => '@webRoot',
  'baseUrlDir' => 'sitemap',
]);
$sitemapGenerator->generate();