# Changelog

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