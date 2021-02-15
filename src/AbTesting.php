<?php

namespace PeterVincze\AbTesting;
use PeterVincze\AbTesting\Models\Goal;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use PeterVincze\AbTesting\Models\Experiment;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use PeterVincze\AbTesting\Exceptions\InvalidConfiguration;

class AbTesting
{
    protected $experiments;

    const SESSION_KEY_EXPERIMENT = 'ab_testing_experiment';
    const SESSION_KEY_GOALS = 'ab_testing_goals';

    public function __construct()
    {
        $this->experiments = new Collection;
        if (php_sapi_name() !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
    /**
     * Validates the config items and puts them into models.
     *
     * @return void
     */
    protected function start()
    {
        $configExperiments = config('ab-testing.experiments');
        $configGoals = config('ab-testing.goals');
        if (empty($configExperiments)) {
            throw InvalidConfiguration::noExperiment();
        }

        if (empty($configGoals)) {
            throw InvalidConfiguration::noGoal();
        }

        foreach ($configExperiments as $configExperimentKey => $configExperiment) {
            if (!isset($configExperiment['git_repo'])) {
               throw InvalidConfiguration::experiment();
            }
            if (!isset($configExperiment['git_checkout'])) {
               throw InvalidConfiguration::experiment();
            }
            $this->experiments[] = $experiment = Experiment::firstOrCreate([
            'name' => $configExperimentKey], 
            ['git_checkout' => $configExperiment['git_checkout'],
            'git_repo' => $configExperiment['git_repo'],
            'visitors' => 0,
            'deploy_script' => $configExperiment['deploy_script']]);
            foreach ($configGoals as $configGoalKey => $configGoal) {
                if (!isset($configGoal['autocompletegoal_route_regexp_pattern'])) {
                   throw InvalidConfiguration::goal();
                }
                if (!isset($configGoal['goal_once_a_session'])) {
                   throw InvalidConfiguration::goal();
                }

                $this->goals[] = $experiment->goals()->firstOrCreate([
                'name' => $configGoalKey], 
                ['autocompletegoal_route_regexp_pattern' => $configGoal['autocompletegoal_route_regexp_pattern'],
                'goal_once_a_session' => $configGoal['goal_once_a_session'],
                'hit' => 0]);
            }
        }
        $collection = new Collection;
        $_SESSION[self::SESSION_KEY_GOALS] = $collection;
    }

    /**
     * Triggers a new visitor. Picks a new experiment and saves it to the session.
     *
     * @return \PeterVincze\AbTesting\Models\Experiment|void
     */
    public function pageView()
    {
        $experiment = $this->getExperiment();
        if (!empty($experiment)) {
            return $experiment;
        }
        $this->start();

        $this->setNextExperiment();

        return $this->getExperiment();
    }

    /**
     * Calculates a new experiment and sets it to the session.
     *
     * @return void
     */
    protected function setNextExperiment()
    {
        $next = $this->getNextExperiment();
        $next->visit();
        $_SESSION[self::SESSION_KEY_EXPERIMENT] = $next;
    }

    /**
     * Calculates a new experiment.
     *
     * @return \PeterVincze\AbTesting\Models\Experiment|null
     */
    protected function getNextExperiment()
    {
        $sorted = $this->experiments->sortBy('visitors');

        return $sorted->first();
    }

    /**
     * Checks if the currently active experiment is the given one.
     *
     * @param string $name The experiments name
     *
     * @return bool
     */
    public function isExperiment(string $name)
    {
        $this->pageView();

        return $this->getExperiment()->name === $name;
    }

    /**
     * Completes a goal by incrementing the hit property of the model and setting its ID in the session.
     *
     * @param string $goal The goals name
     *
     * @return \PeterVincze\AbTesting\Models\Goal|false
     */
    public function completeGoal(string $goal)
    {
        $experiment = $this->getExperiment();
        if (!$experiment) {
            $this->pageView();
            $experiment = $this->getExperiment();
        }

        $goal = $experiment->goals->where('name', $goal)->first();
        if (!$goal || 
            ((bool)$goal->goal_once_a_session && $_SESSION[self::SESSION_KEY_GOALS]->contains($goal->id))) {
            return false;
        }
        $_SESSION[self::SESSION_KEY_GOALS]->push($goal->id);
        $goal->complete();

        return $goal;
    }

     /**
     * Auto Completes a goal by checking route regexp hit property of the model and setting its ID in the session.
     *
     * @param Request $route The goals name
     *
     * @return \PeterVincze\AbTesting\Models\Goal|false
     */
     public function autoCompleteGoal(Request $request)
     {
        $experiment = $this->getExperiment();
        if (empty($experiment)) {
            return;
        }
        $goals = $experiment->goals;
        foreach($goals as $goal) {
            $findGoal = Goal::find($goal->id);
            if (!empty($findGoal['autocompletegoal_route_regexp_pattern'])) {
                $matches = [];
                preg_match_all("/" . ($findGoal['autocompletegoal_route_regexp_pattern']) . "/",$request->getRequestUri(),$matches);
                if (!empty($matches[0])) {
                    $this->completeGoal($findGoal['name']);
                }
            }
        };
    }

    /**
     * Returns the currently active experiment.
     *
     * @return \PeterVincze\AbTesting\Models\Experiment|null
     */
    public function getExperiment()
    {
        if (!empty($_SESSION[self::SESSION_KEY_EXPERIMENT])) {
            $_SESSION[self::SESSION_KEY_EXPERIMENT] = Experiment::find($_SESSION[self::SESSION_KEY_EXPERIMENT]->id);
        }
        return $_SESSION[self::SESSION_KEY_EXPERIMENT] ?? null;
    }

    /**
     * Returns all the completed goals.
     *
     * @return \Illuminate\Support\Collection|false
     */
    public function getCompletedGoals()
    {
        if (empty($_SESSION[self::SESSION_KEY_GOALS])) {
            return false;
        }

        return $_SESSION[self::SESSION_KEY_GOALS]->map(function ($goalId) {
            return Goal::find($goalId);
        });

    }

    /**
     * Check if the current request is from a crawler or bot and config option is enabled.
     *
     * @return bool
     */
    public function isCrawler()
    {
        return config('ab-testing.ignore_crawlers')
        && (new CrawlerDetect)->isCrawler();
    }
}