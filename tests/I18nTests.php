<?php

namespace Nalingia\I18n\Tests;

class I18nTests extends TestCase {

  /** @var \Nalingia\I18n\Tests\TestModel */
  protected $testModel;

  protected function setUp(): void {
    parent::setUp();

    $this->testModel = new TestModel();
  }

  /** @test */
  public function it_should_return_fallback_locale_item_when_getting_unknown_locale() {
    $this->app['config']->set('app.fallback_locale', 'en');
    $this->testModel->setCatalogueItem('title', 'en', 'This is  test');

    $this->assertSame('This is  test', $this->testModel->getCatalogueItem('title', 'it'));
  }

  /** @test */
  public function it_should_return_an_empty_value_when_getting_unknown_locale_without_fallback_locale() {
    $this->app['config']->set('app.fallback_locale', 'en');
    $this->testModel->setCatalogueItem('title', 'en', 'This is  test');

    $this->assertSame('', $this->testModel->getCatalogueItem('title', 'it', false));
  }
}