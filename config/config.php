<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Experiments
    |--------------------------------------------------------------------------
    |
    | A list of experiment identifiers.
    |
    | Example for config
    |        'experiments' => ['buy_button_green' => ['git_repo' => './.git', 
    |         'git_checkout' => 'experiments/buy_button_green', 'deploy_script' => 'deploy.sh'],
    |         'buy_button_red'   => ['git_repo' => './.git', 'git_checkout' => 'experiments/buy_button_red', 
    |         'deploy_script' => '']],
    |
    | if you want the normal working process, just leave git_checkout deploy_script and git_repo fields empty
    */
    'experiments' => [],

    /*
    |--------------------------------------------------------------------------
    | Goals
    |--------------------------------------------------------------------------
    |
    | A list of goals.
    |
    | Example: ['buy' =>['autocompletegoal_route_regexp_pattern' => '\/buy', 
    | 'goal_once_a_session' => 1]]
    | 
    |  If you want autocomplete a goal by checking the request uri matches against regular 
    |  expression | the autocompletegoal_route_regexp_pattern field going to do the job for you
    | 
    |  There may be cases if a goal should count in a session more than once, in that cases the 
    |  goal_once_a_session field should be 1
    |
    */
    'goals' => [],
    /*
    |--------------------------------------------------------------------------
    | Ignore Crawlers
    |--------------------------------------------------------------------------
    |
    | Ignore pageviews for crawlers.
    |
    */
    'ignore_crawlers' => true,
];
