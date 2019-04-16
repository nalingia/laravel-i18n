<?php

namespace Nalingia\I18n;

use Illuminate\Support\ServiceProvider;
use Nalingia\I18n\Console\I18nTableCommand;

class I18nServiceProvider extends ServiceProvider {

  public function boot() {
    $this->publishes([
      __DIR__ . '/../config/i18n.php' => config_path('i18n.php'),
    ], 'config');

    $this->mergeConfigFrom(__DIR__.'/../config/i18n.php', 'i18n');
  }
  /**
   * Register the application services.
   *
   * @return void
   */
  public function register() {
    $this->commands([
      I18nTableCommand::class
    ]);
  }
}