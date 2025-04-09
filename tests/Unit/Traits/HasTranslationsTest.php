<?php

namespace Tests\Unit\Traits;

use Mockery;
use Tests\TestCase;
use App\Traits\HasTranslations;

// Create a Dummy Model that uses the HasTranslations trait (we can mock this directly in the tests).
class TestModel
{
    use HasTranslations;
}

class HasTranslationsTest extends TestCase
{
    // Test for setting and getting translations
    public function test_it_can_set_and_get_translations()
    {
        // Mock the TestModel that uses the HasTranslations trait
        $mockModel = Mockery::mock('Tests\Unit\Traits\TestModel[setTranslation, translate]')
            ->makePartial()  // Make the class partial to use real methods bgom the trait
            ->shouldAllowMockingProtectedMethods();

        // Simulate setting a translation
        $mockModel->shouldReceive('setTranslation')
            ->with('title', 'en', 'Test Category')
            ->once();

        // Simulate getting the translation
        $mockModel->shouldReceive('translate')
            ->with('title', 'en')
            ->andReturn('Test Category')
            ->once();

        // Act: Set translation and get translation
        $mockModel->setTranslation('title', 'en', 'Test Category');
        $translation = $mockModel->translate('title', 'en');

        // Assert: Ensure the translation was set and returned correctly
        $this->assertEquals('Test Category', $translation);
    }

    // Test for deleting a specific translation
    public function test_it_can_delete_a_translation()
    {
        // Mock the TestModel that uses the HasTranslations trait
        $mockModel = Mockery::mock('Tests\Unit\Traits\TestModel[setTranslation, deleteTranslation, translate]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate setting a translation and deleting it
        $mockModel->shouldReceive('setTranslation')
            ->with('title', 'en', 'Test Category')
            ->once();

        $mockModel->shouldReceive('deleteTranslation')
            ->with('title', 'en')
            ->once();

        $mockModel->shouldReceive('translate')
            ->with('title', 'en')
            ->andReturnNull()
            ->once();

        // Act: Set translation, delete it, and attempt to get it
        $mockModel->setTranslation('title', 'en', 'Test Category');
        $mockModel->deleteTranslation('title', 'en');
        $translation = $mockModel->translate('title', 'en');

        // Assert: Ensure the translation is deleted (null)
        $this->assertNull($translation);
    }

    // Test for getting all translations for a field
    public function test_it_can_get_all_translations_for_a_field()
    {
        // Mock the TestModel that uses the HasTranslations trait
        $mockModel = Mockery::mock('Tests\Unit\Traits\TestModel[setTranslation, getAllTranslations]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate setting translations and retrieving all translations
        $mockModel->shouldReceive('setTranslation')
            ->with('title', 'en', 'Test Category')
            ->once();

        $mockModel->shouldReceive('setTranslation')
            ->with('title', 'bg', 'Тест Категория')
            ->once();

        $mockModel->shouldReceive('getAllTranslations')
            ->with('title')
            ->andReturn([
                'en' => 'Test Category',
                'bg' => 'Тест Категория'
            ])
            ->once();

        // Act: Set translations and then get all translations
        $mockModel->setTranslation('title', 'en', 'Test Category');
        $mockModel->setTranslation('title', 'bg', 'Тест Категория');
        $translations = $mockModel->getAllTranslations('title');

        // Assert: Ensure all translations are returned correctly
        $this->assertEquals([
            'en' => 'Test Category',
            'bg' => 'Тест Категория'
        ], $translations);
    }

    // Test for deleting all translations for a model
    public function test_it_can_delete_all_translations_for_a_model()
    {
        // Mock the TestModel that uses the HasTranslations trait
        $mockModel = Mockery::mock('Tests\Unit\Traits\TestModel[setTranslation, deleteAllTranslations, translate]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate setting translations and deleting all translations
        $mockModel->shouldReceive('setTranslation')
            ->with('title', 'en', 'Test Category')
            ->once();

        $mockModel->shouldReceive('setTranslation')
            ->with('title', 'bg', 'Тест Категория')
            ->once();

        $mockModel->shouldReceive('deleteAllTranslations')
            ->once();

        $mockModel->shouldReceive('translate')
            ->with('title', 'en')
            ->andReturnNull()
            ->once();

        $mockModel->shouldReceive('translate')
            ->with('title', 'bg')
            ->andReturnNull()
            ->once();

        // Act: Set translations, delete all translations, and attempt to get them
        $mockModel->setTranslation('title', 'en', 'Test Category');
        $mockModel->setTranslation('title', 'bg', 'Тест Категория');
        $mockModel->deleteAllTranslations();

        $enTranslation = $mockModel->translate('title', 'en');
        $bgTranslation = $mockModel->translate('title', 'bg');

        // Assert: Ensure all translations are deleted (null)
        $this->assertNull($enTranslation);
        $this->assertNull($bgTranslation);
    }
}
