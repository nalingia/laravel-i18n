# Changelog

## 1.1.1 2019/05/09
- Fixed an issue when translating models which are not persisted in database yet. Now you can ask, set and remove translations also when the model
does not exist.
```php
$instance = new Model;
$instance->title = 'Translated title';
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