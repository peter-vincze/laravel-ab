<h6 align="center">
    <img src="https://i.ibb.co/hy7fjMG/Laravel-AB.png" width="300"/>
</h6>

<p align="center"><a href="https://github.com/peter-vincze/laravel-ab/releases"><img src="https://camo.githubusercontent.com/c3ce61db6a98f1a6d141a4fc3b3f83c182674ba8/68747470733a2f2f696d672e736869656c64732e696f2f6769746875622f72656c656173652f62656e3138322f6c61726176656c2d61622e7376673f7374796c653d666c61742d737175617265" alt="Latest Version" data-canonical-src="https://img.shields.io/github/release/peter-vincze/laravel-ab.svg?style=flat-square" style="max-width:100%;"></a>
<a href="https://travis-ci.org/peter-vincze/laravel-ab" rel="nofollow"><img src="https://camo.githubusercontent.com/7994c56ad88fb3e839360835571cc670d88af2e2/68747470733a2f2f696d672e736869656c64732e696f2f7472617669732f62656e3138322f6c61726176656c2d61622f6d61737465722e7376673f7374796c653d666c61742d737175617265" alt="Build Status" data-canonical-src="https://img.shields.io/travis/peter-vincze/laravel-ab/master.svg?style=flat-square" style="max-width:100%;"></a>
</p>

This package helps you to find out which content works on your site and which doesn't.

It allows you to create experiments and goals. The visitor will receive randomly the next experiment and you can customize your site to that experiment. The view and the goal conversion will be tracked and you can view the results in a report. It can work as a git repo based way, 
to be able to get separate codes for separate abtest experiments, to be able to easier maintain the code back to the master code in git revisioning when ab test goals are over.

## Installation

This package can be used in Laravel 8 or higher.

You can install the package via composer:

```bash
composer require peter-vincze/laravel-ab
```

## Config

After installation publish the config file.use the command above.

```bash
php artisan vendor:publish --provider="PeterVincze\AbTesting\AbTestingServiceProvider"
```
If you write a config as to checkout branch(es)/commit(s) from repo what config experiment git_repo 
field specify into abtesting/{experiment->id} directory under base_path() is going to happen, with
composer install and npm update and npm run development. If you make a "ab-testing-deploy-script"
directory under base_path() in the branch/commit the process going to search for the config
of the per experiment based deploy_script field filename in that directory, and going to run it,
and finally after deleting the .git directory under all abtesting/** directories to remove the 
revision system by reason of performance.
If you change config.php you should run this script every time to synchronize
changes, do not forget if an experiment name is the same as before, by running this command, it 
will lost its goals and visits. You can autocomplete goals by a regexp pattern of request uri what you can specity also in the config under the goal, the field name is autocompletegoal_route_regexp_pattern, if a goal is not a complicated logic, or if you can organize your logic around a route regexp pattern. This means once an ab stat is ready, and we can make a choice from them, than you can only cherry pick a commit on top of master, without any code cleaning.

```bash
php artisan ab:config
```

You can define your experiments and goals in there.

Finally, run the newly added migration

```bash
php artisan migrate
```

Two new migrations should be added.

## Usage

### Experiments

```html
@if (AbTesting::isExperiment('logo-big'))

    <div class="logo-big"></div>

@elseif (AbTesting::isExperiment('logo-grayscale'))

    <div class="logo-greyscale"></div>

@elseif (AbTesting::isExperiment('brand-name'))

    <h1>Brand name</h1>

@endif
```

That's the most basic usage of the package. You don't have to initialize anything. The package handles everything for you if you call `isExperiment`

Alternatively you can use a custom blade if statement:

```html
@ab('logo-big')

    <div class="logo-big"></div>

@elseab('logo-grayscale')

    <div class="logo-greyscale"></div>

@elseab('brand-name')

    <h1>Brand name</h1>

@endab
```

This will work exactly the same way.

If you don't want to make any continual rendering you can call

```php
AbTesting::pageView()
```

directly and trigger a new page view with a random experiment. This function will also be called from `isExperiment`.

Under the hood a new session item will keep track of the current experiment. A session will only get one experiment and only trigger one page view.

You can grab the current experiment with:

```php
// get the underlying model
AbTesting::getExperiment()

// get the experiment name
AbTesting::getExperiment()->name

// get the visitor count
AbTesting::getExperiment()->visitors
```

Alternatively there is a request helper for you:

```php
public function index(Request $request) {
    // the same as 'AbTesting::getExperiment()'
    $request->abExperiment()
}
```

### Goals

To complete a goal simply call:

```php
AbTesting::completeGoal('signup')
```

The function will increment the conversion of the goal assigned to the active experiment. If there isn't an active experiment running for the session one will be created. You can only trigger a goal conversion once per session. This will be prevented with another session item. The function returns the underlying goal model.

To get all completed goals for the current session:

```php
AbTesting::getCompletedGoals()
```

### Bots and crawlers

The package can try to ignore bots and crawlers from registering pageviews. Just enable the `ignore_crawlers` option in the config.

### Report

To get a report of the page views, completed goals and conversion call the report command:

```bash
php artisan ab:report
```

This prints something like this:

```
+---------------+----------+-------------+
| Experiment    | Visitors | Goal signup |
+---------------+----------+-------------+
| big-logo      | 2        | 1 (50%)     |
| small-buttons | 1        | 0 (0%)      |
+---------------+----------+-------------+
```

### Reset

To reset all your visitors and goal completions call the reset command:

```bash
php artisan ab:reset
```

### Events

In addition you can hook into two events:

- `ExperimentNewVisitor` gets triggered once an experiment gets assigned to a new visitor. You can grab the experiment as a property of the event.
- `GoalCompleted` gets triggered once a goal is completed. You can grab the goal as a property of the event.

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email vicnzepetertamas@gmail.com instead of using the issue tracker.

## Credits

- [Peter Vincze](https://github.com/peter-vincze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
