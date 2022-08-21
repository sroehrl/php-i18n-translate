# PHP i18n translate

> Straight forward. Convenient. Fast.

[![Build](https://github.com/sroehrl/php-i18n-translate/actions/workflows/php.yml/badge.svg)](https://github.com/sroehrl/php-i18n-translate/actions/workflows/php.yml)
[![Coverage](https://raw.githubusercontent.com/sroehrl/php-i18n-translate/badges/.github/badges/test-badge.svg)](https://github.com/sroehrl/php-i18n-translate/actions/workflows/php.yml)
[![php](https://img.shields.io/static/v1?label=PHP&message=With%20Love&color=777BB4&logo=php)](https://php.net)
[![vegan](https://img.shields.io/static/v1?label=100%&message=vegan&color=47a244&logo=mongodb)](https://www.whyveganism.com/)
[![Maintainability](https://api.codeclimate.com/v1/badges/5b650de720eb802a84a9/maintainability)](https://codeclimate.com/github/sroehrl/php-i18n-translate/maintainability)

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

<div>
    <p>Event starts @ <strong i18n-time>12:30</strong> (That's <span i18n-time-local="hh:mm a">12:30</span> your time)</p>
</div>

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

<div>
    <p>Event starts @ <strong>12:30 EDT</strong> (That's <span>9:30 am</span> your time)</p>
</div>

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
- [Time, Date, Currencies & Numbers](#time-date-currencies--numbers)
- [Time & Date Formats](#time--date-formats)
- [Usage with "Template" _**recommended**_](#using-i18ntranslate-with-version-2-of-the-neoan3-appstemplate-engine)

## Initialization

`$t = new I18nTranslate(string $locale = null, string $clientTimezone = null)`

You can initialize either with or without a ISO-locale (e.g. 'en-US').
If no value is provided, the class first tries to set the locale by the ACCEPT_LANGUAGE header and if that fails
defaults to "en-US".
If you don't pass a $clientTimeZone (e.g. 'Europe/Paris'), then a guess is made based on the locale. This can potentially lead to 
time offsets in countries with multiple timezones.

> TIPS:
> 
> Timezones: There are several "timezone-guessing" mechanisms around. Using JavaScript is usually the most reliable way.
> 
> Settings: When dealing with internationalization, setting your server & database to UTC is a battle-tested approach.

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

## Time, Date, Currencies & Numbers

This package includes a formatter for currencies, numbers and dates. If you want to use its functionality
outside of an HTML template, you can initialize it yourself.

The following converters are at your disposal:
- date (accepts optional [format](#time--date-formats))
- date-local (accepts optional [format](#time--date-formats))
- time (accepts optional [format](#time--date-formats))
- time-local (accepts optional [format](#time--date-formats))
- currency (accepts optional, but recommended, ISO currency code like "USD")
- number

```php 
$locale = 'en-US';
$clientTimeZone = 'America/New_York'; // or null to let the class make an educated guess
$formatter = new I18nTranslate\Formatter(string $locale, $clientTimeZone);

$convertToClientTime = $formatter->format('time-local');

$serverTime = time();
$clientTime = $convertToClientTime($serverTime); // e.g. 09:30 AM EDT
$clientTime = $convertToClientTime($serverTime, 'h:mm'); // e.g. 9:30
```

In most scenarios the templating attributes will be sufficient to handle your needs:

```html
<section i18n-number>12.34</section>
<!-- with currencies we recommend setting a currency as it otherwise defaults to the user's locale... -->
<section i18n-currency="CAD">12.34</section>
<!-- ... But the following attributes accept formatting, but don't need it -->
<section i18n-date="EEEE">tomorrow</section>
<section i18n-date-local>tomorrow</section>
<section i18n-time>9:30</section>
<section i18n-time-local>9:30</section>
```

For a better understanding of how to pass values to your HTML, read [here](#using-i18ntranslate-with-version-2-of-the-neoan3-appstemplate-engine)

## Time & Date formats

This package uses the Intl-extension for PHP but has a fallback mechanisms. If you do not have Intl installed, localized transformation does not work.

Date & Time inputs are interpreted either as UNIX timestamps or strings supported by PHP's [strtotime](https://www.php.net/manual/en/function.strtotime) function.

Date & Time formats accepts strings in the format of ISO8601 date format **So not PHP's date notation**

I kindly ask contributors to find an appropriate list. Until then, this dated Zend list is the best I could find:
[formats](https://framework.zend.com/manual/1.12/en/zend.date.constants.html#zend.date.constants.selfdefinedformats)

_Default fallback formats:_

| | Metric | Imperial |
| --- | --- | --- |
| date | dd.MM.Y | MM/dd/Y |
| time | HH:mm | hh:mm A z |

Numbers and currencies work with or without the Intl-extension, but might not conform to the ISO 8601 standard without the Intl-extension.


The examples at [Quick Start](#quick-start) should help.

## Using i18nTranslate with version 2+ of the neoan3-apps/template engine.

Under the hood this package interprets html-files with the help of [neoan3-apps/template](https://packagist.org/packages/neoan3-apps/template).
It is therefore already available to you once you installed this package. The following setup allows the template engine to
run **PRIOR** to translations, making dynamic formats and values possible:

```php 
use I18nTranslate\Translate;
use Neoan3\Apps\Template\Constants;
use Neoan3\Apps\Template\Template;
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
    'format' => 'dd-MM-Y',
    'iterateMe' => [0,1,2],
    'fromCode' => $t->('man')
];

echo $t->translate(Template::embraceFromFile('/test.html', $regularRenderData));
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

## CONTRIBUTION

[rules](/contributing.md)