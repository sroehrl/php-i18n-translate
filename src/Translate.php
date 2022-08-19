<?php

namespace I18nTranslate;

use Neoan3\Apps\Template\Constants;
use Neoan3\Apps\Template\Template;

class Translate
{
    private array $translations = [];
    private array $formats;
    private bool $debug = false;

    private string $locale = 'en-US';
    private string $lang = 'en';

    public function __construct(string $locale = null)
    {
        if (!$locale && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $locale = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        [
            0 => $this->lang
        ] = explode('-', $locale);
        $this->locale = $locale;
        $this->formats = $this->getDateFormat();
        $this->attributes();
        $this->functions();
    }
    public function setDebug(bool $bool): void
    {
        $this->debug = $bool;
    }

    private function getDateFormat(): array
    {
        $country = mb_substr($this->locale, 3);
        $dateFormat = match ($country) {
            'US' => 'm/d/Y',
            default => 'd.m.Y'
        };
        $timeFormat = match ($country) {
            'US' => 'h:i A',
            default => 'H:i'
        };
        return [
            'date' => $dateFormat,
            'time' => $timeFormat
        ];

    }

    private function attributes(): void
    {
        Constants::addCustomAttribute('i18n-date', function (\DOMAttr $attr, array $contextData) {

            // get wanted time
            $existing = trim(Template::embrace($attr->parentNode->nodeValue, $contextData));
            $time = match (true) {
                is_numeric($existing) => $existing,
                !empty($existing) => strtotime($existing),
                default => time()
            };
            // get wanted format
            if ($attr->nodeValue) {
                $attr->parentNode->textContent = date($attr->nodeValue, $time);
            } else {
                $attr->parentNode->textContent = date($this->formats['date'], $time);
            }
            $attr->parentNode->removeChild($attr);

        });
    }

    private function functions(): void
    {
        Constants::addCustomFunction('t', function ($original, $additional = null) {
            return $this->t($original, $additional);
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
}