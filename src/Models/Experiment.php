<?php

namespace PeterVincze\AbTesting\Models;

use PeterVincze\AbTesting\AbTestingFacade;
use Illuminate\Database\Eloquent\Model;
use PeterVincze\AbTesting\Events\ExperimentNewVisitor;

class Experiment extends Model
{
    protected $table = 'ab_experiments';

    protected $fillable = [
        'name',
        'visitors',
        'git_repo',
        'git_checkout',
        'deploy_script'
    ];

    protected $casts = [
        'visitors' => 'integer',
    ];

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function visit()
    {
        if (AbTestingFacade::isCrawler()) {
            return;
        }
        $this->increment('visitors');
        event(new ExperimentNewVisitor($this));
    }
}
