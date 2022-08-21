<?php

namespace I18nTranslate;

use Neoan3\Apps\Template\Constants;
use Neoan3\Apps\Template\Template;

class Translate
{
    private array $translations = [];
    public Formatter $formatter;
    private bool $debug = false;

    private string $locale = 'en-US';
    private string $lang = 'en';
    private array $globalContextData = [];

    public function __construct(string $locale = null, string $clientTimezone = null)
    {
        if (!$locale && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $locale = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        [
            0 => $this->lang
        ] = explode('-', $locale);
        $this->locale = $locale;
        $this->formatter = new Formatter($locale, $clientTimezone);
        $this->attributes();
        $this->functions();
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
    public function setContextData(array $contextData = []): void
    {
        $this->globalContextData = $contextData;
    }
    public function setDebug(bool $bool): void
    {
        $this->debug = $bool;
    }
    private function applyDateTimeFormat(\DOMAttr $attr, $contextData, $what): void
    {
        $dateFormatter = $this->formatter->format($what);
        // get wanted time
        $existing = trim(Template::embrace($attr->parentNode->nodeValue, $contextData));
        $time = $this->identifyDateTime($existing);
        // get wanted format
        if ($attr->nodeValue) {
            $attr->parentNode->textContent = $dateFormatter($time, $attr->nodeValue);
        } else {
            $attr->parentNode->textContent = $dateFormatter($time);
        }
        $attr->parentNode->removeChild($attr);
    }
    private function identifyDateTime(mixed $input): float
    {
        return match (true) {
            is_numeric($input) => $input,
            !empty($input) => strtotime($input),
            default => time()
        };
    }


    private function attributes(): void
    {
        Constants::addCustomAttribute('i18n-currency', function(\DOMAttr $attr, array $contextData) {
            $currency = $this->formatter->format('currency');
            $currencyCode = empty($attr->nodeValue) ? null : $attr->nodeValue;
            $existing = trim(Template::embrace($attr->parentNode->nodeValue, $contextData));
            $attr->parentNode->nodeValue = $currency((float)$existing, $currencyCode);
            $attr->parentNode->removeChild($attr);
        });
        Constants::addCustomAttribute('i18n-date', function (\DOMAttr $attr, array $contextData) {
            $this->applyDateTimeFormat($attr, $contextData, 'date');
        });
        Constants::addCustomAttribute('i18n-date-local', function (\DOMAttr $attr, array $contextData) {
            $this->applyDateTimeFormat($attr, $contextData, 'date-local');
        });
        Constants::addCustomAttribute('i18n-time', function (\DOMAttr $attr, array $contextData) {
            $this->applyDateTimeFormat($attr, $contextData, 'time');
        });
        Constants::addCustomAttribute('i18n-time-local', function (\DOMAttr $attr, array $contextData) {
            $this->applyDateTimeFormat($attr, $contextData, 'time-local');
        });
        Constants::addCustomAttribute('i18n-number', function (\DOMAttr $attr, array $contextData) {
            $this->applyDateTimeFormat($attr, $contextData, 'number');
        });
    }

    private function functions(): void
    {
        // the order of things matters!
        Constants::addCustomFunction('t', function ($original, $additional = null) {
            return $this->t($original, $additional);
        });
        Constants::addCustomFunction('i18n-currency', function($original, $additional = null){
            $currency = $this->formatter->format('currency');
            $interpreted = $this->arithmeticInterpretation($original);
            return '[%currency-value%](%' . $currency($interpreted, $additional) . '%)';
        });
        Constants::addCustomFunction('i18n-number', function ($original){
            $number = $this->formatter->format('number');
            $interpreted = $this->arithmeticInterpretation($original);
            return '[%number-value%](%' . $number($interpreted) . '%)';
        });
        foreach(['date','date-local','time','time-local'] as $func){
            Constants::addCustomFunction('i18n-' .$func, function ($original, $additional = null) use($func){
                $funcParts = explode('-', $func);
                $formatter = $this->formatter->format($func);
                $interpreted = $this->identifyDateTime(trim($original));
                return '[%'. $funcParts[0] .'-value%](%' . $formatter($interpreted, $additional) . '%)';
            });
        }

        Constants::addCustomFunction('i18n-evaluate', function($function, $additional = null){
            $result = $this->translations[$this->lang][$function](...explode(',', $additional));
            return "[%$function%](%$result%)";
        });
    }

    public function setTranslations(string $lang, array $assocKeyValues): void
    {
        $simplified = $assocKeyValues;
        foreach ($assocKeyValues as $key => $translation) {
            if (is_array($translation)) {
                $simplified[$key] = $translation[0];
                $simplified[$key . '.plural'] = $translation[1];

            }
        }
        $this->translations[$lang] = $simplified;
    }

    public function translate($html): string
    {
        $originalDelimiter = Constants::getDelimiter();
        Constants::setDelimiter('<t>', '<\/t>');
        $context = $this->translations[$this->lang] ?? null;
        if(!$context){
            if(empty(array_keys($this->translations))){
                $this->translations['en-US'] = [];
            }
            $context = $this->translations[array_keys($this->translations)[0]] ?? [];
        }
        $output = Template::embrace($html, $context);

        Constants::setDelimiter(...$originalDelimiter);
        return $output;
    }

    public function t(string $original, $additional = null): string
    {
        $additional = trim($additional);
        $which = is_numeric($additional) && $additional != 1 ? $original . '.plural' : $original;
        return $this->getTranslation($which);
    }


    private function getTranslation($key): string|array
    {
        if(isset($this->translations[$this->lang][$key])){
            return $this->translations[$this->lang][$key];
        } elseif ($this->debug){
            return "missing translation: $key";
        }
        return $this->translations[array_keys($this->translations)[0]][$key] ?? str_replace('.plural','',$key);
    }
    private function arithmeticInterpretation(string $calcString): float
    {
        $finalResult = 0;
        $previousOperator = '+';
        $exploreOriginal = explode(' ', trim($calcString));
        foreach ($exploreOriginal as $i => $part){
            $interim = $this->globalContextData[trim($part)] ?? trim($part);
            if(is_numeric($interim)){
                $finalResult = match($previousOperator){
                    '+' => $finalResult + $interim,
                    '-' => $finalResult - $interim,
                    '/' => $finalResult / $interim,
                    '*' => $finalResult * $interim,
                    '**' => $finalResult ** $interim,
                    '%' => $finalResult % $interim
                };
            } else {
                $previousOperator = $interim;
            }
        }
        return $finalResult;
    }
}