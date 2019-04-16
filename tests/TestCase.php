<?php

namespace Nalingia\I18n\Tests;

use CreateCatalogueItemsTable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nalingia\I18n\I18nServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra {

  protected function setUp(): void {
    parent::setUp();

    $this->setUpDatabase();
  }

  protected function getPackageProviders($app) {
    return  [
      I18nServiceProvider::class,
    ];
  }

  protected function setUpDatabase() {
    Schema::create('test_models', function (Blueprint $table) {
      $table->increments('id');
      $table->string('field_one');
    });

    include_once __DIR__ . '/../database/migrations/create_catalogue_items_table.php.stub';
    (new CreateCatalogueItemsTable())->up();
  }
}