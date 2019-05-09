<?php

namespace Nalingia\I18n\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nalingia\I18n\Models\CatalogueItem;
use Nalingia\I18n\Exceptions\AttributeIsNonCatalogable;

trait HasI18n {

  private $cataloguePool = [];

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

    if (is_array($value)) {
      $first = null;
      foreach ($value as $locale => $v) {
        $locale = is_numeric($locale)
          ? $this->getLocale()
          : $locale;

        $item = $this->setCatalogueItem($key, $locale, $v);

        if (!$first) {
          $first = $item;
        }
      }

      return $first;
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

    $catalogueItem = !$this->exists
      ? Arr::get($this->cataloguePool, "{$key}.{$locale}")
      : (string) $this->catalogueItems
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

    if (!$this->exists) {
      Arr::set($this->cataloguePool, "{$key}.{$locale}", $value);
    } else {
      $item = $this->catalogueItems()
        ->updateOrCreate(
          ['key' => $key, $this->getLocaleIdentifier() => $locale ],
          ['value' => $value ]
        );

      $this->catalogueItems = $this->catalogueItems->reject(function ($item) use ($locale, $key) {
        return $item->{$this->getLocaleIdentifier()} == $locale && $item->key == $key;
      });

      $this->catalogueItems->push($item);
    }

    return $this;
  }

  public function hasCatalogueItem(string $key, ?string $locale = null) : bool {
    $locale = $locale ?? $this->getLocale();

    return !$this->exists
      ? Arr::has($this->cataloguePool, "{$key}.{$locale}")
      : $this->catalogueItems
        ->where($this->getLocaleIdentifier(), $locale)
        ->keyBy('key')
        ->has($key);
  }

  public function forgetCatalogueItem(string $key, string $locale) : self {
    if (!$this->exists) {
      Arr::forget($this->cataloguePool, "{$key}.{$locale}");
      return $this;
    }

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

    if (!$this->exists) {
      foreach ($this->cataloguePool as $attribute => &$translations) {
        Arr::forget($translations, $locale);
      }
      return $this;
    }

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
    return !$this->exists
      ? collect($this->cataloguePool)
      : $this->catalogueItems
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

    return !$this->exists
      ? (is_null($key) ? collect($this->cataloguePool) : collect(Arr::get($this->cataloguePool, $key)))
      : $this->catalogueItems
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

  /**
   * {@inheritdoc}
   */
  public static function create(array $attributes = []) {
    $instance = new static;

    $catalogue = collect($instance->getCatalogueAttributes())
      ->mapWithKeys(function ($attribute) use (&$attributes) {
        return [ $attribute => Arr::pull($attributes, $attribute) ];
      })
      ->filter(function ($translations) {
        return !is_null($translations);
      });

    $instance->fill($attributes);
    $instance->save();

    $items = $instance->saveTranslationsArray($catalogue);
    return $instance->setRelation('catalogueItems', $items);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $options = []) {
    $saved = parent::save($options);

    if ($saved && !empty($this->cataloguePool)) {
      $items = $this->saveTranslationsArray($this->cataloguePool);
      $this->setRelation('catalogueItems', $items);
      $this->cataloguePool = [];
    }

    return $saved;
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

  private function saveTranslationsArray($attributes) : EloquentCollection {
    $items = new EloquentCollection;

    collect($attributes)
      ->each(function ($translations, $attribute) use (&$items) {
        collect($translations)
          ->each(function ($value, $locale) use ($attribute, &$items) {
            $locale = is_numeric($locale) ? $this->getLocale() : $locale;
            $item = $this->catalogueItems()
              ->create([
                'key' => $attribute,
                $this->getLocaleIdentifier() => $locale ,
                'value' => $value
              ]);
            $items->add($item);
          });
      });

    return $items;
  }
}