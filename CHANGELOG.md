# Changelog

## 1.1.3 2019/05/10
- Added `initializeHasI18n` method to add `catalogueItems` relation to `$with` and `$hidden` array when booting new model.

## 1.1.2 2019/05/09
- Fixed an issue when translating models which are not persisted in database yet. Now you can ask, set and remove translations also when the model
does not exist.
```php
$instance = new Model;
$instance->title = 'Translated title';
$instance->save();
```
- Fixed an issue when assigning array with translations to catalogue attributes.
```php
$instance = new Model;
$instance->title = [
  'en' => 'English title',
  'it' => 'Italian title',
];
$instance->save();
```

## 1.1.0 2019/05/08
- Added API to create models with catalogue items using `Model::create` static method.
```php
$instance = Model::create([
    'title' => [
      'en' => 'English title',
      'it' => 'Titolo italiano',
    ],
    'order' => 1,
]);
```
- Update README.md file documentation.

## 1.0.0 - 2019/04/16
- Initial release.