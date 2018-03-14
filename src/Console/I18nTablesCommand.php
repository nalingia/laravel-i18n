<?php

namespace Nalingia\I18n\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class I18nTablesCommand extends Command {

  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = 'i18n:tables';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create migrations for catalogue and languages tables.';

  /**
   * The filesystem instance.
   *
   * @var \Illuminate\Filesystem\Filesystem
   */
  protected $_filesystem;

  /**
   * The Composer instance.
   *
   * @var \Illuminate\Support\Composer
   */
  protected $_composer;

  /**
   * I18nTablesCommand constructor.
   *
   * @param \Illuminate\Filesystem\Filesystem $filesystem
   * @param \Illuminate\Support\Composer $composer
   */
  public function __construct(Filesystem $filesystem, Composer $composer) {
    parent::__construct();

    $this->_filesystem = $filesystem;
    $this->_composer = $composer;
  }

  /**
   * Execute the console command.
   *
   * @return void
   */
  public function handle() {
    $languagesMigrationPath = $this->createBaseMigration('create_languages_table');
    $this->_filesystem->put($languagesMigrationPath, $this->_filesystem->get(__DIR__ . '/stubs/languages.stub'));

    $languagesMigrationPath = $this->createBaseMigration('create_catalogue_items_table');
    $this->_filesystem->put($languagesMigrationPath, $this->_filesystem->get(__DIR__ . '/stubs/catalogueItems.stub'));

    $this->info('Migrations created succesfully!');

    $this->_composer->dumpAutoloads();
  }

  /**
   * Execute the console command.
   *
   * @return void
   */
  public function fire() {
    $this->handle();
  }

  /**
   * Create a migration with the given name.
   *
   * @return string Migration path.
   */
  private function createBaseMigration($name) {
    $path = $this->getLaravel()->getDatabasePath() . '/migrations';
    return $this->getLaravel()['migration.creator']->create($name, $path);
  }
}