An _opinionated_ Laravel package for models internationalisation
=======================

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nalingia/laravel-i18n.svg?style=flat-square)](https://packagist.org/packages/nalingia/laravel-i18n)
[![Build Status](https://travis-ci.com/nalingia/laravel-i18n.svg?branch=master)](https://travis-ci.com/nalingia/laravel-i18n)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/nalingia/laravel-i18n.svg?style=flat-square)](https://packagist.org/packages/nalingia/laravel-i18n)

I18n is an opinionated package to add internationalisation to a Laravel model.

## Installation
You can install the package via composer:
```bash
composer require nalingia/laravel-i18n
```

Laravel will discover the related service provider.

## Usage
This package comes with a _ready-to-use_ migration to enable your model to be internationalised. To create the migration run
```bash
 php artisan i18n:table
```
and then
```bash
 php artisan migrate
```

It has a minimum configuration available. You can publish using
```bash
 php artisan vendor:publish --provider="Nalingia\I18n\I18nServiceProvider" --tag="config"
```

To enable internationalisation in your models, follow these simple steps:
1. Import `Nalingia\I18n\Traits\HasI18n` trait into you model.
2. Add a public property named `$catalogueAttributes`: it will contains all attributes that will be translated.
3. Add `'catalogueItems'` to model's `$with` array when using Lavavel 5.6 or below. Starting from Laravel 5.7 imported
traits can initialize everything needed when a model is booting. HasI18n can append `'catalogueItems'` to `$with` and `$hidden` arrays.

Here's an set up example:
 ```php
use \Nalingia\I18n\Traits\HasI18n;

class Article extends Model {
    use HasI18n;
    
    public $catalogueAttributes = [
      'title',
      'abstract',
      'content',
    ];
}
 ```
 
### Translations management
There are several way to access property localisations but the easiest one is related to the current application locale:
```php
$article->title
```
You can also use this method to access a translation:
```php
public function getCatalogueItem(string $attribute, string $locale) : string
```
#### Get a catalogue item
Accessing translation for current application locale is as easy as accessing a model attribute:
```php
$article->title
// or
$article->abstract
```
If you want to access translation for a different locale, you can call `translate(string $key, string $locale)`:
```php
$article->translate('title', 'it')
```
or
```php
$article->getCatalogueItem('title', 'it')
```

#### Retrieve all catalogue items
You can get all available catalogue items for a model by calling `getCatalogueItems()` without providing any argument:
```php
$article->getCatalogueItems()
```
Or you can use the accessor
```php
$article->translations
```

#### Retrieve all available locales
You can get all available locale for which exist at least one catalogue item by calling `getCatalogueLocales()`:
```php
$article
  ->setCatalogueItem('title', 'en', 'English title')
  ->setCatalogueItem('abstract', 'en', 'English abstract')
  ->setCatalogueItem('title', 'it', 'Italian title')
  ->setCatalogueItem('abstract', 'it', 'Italian abstract')
  ->setCatalogueItem('abstract', 'de', 'German abstract');
  
$article->getCatalogueLocales(); // ['it', 'en', 'de']
```

#### Setting a catalogue item
Setting translation for current application locale is as easy as setting a model's property:
```php
$article->title = 'Super cool title';
// or
$article->abstract = 'Exciting abstract...';
```

If you want to translate in locales different to the application one you can call `setCatalogueItem(string $key, string $locale, $value)`:
```php
$article
  ->setCatalogueItem('title', 'en', 'English title')
  ->setCatalogueItem('abstract', 'en', 'English abstract')
  ->setCatalogueItem('title', 'it', 'Italian title')
  ->setCatalogueItem('abstract', 'it', 'Italian abstract');
```

#### Remove a catalotue item
You can delete a translation for a specific field:
```php
public function forgetCatalogueItem(string $key, string $locale);
```
Or, you can delete all translation for a locale:
```php
public function forgetCatalogueItemsForLocale(string $locale);
```

### Creating models
You can set catalogue items when creating new model
```php
$article = Article::create([
    'title' => [
        'en' => 'English title',
        'it' => 'Italian title',
    ],
    'abstract' => [
        'en' => 'English abstract',
        'it' => 'Italian abstract',
    ],
]);
```
or you can set only catalogue items that match the current application locale by setting the attribute value without providing any locale key. For example:
```php
// app()->getLocale() == 'de'

$article = Article::create([
    'title' => 'German title'
    'abstract' => 'German abstract',
]);
```
is the same as
```php
$article = Article::create([
    'title' => [
        'de' => 'German title',
    ],
    'abstract' => [
        'de' => 'German abstract',
    ],
]);
```

## Change log
Please, see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Testing
You can run the tests with:
```php
composer test
```
or
```php
vendor/bin/phpunit
``` 

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.