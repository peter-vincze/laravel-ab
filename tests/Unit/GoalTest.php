<?php

namespace PeterVincze\AbTesting\Tests;

use PeterVincze\AbTesting\AbTesting;
use PeterVincze\AbTesting\AbTestingFacade;
use Illuminate\Support\Facades\Event;
use PeterVincze\AbTesting\Events\GoalCompleted;
use PeterVincze\AbTesting\Models\Goal;

class GoalTest extends TestCase
{
    public function test_that_goal_complete_works()
    {
        $returnedGoal = AbTestingFacade::completeGoal('firstGoal');

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];
        $goal = $experiment->goals->where('name', 'firstGoal')->first();

        $this->assertEquals($goal, $returnedGoal);

        $this->assertEquals(1, $goal->hit);

        $this->assertEquals(collect([$goal->id]), $_SESSION[AbTesting::SESSION_KEY_GOALS]);

        Event::assertDispatched(GoalCompleted::class, function ($g) use ($goal) {
            return $g->goal->id === $goal->id;
        });
    }

    public function test_that_goal_can_only_be_completed_once()
    {
        $this->test_that_goal_complete_works();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];
        $goal = $experiment->goals->where('name', 'firstGoal')->first();

        $this->assertEquals(1, $goal->hit);

        $returnedGoal = AbTestingFacade::completeGoal('firstGoal');

        $this->assertFalse($returnedGoal);

        $this->assertEquals(1, $goal->hit);

        $this->assertEquals(collect([$goal->id]), $_SESSION[AbTesting::SESSION_KEY_GOALS]);
    }

    public function test_that_goal_can_be_completed_twice()
    {
        $this->test_that_goal_complete_works();

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];
        $goal = $experiment->goals->where('name', 'firstGoal')->first();
        
        $this->assertEquals(1, $goal->hit);
        $goal->goal_once_a_session = 0;
        $goal->save();
        $returnedGoal = AbTestingFacade::completeGoal('firstGoal');

        $this->assertEquals(2, $returnedGoal->hit);

        $this->assertEquals(collect([$goal->id,$goal->id]), $_SESSION[AbTesting::SESSION_KEY_GOALS]);
        Goal::where('name', 'firstGoal')->update(['goal_once_a_session' => 1]);
        $returnedGoal->goal_once_a_session = 1;
        $returnedGoal->save();

    }

    public function test_that_crawlers_does_not_complete_goals()
    {
        $this->startActingAsCrawler();

        $goal = AbTestingFacade::completeGoal('firstGoal');

        $this->assertEquals(0, $goal->hit);

        Event::assertNotDispatched(GoalCompleted::class);
        $this->stopActingAsCrawler();
    }

    public function test_that_invalid_goal_name_returns_false()
    {
        $this->assertFalse(AbTestingFacade::completeGoal('1234'));
    }

    public function test_that_completed_goals_works()
    {
        AbTestingFacade::completeGoal('firstGoal');

        $experiment = $_SESSION[AbTesting::SESSION_KEY_EXPERIMENT];
        $goal = $experiment->goals->where('name', 'firstGoal');

        $this->assertEquals($goal->pluck('id')->toArray(), AbTestingFacade::getCompletedGoals()->pluck('id')->toArray());
    }
}
