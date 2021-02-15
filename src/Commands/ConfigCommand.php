<?php

namespace PeterVincze\AbTesting\Commands;

use Illuminate\Console\Command;
use PeterVincze\AbTesting\Models\Goal;
use PeterVincze\AbTesting\Models\Experiment;
use PeterVincze\AbTesting\Exceptions\InvalidConfiguration;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ab:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating the git checkout based abtesting directory structure with laravel deploy and git checkout based deploy and database seed if not exists based on config';

    /**
     * Reserved for file system operations
     * 
     */
    private $fs;

    /**
     * Primry key of Experiment when config starts
     * 
     */
    private $revertExperimentId;

    /**
     * Primry key of Goal when config starts
     * 
     */
    private $revertGoalId;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->output = new ConsoleOutput();
        $this->fs = new FileSystem;
    }

    /*
     * Run a Symfony Process and check error output and if exists throw an exception
     *
     */
    private function processRun(Process $process) {
        try {
            $process->setTimeout(180);
            $process->mustRun();
        }
        catch (ProcessFailedException $exception) {
            $this->revert();
            throw new \Exception($exception->getMessage());
        }
        $this->info($process->getOutput());
        $this->info($process->getErrorOutput());
    }

    /*
     * If an exception thrown in the process reverting changes back to origin
     *
     */
    private function revert() {
        if ($this->fs->isDirectory(base_path() . '/ab-testing')) {
            $deleteAbTestingDirectory = true;
            foreach($this->fs->directories(base_path() . '/ab-testing') as $key => $value) {
                $experimentId = mb_substr($value, mb_strrpos($value, '/') + 1) + 0;
                if ($experimentId > $this->revertExperimentId) {
                    $this->fs->deleteDirectory(base_path() . '/ab-testing/' . $experimentId);
                }
                else {
                    $deleteAbTestingDirectory = false;
                }
            }
            if ($deleteAbTestingDirectory) {
                $this->fs->deleteDirectory(base_path() . '/ab-testing');
            }
        }
        Experiment::where('id', '>', $this->revertExperimentId)->delete();
        Goal::whereRaw('id', '>', $this->revertGoalId)->delete();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (mb_strpos(__DIR__, '/ab-testing/') === false) {
            $config = config('ab-testing');
            $info = '';
            if (!empty($config['experiments']) && !empty($config['goals'])) {
                $this->revertExperimentId = Experiment::max('id');
                $this->revertgoalId = Goal::max('id');
                $configExperiments = $config['experiments'];
                $configGoals = $config['goals'];
                $process = new Process(['...']);
                $process->setWorkingDirectory(base_path());

                foreach ($configExperiments as $configExperimentKey => $configExperiment) {
                    if (!isset($configExperiment['git_repo'])) {
                        throw InvalidConfiguration::experiment();
                    }
                    if (!isset($configExperiment['git_checkout'])) {
                        throw InvalidConfiguration::experiment();
                    }

                    Experiment::where(['name' => $configExperimentKey])->delete();
                    $experiment = Experiment::firstOrCreate([
                    'name' => $configExperimentKey],
                    ['git_checkout' => $configExperiment['git_checkout'],
                    'visitors' => 0,
                    'git_repo' => $configExperiment['git_repo'],
                    'deploy_script' => $configExperiment['deploy_script']]);
                    foreach ($configGoals as $configGoalKey => $configGoal) {

                        if (!isset($configGoal['autocompletegoal_route_regexp_pattern'])) {
                           throw InvalidConfiguration::goal();
                        }
                        if (!isset($configGoal['goal_once_a_session'])) {
                           throw InvalidConfiguration::goal();
                        }
                        $experiment->goals()->where(['name' => $configGoalKey])->delete();
                        $experiment->goals()->firstOrCreate([
                            'name' => $configGoalKey],  
                        ['autocompletegoal_route_regexp_pattern' => $configGoal['autocompletegoal_route_regexp_pattern'],
                        'goal_once_a_session' => $configGoal['goal_once_a_session'],
                        'hit' => 0]);
                    }

                    if (!empty($experiment->git_checkout) && !empty($experiment->git_repo)) {
                        if (!$this->fs->isDirectory(base_path() . '/ab-testing')) {
                            $this->fs->makeDirectory(base_path() . '/ab-testing');
                            $this->info('Directory "ab-testing" created succesfully.');
                        }

                        $process = Process::fromShellCommandLine('git clone ' . $experiment->git_repo . ' ab-testing/' . $experiment->id, base_path());
                        $this->processRun($process);

                        $process = Process::fromShellCommandLine('git checkout -f '. $experiment->git_checkout, base_path() .'/ab-testing/' . $experiment->id);
                        $this->processRun($process);

                        $process = Process::fromShellCommandLine('composer update', base_path() .'/ab-testing/' . $experiment->id);
                        $this->processRun($process);

                        $process = Process::fromShellCommandLine('npm install', base_path() .'/ab-testing/' . $experiment->id);
                        $this->processRun($process);

                        $process = Process::fromShellCommandLine('npm run dev', base_path() .'/ab-testing/' . $experiment->id);
                        $this->processRun($process);

                        if ($this->fs->exists(base_path() . '/ab-testing/' . $experiment->id . '/ab-testing-deploy-script/' . $experiment->deploy_script)) {
                            $process = Process::fromShellCommandLine('./ab-testing-deploy-script/' .$experiment->deploy_script,'ab-testing/' . $experiment->id);
                            $this->processRun($process);

                        }
                        $this->fs->deleteDirectory(base_path() .'/ab-testing/' . $experiment->id . '/.git');
                        $this->info('Git repository for "ab-testing/' . $experiment->id . '" has been deleted successfully');
                    }
                }
            }
            else {
                $info.= ' - but no experiments or goals set';
            }
            $this->info('Config Successfully created' . $info);
        }
    }
}
