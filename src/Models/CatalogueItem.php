<?php

namespace Nalingia\I18n\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogueItem extends Model {

  protected $table = 'catalogue_items';

  protected $fillable = [
    'key',
    'value',
    'lang'
  ];

  protected $guarded = [
    'id',
    'catalogable_id',
    'catalogable_type',
    'created_at',
    'updated_at'
  ];

  protected $hidden = [
    'id',
    'catalogable_id',
    'catalogable_type'
  ];

  public function catalogable() {
    return $this->morphTo();
  }

  public function language() {
    return $this->hasOne(Language::class, 'id', 'lang');
  }

  public function __toString() {
    return $this->value;
  }

}