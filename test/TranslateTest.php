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
            'bus' => ['Bus','Busse']
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

    public function testAttributes()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $today = date('d');
        $res = Template::embrace('<section><div i18n-date="d"></div><p i18n-date>today</p></section>',[]);
        $this->assertStringContainsString($today, $res);
        $this->assertStringContainsString(date('d.m.Y'), $res);
    }

    public function testFunctions()
    {
        $t = new Translate();
        $this->setTranslations($t);
        $res = Template::embrace('<p>{{t(bus.plural)}}</p>',[]);
        $this->assertStringContainsString('Busse', $res);
    }



    private function setTranslations(Translate $instance)
    {
        $instance->setTranslations('de', $this->translations['de']);
    }
}
