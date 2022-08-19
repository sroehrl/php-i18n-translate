<?php

namespace Test;

use I18nTranslate\Formatter;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    function testNumberIntl()
    {
        $formatter = new Formatter('de-DE');
        $call = $formatter->format('number');
        $this->assertSame('12,3', $call(12.3));
    }
    function testLocalDateIntl()
    {
        $formatter = new Formatter('de-DE');
        $call = $formatter->format('date-local');
        $existingTimezone = date_default_timezone_get();
        ini_set('date.timezone','Europe/Berlin');
        $now = time();
        $this->assertSame(date('d.m.Y H:i',$now ),$call($now,'dd.MM.Y HH:mm'));
        $formatter = new Formatter('en-US');
        $call = $formatter->format('date-local');
        $this->assertSame(date('m/d/Y',$now ),$call($now));
        ini_set('date.timezone', $existingTimezone);
    }
    function testDateIntl()
    {
        $f = new Formatter('de-DE');
        $call = $f->format('date');
        $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}/', $call(time()));
    }
    function testTimeLocalIntl()
    {
        $f = new Formatter('de-DE', date_default_timezone_get());
        $closure = $f->format('time-local');
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}/', $closure(time()));
        $this->assertSame(date('H:i'), $closure(time()));
    }
    function testTimeIntl()
    {
        $f = new Formatter('de-DE');
        $closure = $f->format('time');
        $this->assertMatchesRegularExpression('/\d{2}:\d{2}/', $closure(time()));
    }

    function testCurrency()
    {
        $formatter = new Formatter('de-DE');
        $call = $formatter->format('currency');
        $this->assertSame("12,13 €", $call(12.13));
        $this->assertSame("12,13 $", $call(12.13, 'USD'));
        $formatter2 = new Formatter('en-US');
        $call = $formatter2->format('currency');
        $this->assertSame("$12.13", $call(12.13));
    }
    function testFallbackDefaults()
    {
        $f = new Formatter('de-DE', 'America/New_York', 'NotExistingClass');
        $number = $f->format('number');
        $this->assertSame('12,30', $number(12.3));
        $currency = $f->format('currency');
        $this->assertSame('€12,09', $currency(12.09));
        $date = $f->format('date');
        $this->assertSame(date('d.m.Y'), $date(time()));
        $time = $f->format('time');
        $this->assertSame(date('H:i'), $time(time()));
        $time = $f->format('time-local');
        $this->assertSame(date('H:i'), $time(time()));
    }
}
