<?php
/**
 * Author: Xavier Au
 * Date: 15/6/2017
 * Time: 12:24 PM
 */

namespace Anacreation\PageComposer;


use Illuminate\Support\ServiceProvider;

class PageComposerServiceProvider extends ServiceProvider
{
    public function boot() {
        $this->publishes([
            __DIR__ . '/config/PageComposer.php' => config_path('PageComposer.php'),
        ]);
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__ . '/config/PageComposer.php', 'PageComposer');
    }
}