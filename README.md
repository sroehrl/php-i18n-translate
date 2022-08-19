# PHP i18n translate

> Straight forward. Convenient. Fast.

[![Build](https://github.com/sroehrl/php-i18n-translate/actions/workflows/php.yml/badge.svg)](https://github.com/sroehrl/php-i18n-translate/actions/workflows/php.yml)
[![Coverage](https://raw.githubusercontent.com/sroehrl/php-i18n-translate/badges/.github/badges/test-badge.svg)](https://github.com/sroehrl/php-i18n-translate/actions/workflows/php.yml)


## Installation

`composer require sroehrl/php-i18n-translate`

```php 
$i18n = new I18nTranslate\Translate();

$i18n->setTranslations('de', [
    'hello' => 'hallo',
    'goose' => ['Gans', 'Gänse']
]);
```

## Quick start:

### 1. In Code

```php

echo "a: " . $i18n->t('hello') . "<br>"; 
echo "b: " . $i18n->t('goose') . "<br>";
echo "c: " . $i18n->t('goose.plural') . "<br>";

// detect plural by numeric value
foreach([0,1,2] as $number){
    echo $number . " " . $i18n->t('goose', $number) . ", ";
}

```
Outputs:
```html
a: hallo <br>
b: Gans <br>
c: Gänse <br>
0 Gänse, 1 Gans, 2 Gänse
```

### 2. In HTML

main.html
```html
<!-- We haven't provided any locale, so the ACCEPTED LANGUAGE header is used -->
<p i18n-date>03/06/2009</p>

<!-- If no value is present, the current timestamp is used -->
<p i18n-date="\`y H:i"></p>

<!-- If it's not a timestamp, things become smart -->
<p i18n-date="d.m">next monday</p>

<!-- let's translate again, using the t-tag -->
<p><t>goose</t></p>
<p><t>goose.plural</t></p>
```
```php 
...

echo $i18n->translate(file_get_contents('main.html'));
```
Outputs:
```html 
<!-- We haven't provided any locale, so the ACCEPTED LANGUAGE header is used -->
<p>06.03.2009</p>

<!-- In no value is present, the current timestamp is used -->
<p>`22 13:14</p>

<!-- If it's not a timestamp, things become smart -->
<p>22.06</p>

<!-- let's translate again, using the t-tag -->
<p><t>Gans</t></p>
<p><t>Gänse</t></p>
```

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- Table of Contents
- [Initialization](#installation)
- [Setting up translations](#setting-up-translations)
- [Time & Date Formats](#time--date-formats)
- [Usage with "Template" _**recommended**_](#using-i18ntranslate-with-version-2-of-the-neoan3-appstemplate-engine)

## Initialization

`$t = new I18nTranslate(string $locale = null)`

You can initialize either with or without a ISO-locale (e.g. 'en-US').
If no value is provided, the class first tries to set the locale by the ACCEPT_LANGUAGE header and if that fails
defaults to "en-US".

## Setting up translations

Whether you read your translations from a database or a file: gettext is not required, and you are expected to
run the method `setTranslations` for every language you support.

```php
$t = new I18nTranslate();
$all = [
    ['eo' => ['blue' => 'blua',...]],
    ['jp' => ['blue' => '青い',...]], // BTW: make sure to consider encoding
    ['de' => ['blue' => 'blau',...]],
];
foreach($all as $lang => $translations){
    $t->setTranslations($lang, $translations);
}
```
> $t->setDebug(true)
>
> Will output a missing key message when a translation isn't set, rather than the following default behavior.
> This can be useful while developing/translating.

- If the language is not found, the class will default to the first defined language
- If a key is not found, the class will return the key
- If the key has a suffix (.plural), it will be removed

**About locale-translations:** The decision to ignore the country-specification on the locale on translations
is intended. While formatting reacts to the differences of country localisation, translations do not.
Example en-US vs. en-EN: the date formatting will react to these differences,
but translations like 'color' <=> 'colour' are not supported.

## Time & Date formats

Out of the box, the localization decides between the imperial and metric system.
Given the wide range of date formats used around the globe, it is recommended to target important audiences individually.

Date & Time inputs are interpreted either as UNIX timestamps or strings supported by PHP's [strtotime](https://www.php.net/manual/en/function.strtotime) function.

Date & Time outputs accept strings in the format of [DateTimeInterface::format](https://www.php.net/manual/en/datetime.format.php)

_Defaults:_

| | Metric | Imperial |
| --- | --- | --- |
| date | d.m.Y | m/d/Y |
| time | H:i | h:i A |

The examples at [Quick Start](#quick-start) should help.

## Using i18nTranslate with version 2+ of the neoan3-apps/template engine.

Under the hood this package interprets html-files with the help of [neoan3-apps/template](https://packagist.org/packages/neoan3-apps/template).
It is therefore already available to you once you installed this package. The following setup allows the template engine to
run **PRIOR** to translations, making dynamic formats and values possible:

```php 
use I18nTranslate\Translate;
use Neoan3\Apps\Template\Constants;
use Neoan3\Apps\Template\template;
...
// your template path
Constants::setPath(__DIR__ . '/view');

// initialize i18n
$t = new Translate('de-DE);
$t->setTranslations('de', [
    'woman' => ['Frau', 'Frauen'],
    'man' => ['Mann', 'Männer']
]);

$regularRenderData = [
    'tomorrow' => time() + 60*60*24,
    'format' => 'd-m-y',
    'iterateMe' => [0,1,2],
    'fromCode' => $t->('man')
];

echo $t->translate(Template::embraceFromFile('/test.html'), $regularRenderData)
```
test.html
```html 
<h2>{{fromCode}}</h2>
<p>Hackathon @ <span i18n-date>{{tomorrow}}</span></p>
<p i18n-date="{{format}}">next monday</p>
<div>
    <p n-for="iterateMe as number">{{number}} {{t(woman, {{number}})}}</p>
</div>

```
Example-output:
```html
<h2>Mann</h2>
<p>Hackathon @ <span>20.08.2022</span></p>
<p>22-08-22</p>
<div>
    <p>0 Frauen</p>
    <p>1 Frau</p>
    <p>2 Frauen</p>
</div>
```