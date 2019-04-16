<?php

namespace Nalingia\I18n\Tests;

use Nalingia\I18n\Exceptions\AttributeIsNonCatalogable;

class I18nTest extends TestCase {

  /** @var \Nalingia\I18n\Tests\TestModel */
  protected $testModel;

  protected function setUp(): void {
    parent::setUp();
    $this->testModel = TestModel::create([
      'field_one' => 'Hello, world!',
    ]);
  }

  /** @test */
  public function it_should_return_fallback_locale_item_when_getting_unknown_locale() {
    $this->app['config']->set('app.fallback_locale', 'en');
    $this->testModel->setCatalogueItem('title', 'en', 'This is test');

    $this->assertSame('This is test', $this->testModel->getCatalogueItem('title', 'it'));
  }

  /** @test */
  public function it_should_return_an_empty_value_when_getting_unknown_locale_without_fallback_locale() {
    $this->app['config']->set('app.fallback_locale', 'en');
    $this->testModel->setCatalogueItem('title', 'en', 'This is test');

    $this->assertSame('', $this->testModel->getCatalogueItem('title', 'it', false));
  }

  /** @test */
  public function it_should_return_translation_for_the_application_locale() {
    $this->app['config']->set('app.locale', 'en');
    $this->testModel->setCatalogueItem('title', 'en', 'This is a test');

    $this->assertSame('This is a test', $this->testModel->title);
  }

  /** @test */
  public function it_can_save_multiple_catalogue_items() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.');

    $this->assertSame('This is an english text.', $this->testModel->title);
    $this->assertSame('This is an italian text.', $this->testModel->getCatalogueItem('title', 'it'));
  }

  /** @test */
  public function it_should_get_all_locales_for_which_a_translation_exists() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.');

    $this->assertSame(['en', 'it'], $this->testModel->getCatalogueLocalesForAttribute('title'));
  }

  /** @test */
  public function it_should_get_all_translations_for_attribute() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.');

    $this->assertSame([
      'en' => 'This is an english text.',
      'it' => 'This is an italian text.',
    ], $this->testModel->getCatalogueItems('title')->toArray());
  }

  /** @test */
  public function it_should_get_all_available_translations_for_all_catalogable_attributes() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.')
      ->setCatalogueItem('field_two', 'en', 'This is an english field two text.')
      ->setCatalogueItem('field_two', 'it', 'This is an italian field two text.');

    $this->assertSame([
      'title' => [
        'en' => 'This is an english text.',
        'it' => 'This is an italian text.',
      ],
      'field_two' => [
        'en' => 'This is an english field two text.',
        'it' => 'This is an italian field two text.',
      ],
    ], $this->testModel->getCatalogueItems()->toArray());
  }

  /** @test */
  public function it_should_forget_a_catalogue_item() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.');

    $this->assertSame([
      'en' => 'This is an english text.',
      'it' => 'This is an italian text.',
    ], $this->testModel->getCatalogueItems('title')->toArray());

    $this->testModel->forgetCatalogueItem('title', 'en');

    $this->assertSame([
      'it' => 'This is an italian text.',
    ], $this->testModel->getCatalogueItems('title')->toArray());
  }

  /** @test */
  public function it_should_forget_all_items_for_locale() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.')
      ->setCatalogueItem('field_two', 'en', 'This is an english field two text.')
      ->setCatalogueItem('field_two', 'it', 'This is an italian field two text.');

    $this->assertSame([
      'title' => [
        'en' => 'This is an english text.',
        'it' => 'This is an italian text.',
      ],
      'field_two' => [
        'en' => 'This is an english field two text.',
        'it' => 'This is an italian field two text.',
      ],
    ], $this->testModel->getCatalogueItems()->toArray());

    $this->testModel->forgetCatalogueItemsForLocale('it');

    $this->assertSame([
      'title' => [
        'en' => 'This is an english text.',
      ],
      'field_two' => [
        'en' => 'This is an english field two text.',
      ],
    ], $this->testModel->getCatalogueItems()->toArray());
  }

  /** @test */
  public function it_should_throw_an_exception_when_accessing_non_catalogue_attribute() {
    $this->expectException(AttributeIsNonCatalogable::class);

    $this->testModel->translate('fake_title');
  }

  /** @test */
  public function it_can_save_a_catalogue_item_via_attribute_for_application_locale() {
    $this->testModel->title = 'This is an english text.';

    $this->assertSame('This is an english text.', $this->testModel->translate('title'));
  }

  /** @test */
  public function it_can_check_if_an_attribute_has_a_catalogue_item() {
    $this->testModel->title = 'This is an english text.';

    $this->assertTrue($this->testModel->hasCatalogueItem('title'));
  }

  /** @test */
  public function it_can_check_if_an_attribute_is_a_catalogue_item() {
    $this->assertTrue($this->testModel->isCatalogueAttribute('title'));
  }

  /** @test */
  public function it_can_set_a_field_when_a_mutator_is_defined() {
    $testModel = (new class() extends TestModel {
      public function setTitleAttribute($value) {
        return "Mutated {$value}";
      }
    });

    $testModel->field_one = 'Test';
    $testModel->save();

    $testModel->title = 'hi!';

    $this->assertSame('Mutated hi!', $testModel->title);
  }

  /** @test */
  public function it_can_set_catalogue_item_for_default_locale() {
    $this->testModel
      ->setCatalogueItem('title', 'en', 'This is an english text.')
      ->setCatalogueItem('title', 'it', 'This is an italian text.');

    app()->setLocale('en');
    $this->testModel->title = 'Updated english text.';

    $this->assertSame('Updated english text.', $this->testModel->title);
    $this->assertSame('This is an italian text.', $this->testModel->translate('title', 'it'));

    app()->setLocale('it');
    $this->testModel->title = 'Updated italian text.';

    $this->assertSame('Updated italian text.', $this->testModel->title);
    $this->assertSame('Updated english text.', $this->testModel->translate('title', 'en'));
  }

  /** @test */
  public function it_should_return_empty_string_when_accessing_non_existing_catalogue_field() {
    $this->testModel->setCatalogueItem('title', 'it', 'This is an italian text.');

    $this->assertSame('', $this->testModel->title);
  }
}