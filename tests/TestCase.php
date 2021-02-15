<?php

namespace PeterVincze\AbTesting\Tests;

use PeterVincze\AbTesting\AbTestingFacade;
use Illuminate\Support\Facades\Event;
use PeterVincze\AbTesting\AbTestingServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $experiments = [
        'firstExperiment'  =>['git_checkout' => '', 'git_repo' => '', 'deploy_script'    => ''],
        'secondExperiment' =>['git_checkout' => '', 'git_repo' => '', 'deploy_script'    => '']
    ];
    protected $goals = [
        'firstGoal'  =>  ['autocompletegoal_route_regexp_pattern' => '', 'goal_once_a_session' => 1],
        'secondGoal' =>  ['autocompletegoal_route_regexp_pattern' => '', 'goal_once_a_session' => 1]
    ];
    protected $temp_config = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->loadLaravelMigrations();
        $this->artisan('migrate')->run();

        $_SESSION = [];

        Event::fake();
    }

    protected function getEnvironmentSetUp($app)
    {

        $app['config']->set('ab-testing.experiments', $this->experiments);
        $app['config']->set('ab-testing.goals', $this->goals);
    }

    protected function getPackageProviders($app)
    {
        return [AbTestingServiceProvider::class];
    }

    protected function newVisitor()
    {
        $_SESSION = [];
        AbTestingFacade::pageView();
    }

    protected function startGitCheckout()
    {
        $this->temp_config=config()->get('ab-testing');
        config()->set('ab-testing',['experiments' => [
            'firstExperiment'  =>['git_checkout' => 'experiments/buy_button_green', 'git_repo' => 'https://github.com/peter-vincze/laravel-ab-example-app.git', 'deploy_script' => 'deplyoy.sh'],
            'secondExperiment' =>['git_checkout' => 'experiments/buy_button_red', 'git_repo' => 'https://github.com/peter-vincze/laravel-ab-example-app.git', 'deploy_script' => '']],
        'goals' => [
            'firstGoal' => ['autocompletegoal_route_regexp_pattern' => '', 'goal_once_a_session' => 1],
            'secondGoal' => ['autocompletegoal_route_regexp_pattern' => '', 'goal_once_a_session' => 1]]]);
    }
    protected function stop()
    {
        config()->set('ab-testing',$this->temp_config);
    }
    protected function stopActingAsCrawler()
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        config()->set('ab-testing',$this->temp_config);
    }

    protected function startActingAsCrawler()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'crawl';
        $this->temp_config=config()->get('ab-testing');
        config()->set('ab-testing.ignore_crawlers', true);
    }
}
