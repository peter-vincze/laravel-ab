<?php

namespace PeterVincze\AbTesting\Tests;

use PeterVincze\AbTesting\AbTesting;
use PeterVincze\AbTesting\AbTestingFacade;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use PeterVincze\AbTesting\Events\ExperimentNewVisitor;

class PageViewTest extends TestCase
{
    public function test_that_pageview_works()
    {
        AbTestingFacade::pageView();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];
        $this->assertEquals(array_keys($this->experiments)[0], $experiment->name);

        $this->assertEquals(1, $experiment->visitors);

        Event::assertDispatched(ExperimentNewVisitor::class, function ($e) use ($experiment) {
            return $e->experiment->id === $experiment->id;
        });
    }

    public function test_that_pageview_changes_after_first_test()
    {
        $this->test_that_pageview_works();

        $_SESSION = [];

        $this->assertNull($_SESSION[AbTesting::SESSION_KEY_EXPERIMENT] ?? null);

        AbTestingFacade::pageView();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];

        $this->assertEquals(array_keys($this->experiments)[1], $experiment->name);
        $this->assertEquals(1, $experiment->visitors);
    }

    public function test_that_crawlers_does_not_trigger_pageviews()
    {
        $this->startActingAsCrawler();

        AbTestingFacade::pageView();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];

        $this->assertEquals(0, $experiment->visitors);

        Event::assertNotDispatched(ExperimentNewVisitor::class);
        $this->stopActingAsCrawler();
    }

    public function test_is_experiment()
    {
        AbTestingFacade::pageView();

        $this->assertTrue(AbTestingFacade::isExperiment('firstExperiment'));
        $this->assertFalse(AbTestingFacade::isExperiment('secondExperiment'));

        $this->assertEquals('firstExperiment', AbTestingFacade::getExperiment()->name);
    }

    public function test_that_two_pageviews_do_not_count_as_two_visitors()
    {

        AbTestingFacade::pageView();
        AbTestingFacade::pageView();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];

        $this->assertEquals(1, $experiment->visitors);
    }

    public function test_that_isExperiment_triggers_pageview()
    {

        AbTestingFacade::isExperiment('firstExperiment');

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];

        $this->assertEquals(array_keys($this->experiments)[0], $experiment->name);
        $this->assertEquals(1, $experiment->visitors);

    }

    public function test_request_macro()
    {

        $this->newVisitor();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];

        $this->assertEquals($experiment, request()->abExperiment());
    }

    public function test_blade_macro()
    {
        $this->newVisitor();

        $this->assertTrue(Blade::check('ab', 'firstExperiment'));
    }
}
