<?php

namespace Nalingia\I18n\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nalingia\I18n\Exceptions\AttributeIsNonCatalogable;

trait HasI18n {

  public function catalogueItems() {
    return $this->morphMany('Nalingia\I18n\Models\CatalogueItems', $this->getCatalogueMorphName());
  }

  public function getAttributeValue($key) {
    if (!$this->isCatalogueAttribute($key)) {
      return parent::getAttributeValue($key);
    }

    return $this->getCatalogueItem($key, $this->getLocale());
  }

  public function setAttribute($key, $value) {
    if (!$this->isCatalogueAttribute($key)) {
      return parent::setAttribute($key, $value);
    }

    return $this->setCatalogueItem($key, $this->getLocale(), $value);
  }

  public function getCatalogueAttributes() : array {
    return is_array($this->catalogueAttributes)
      ? $this->catalogueAttributes
      : [];
  }

  public function translate(string $key, string $locale = '') : ?string {
    return $this->getCatalogueItem($key, $locale);
  }

  public function getCatalogueItem(string $key, string $locale, bool $useFallbackLocale = true) : ?string {
    $locale = $this->getNormalisedLocale($key, $locale, $useFallbackLocale);

    $catalogueItem = (string) $this->catalogueItems
      ->first(function ($item) use ($key, $locale) {
        return $item->key == $key && $item->{$this->getLocaleIdentifier()} == $locale;
      });

    if ($this->hasGetMutator($key)) {
      return $this->mutateAttribute($key, $catalogueItem);
    }

    return $catalogueItem;
  }

  public function setCatalogueItem(string $key, string $locale, $value) : self {
    if ($this->hasSetMutator($key)) {
      $method = 'set' . Str::studly($key) . 'Attribute';
      $this->{$method}($value, $locale);

      $value = $this->attributes[$key];
    }

    $this->catalogueItems()
      ->updateOrCreate(
        ['key' => $key, $this->getLocaleIdentifier() => $locale ],
        ['value' => $value ]
      );

    return $this;
  }

  public function hasCatalogueItem(string $key, ?string $locale) : bool {
    $locale = $locale ?? $this->getLocale();

    return $this->catalogueItems
      ->where($this->getLocaleIdentifier(), $locale)
      ->has($key);
  }

  public function forgetCatalogueItem(string $key, string $locale) : self {
    $this->catalogueItems()
      ->where('key', $key)
      ->where($this->getLocaleIdentifier(), $locale)
      ->delete();

    return $this;
  }

  public function forgetAllCatalogueItems(string $locale) : self {
    $this->catalogueItems()
      ->where($this->getLocaleIdentifier(), $locale)
      ->delete();

    return $this;
  }

  public function isCatalogueAttribute(string $key) : bool {
    return in_array($key, $this->getCatalogueAttributes());
  }

  public function getTranslationsAttribute() {
    return $this->catalogueItems
      ->groupBy('key')
      ->mapWithKeys(function ($items) {
        $key = optional($items->first())->key;
        return [
          $key => $items->mapWithKeys(function ($item) {
            return [ $item->{$this->getLocaleIdentifier()} => $item->value ];
          })->toArray(),
        ];
      });
  }

  public function getCatalogueItems(string $key = null) : Collection {
    return $this->catalogueItems
      ->when(!is_null($key), function ($items) use ($key) {
        return $items->where('key', $key);
      })
      ->toBase();
  }

  protected function getCatalogueMorphName() {
    if (!isset($this->morphCatalogueName)) {
      return config('i18n.catalogable_morph_name');
    }

    return $this->morphCatalogueName;
  }

  protected function guardNonCatalogableAttribute($key) {
    if (!$this->isCatalogueAttribute($key)) {
      throw AttributeIsNonCatalogable::make($key, $this);
    }
  }

  protected function getNormalisedLocale($key, $locale, bool $useFallbackLocale): string {
    if (in_array($locale, $this->getCatalogueLocalesForAttribute($key))) {
      return $locale;
    }

    if (!$useFallbackLocale) {
      return $locale;
    }

    if (!is_null($fallbackLocale = config('app.fallback_locale'))) {
      return $fallbackLocale;
    }

    return $locale;
  }

  protected function getCatalogueLocalesForAttribute($key) : array {
    return $this->catalogueItems
      ->where('key', $key)
      ->pluck('lang')
      ->unique()
      ->toArray();
  }

  protected function getLocaleIdentifier() : string {
    return config('i18n.locale_identifier');
  }

  protected function getLocale() : string {
    return config('app.locale');
  }

}