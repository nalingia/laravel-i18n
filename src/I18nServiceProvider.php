<?php

namespace Nalingia\I18n;

use Illuminate\Support\ServiceProvider;
use Nalingia\I18n\Console\I18nTablesCommand;

class I18nServiceProvider extends ServiceProvider {

  /**
   * Register the application services.
   *
   * @return void
   */
  public function register() {
    $this->commands([
      I18nTablesCommand::class
    ]);
  }
}