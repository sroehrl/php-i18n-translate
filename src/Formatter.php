<?php

namespace I18nTranslate;


use Closure;
use IntlDateFormatter;
use IntlTimeZone;
use NumberFormatter;

class Formatter
{
    private bool $hasIntl;
    private array $currencies;
    private string $country;
    private string $locale;
    private ?string $clientTimezone = null;

    public function __construct($locale, $clientTimeZone = null, $mockIntlDateFormatter = null)
    {
        $this->locale = $locale;
        $this->clientTimezone = $clientTimeZone;
        $this->currencies = require 'currencies.php';
        $this->country = substr($this->locale,3);
        $this->hasIntl = class_exists($mockIntlDateFormatter ?? IntlDateFormatter::class);
    }
    private function formatWithIntl(string $what): Closure
    {
        switch ($what) {
            case 'number':
                $fmt = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
                return fn(float|int $value) => $fmt->format($value);
            case 'currency':
                $fmt = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
                return function (float|int $amount, string $currency = null) use ($fmt) {
                    $country = substr($this->locale, 3);
                    return $fmt->formatCurrency($amount, $currency ?? $this->currencies[$country][0]);
                };
            case 'date':
            case 'date-local':
                $fmt = $this->initFormatter($what === 'date-local' ? $this->getClientTimezone():null);
                return function(int $time, string $pattern = null) use($fmt)
                {
                    $fmt->setPattern($pattern ?? $this->getDefaults('date'));
                    return $fmt->format($time);
                };
            case 'time':
            case 'time-local':
                $fmt = $this->initFormatter($what === 'time-local' ? $this->getClientTimezone(): null);
                return function(int $time, string $pattern = null) use($fmt)
                {
                    $fmt->setPattern($pattern ?? $this->getDefaults('time'));
                    return $fmt->format($time);
                };

        }
    }

    private function formatFallback(string $what): Closure
    {
        return function(float|int $input, $format = null) use($what) {
            switch ($what) {
                case 'number':
                case 'currency':
                    $notation = $format ?? $this->currencies[$this->country];
                    return ($what === 'currency' ? $notation[1] : '') . number_format($input, 2, $notation[2], ' ');
                case 'date':
                case 'date-local':
                    $pattern = str_replace(['mm','MM','dd'],['i','m','d'], $format ?? $this->getDefaults('date'));
                    return date($pattern, $input);
                case 'time':
                case 'time-local':
                    $pattern = str_replace(['mm'],['i'], $format ?? $this->getDefaults('time'));
                    return date($pattern, $input);
                default:
                    return $input;
            }

        };
    }

    public function format(string $what = 'number'): Closure
    {
        return $this->hasIntl ? $this->formatWithIntl($what) : $this->formatFallback($what);
    }
    private function initFormatter(string $timezone = null): IntlDateFormatter
    {
        if(!$timezone){
            $timezone = date_default_timezone_get();
        }
        return new IntlDateFormatter($this->locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL,$timezone, IntlDateFormatter::GREGORIAN);
    }
    private function getClientTimezone(): string
    {
        if($this->clientTimezone){
            return $this->clientTimezone;
        }
        $tzArray = iterator_to_array(IntlTimeZone::createEnumeration($this->country));
        return end($tzArray);
    }
    private function getDefaults(string $which = 'date'): string
    {
        $array = [];
        $array['date'] = match ($this->country) {
            'US' => 'MM/dd/Y',
            default => 'dd.MM.Y'
        };
        $array['time'] = match ($this->country) {
            'US' => 'hh:mm A z',
            default => 'HH:mm'
        };
        return $array[$which];
    }
}