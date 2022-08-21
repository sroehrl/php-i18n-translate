<?php

namespace Test;

use I18nTranslate\Translate;
use Neoan3\Apps\Template\Template;
use PHPUnit\Framework\TestCase;

class TranslateTest extends TestCase
{

    private array $translations = [
        'de' => [
            'hi' => 'hallo',
            'bus' => ['Bus','Busse'],
        ]
    ];
    protected function setUp(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE';
    }

    public function testTranslate()
    {
        $t = new Translate();
        $t->setTranslations('de', $this->translations['de']);
        $this->assertSame('Busse', $t->t('bus.plural'));
    }

    public function testSetDebug()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $t->setDebug(true);
        $this->assertStringStartsWith('missing translation', $t->t('mine'));
        $t->setDebug(false);
        $this->assertSame('not valid', $t->t('not valid'));

    }

    public function testSetTranslations()
    {
        $t = new Translate('no-LA');
        $this->setTranslations($t);
        $res = $t->translate('<p><t>hi</t><t>not there</t></p>');
        $this->assertStringContainsString('hallo', $res);
        $this->assertStringContainsString('not there', $res);
    }

    public function testAttributeDate()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $today = date('d');
        $res = Template::embrace('<section><div i18n-date="d"></div><p i18n-date>today</p></section>',[]);
        $this->assertStringContainsString($today, $res);
        $this->assertStringContainsString(date('d.m.Y'), $res);
        ini_set('date.timezone','America/New_York');
        $resLocal = Template::embrace('<p i18n-date-local>2020-01-01 23:59</p>',[]);
        $this->assertMatchesRegularExpression('/02\.\d{2}\.\d{4}/', $resLocal);
    }
    public function testAttributeCurrency()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $res = Template::embrace('<p i18n-currency="USD">30.13</p>',[]);
        $this->assertSame('<p>30,13 $</p>', $res);
    }
    public function testAttributeNumber()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $res = Template::embrace('<p i18n-number>30.13</p>',[]);
        $this->assertSame('<p>30,13</p>', $res);
    }
    public function testAttributeTime()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $time = time();
        $res = Template::embrace("<p i18n-time>$time</p>",[]);
        $this->assertSame('<p>' .date('H:i', $time) .'</p>', $res);
        $resLocal = Template::embrace("<p i18n-time-local>$time</p>",[]);
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}/', $resLocal);
    }

    public function testFunctions()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $context = [
            'now' => time()
        ];
        $t->setContextData($context);
        // t-function
        $res = Template::embrace('<p>{{t(bus.plural)}}</p>',$context);
        $this->assertStringContainsString('Busse', $res);
        // currency
        $res = Template::embrace('<p>{{i18n-currency(12)}}</p>',$context);
        $this->assertStringContainsString('[%currency-value%](%12,00', $res);
        // number
        $res = Template::embrace('<p>{{i18n-number(12.5)}}</p>',$context);
        $this->assertStringContainsString('[%number-value%](%12,5', $res);
        // date & time
        foreach(['date','date-local','time','time-local'] as $function){
            $res = Template::embrace("<p>{{i18n-{$function}(+3 hours)}}</p>",$context);
            $parts = explode('-',$function);
            $this->assertStringContainsString('[%' . $parts[0] . '-value%](%', $res);
        }
        // evaluate
        $t->setTranslations('de',[
            'myFunc' => fn($input) => $input . '!'
        ]);
        $res = Template::embrace('<p>{{i18n-evaluate(myFunc, louder)}}</p>',$context);
        $this->assertStringContainsString('[%myFunc%](% louder!', $res);
    }

    public function testCalculations()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $basic = "4 + 1 - 3 / 2 * 2 ** 2 % 3"; //1
        $res = Template::embrace('<p>{{i18n-number('.$basic.')}}</p>',[]);
        $this->assertStringContainsString('[%number-value%](%1', $res);
    }

    public function testGetLocale()
    {
        $t = new Translate('de-DE','Europe/Berlin');
        $this->setTranslations($t);
        $this->assertSame('de-DE', $t->getLocale());
    }
    public function testNoTranslations()
    {
        $t = new Translate('de-DE','Europe/Berlin');
        $res = $t->translate("<t>whut</t>",[]);
        $this->assertSame("<t>whut</t>",$res);
    }


    private function setTranslations(Translate $instance)
    {
        $instance->setTranslations('de', $this->translations['de']);
    }
}
