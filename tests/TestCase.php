<?php

namespace Nalingia\I18n\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra {

  protected function setUp(): void {
    parent::setUp();

    $this->setUpDatabase();
  }

  protected function setUpDatabase() {
    Schema::create('test_models', function (Blueprint $table) {
      $table->increments('id');
      $table->string('field_one');
    });

    Schema::create('catalogue_items', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->string('key', 255);
      $table->text('value');
      $table->string('lang', 5);
      $table->morphs('catalogable');
      $table->timestamps();
    });
  }
}