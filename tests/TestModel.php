<?php

namespace Nalingia\I18n\Tests;

use Illuminate\Database\Eloquent\Model;
use Nalingia\I18n\Traits\HasI18n;

class TestModel extends Model {

  use HasI18n;

  protected $table = 'test_models';

  protected $guarded = [];

  public $timestamps = false;

  public $catalogueAttributes = [
    'title', 'field_two',
  ];
}