<?php

namespace Nalingia\I18n\Exceptions;

use Illuminate\Database\Eloquent\Model;
use \Exception;

class AttributeIsNonCatalogable extends Exception {

  public static function make(string $key, Model $model) : self {
    $catalogueAttributes = implode(', ', $model->getCatalogueAttributes());
    return new static("Attribute `{$key}` cannot be translated because it is not one of the catalogue attributes: {$catalogueAttributes}.");
  }
}