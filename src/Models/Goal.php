<?php

namespace PeterVincze\AbTesting\Models;

use PeterVincze\AbTesting\AbTestingFacade;
use Illuminate\Database\Eloquent\Model;
use PeterVincze\AbTesting\Events\GoalCompleted;

class Goal extends Model
{
    protected $table = 'ab_goals';

    protected $fillable = [
        'name',
        'hit',
        'autocompletegoal_route_regexp_pattern',
        'goal_once_a_session'
    ];

    protected $casts = ['hit' => 'integer'];

    public function experiment()
    {
        return $this->belongsTo(Experiment::class);
    }

    public function complete()
    {
        if (AbTestingFacade::isCrawler()) {
            return;
        }

        $this->increment('hit');
        event(new GoalCompleted($this));
    }
}
