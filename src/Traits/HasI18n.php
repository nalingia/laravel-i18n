<?php

namespace Nalingia\I18n\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nalingia\I18n\Models\CatalogueItem;
use Nalingia\I18n\Exceptions\AttributeIsNonCatalogable;

trait HasI18n {

  public function catalogueItems() {
    return $this->morphMany(CatalogueItem::class, $this->getCatalogueMorphName());
  }

  public function getAttribute($key) {
    if (!$this->isCatalogueAttribute($key)) {
      return parent::getAttribute($key);
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
    $this->guardNonCatalogableAttribute($key);
    return $this->getCatalogueItem($key, $locale);
  }

  public function getCatalogueItem(string $key, string $locale, bool $useFallbackLocale = true) : ?string {
    $this->guardNonCatalogableAttribute($key);

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
    $this->guardNonCatalogableAttribute($key);

    if ($this->hasSetMutator($key)) {
      $method = 'set' . Str::studly($key) . 'Attribute';
      $value = $this->{$method}($value, $locale);
    }

    $item = $this->catalogueItems()
      ->updateOrCreate(
        ['key' => $key, $this->getLocaleIdentifier() => $locale ],
        ['value' => $value ]
      );

    $this->catalogueItems = $this->catalogueItems->reject(function ($item) use ($locale, $key) {
      return $item->{$this->getLocaleIdentifier()} == $locale && $item->key == $key;
    });

    $this->catalogueItems->push($item);

    return $this;
  }

  public function hasCatalogueItem(string $key, ?string $locale = null) : bool {
    $locale = $locale ?? $this->getLocale();

    return $this->catalogueItems
      ->where($this->getLocaleIdentifier(), $locale)
      ->keyBy('key')
      ->has($key);
  }

  public function forgetCatalogueItem(string $key, string $locale) : self {
    $this->catalogueItems()
      ->where('key', $key)
      ->where($this->getLocaleIdentifier(), $locale)
      ->delete();

    $this->catalogueItems = $this->catalogueItems->reject(function ($item) use ($locale, $key) {
      return $item->{$this->getLocaleIdentifier()} == $locale && $item->key == $key;
    });

    return $this;
  }

  public function forgetCatalogueItemsForLocale(string $locale) : self {
    $this->catalogueItems()
      ->where($this->getLocaleIdentifier(), $locale)
      ->delete();

    $this->catalogueItems = $this->catalogueItems->reject(function ($item) use ($locale) {
      return $item->{$this->getLocaleIdentifier()} == $locale;
    });

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

  public function getCatalogueItems(?string $key = null) : Collection {
    if (!is_null($key)) {
      $this->guardNonCatalogableAttribute($key);
    }

    return $this->catalogueItems
      ->toBase()
      ->when(!is_null($key), function ($items) use ($key) {
        return $items
          ->where('key', $key)
          ->mapWithKeys(function ($item) {
            return [
              $item->{$this->getLocaleIdentifier()} => $item->value,
            ];
          });
      })
      ->when(is_null($key), function ($items) {
        return $items
          ->groupBy('key')
          ->mapWithKeys(function ($items, $key) {
            return [
              $key => $items->mapWithKeys(function ($item) {
                return [
                  $item->{$this->getLocaleIdentifier()} => $item->value,
                ];
              }),
            ];
          });
      });
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

  public function getCatalogueLocalesForAttribute($key) : array {
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