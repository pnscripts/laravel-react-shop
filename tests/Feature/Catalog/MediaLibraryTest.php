<?php

namespace Tests\Feature\Catalog;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use PnShop\Catalog\Filament\Resources\Products\Pages\EditProduct;
use PnShop\Catalog\Models\Product;
use PnShop\Media\Filament\Resources\Media\Pages\ListMedia;
use PnShop\Media\MediaLibrary;
use PnShop\Media\Models\Media;
use Tests\Feature\Admin\AdminTestCase;

class MediaLibraryTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_registering_a_file_records_it_and_creates_webp_conversions(): void
    {
        Storage::disk('public')->put('media/2026/10/lamp.png', UploadedFile::fake()->image('lamp.png', 1200, 900)->getContent());

        $media = app(MediaLibrary::class)->register('media/2026/10/lamp.png', 'Lamp.png')->fresh();

        $this->assertSame([1200, 900], [$media->width, $media->height]);
        $this->assertSame('image/png', $media->mime_type);
        $this->assertSame(['thumb', 'medium', 'large'], $media->conversions);
        $this->assertSame(1200, getimagesizefromstring((string) Storage::disk('public')->get('media/2026/10/conversions/lamp-large.webp'))[0], 'Images are never enlarged.');
        Storage::disk('public')->assertExists('media/2026/10/conversions/lamp-thumb.webp');
        Storage::disk('public')->assertExists('media/2026/10/conversions/lamp-medium.webp');
        $this->assertSame('/storage/media/2026/10/conversions/lamp-thumb.webp 320w, /storage/media/2026/10/conversions/lamp-medium.webp 800w', $media->srcset(), 'srcset skips sizes wider than the original.');
        $this->assertStringEndsWith('lamp-large.webp', $media->url('large'));

        $this->assertSame($media->id, app(MediaLibrary::class)->register('media/2026/10/lamp.png')->id, 'Registering twice reuses the record.');
    }

    public function test_deleting_media_removes_its_files(): void
    {
        Storage::disk('public')->put('media/x/photo.jpg', UploadedFile::fake()->image('photo.jpg', 400, 400)->getContent());
        $media = app(MediaLibrary::class)->register('media/x/photo.jpg')->fresh();

        $media->delete();

        Storage::disk('public')->assertMissing('media/x/photo.jpg');
        Storage::disk('public')->assertMissing('media/x/conversions/photo-thumb.webp');
    }

    public function test_images_can_be_uploaded_in_the_media_library(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ListMedia::class)
            ->callAction('upload', ['files' => [UploadedFile::fake()->image('chair.jpg', 800, 600)]])
            ->assertHasNoActionErrors();

        $this->assertSame(1, Media::query()->count());
        $this->assertSame(800, Media::query()->sole()->width);
    }

    public function test_svg_and_other_files_are_rejected(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ListMedia::class)
            ->callAction('upload', ['files' => [UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')]])
            ->assertHasActionErrors();

        $this->assertSame(0, Media::query()->count());
    }

    public function test_a_product_gallery_is_saved_in_order_and_shown_in_the_store(): void
    {
        $this->actingAsAdministrator();
        $product = Product::factory()->active()->create(['image' => null]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->set('data.gallery', [UploadedFile::fake()->image('front.jpg', 1000, 1000), UploadedFile::fake()->image('back.jpg', 1000, 1000)])
            ->call('save')
            ->assertHasNoFormErrors();

        $gallery = $product->fresh()->mediaIn('gallery');
        $this->assertCount(2, $gallery);
        $this->assertSame(0, $gallery[0]->getRelationValue('pivot')->position);

        $this->get("/shop/{$product->slug}")->assertInertia(fn (Assert $page) => $page
            ->has('product.gallery', 2)
            ->where('product.image.id', $gallery[0]->id)
            ->where('product.image.thumb', fn (string $url) => str_ends_with($url, '-thumb.webp'))
        );

        $this->get('/shop')->assertInertia(fn (Assert $page) => $page->where('products.data.0.image.id', $gallery[0]->id));
    }

    public function test_listing_pages_do_not_query_per_image(): void
    {
        $count = function (): int {
            $this->get('/shop');
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/shop');
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        $add = function (int $products): void {
            foreach (range(1, $products) as $i) {
                Storage::disk('public')->put("media/p{$i}-".uniqid().'.jpg', UploadedFile::fake()->image('p.jpg', 50, 50)->getContent());
            }

            foreach (Storage::disk('public')->files('media') as $path) {
                $media = app(MediaLibrary::class)->register($path);
                if ($media->wasRecentlyCreated) {
                    Product::factory()->active()->create()->syncMediaCollection('gallery', [$media->id]);
                }
            }
        };

        $add(2);
        $few = $count();
        $add(6);

        $this->assertSame($few, $count());
    }

    public function test_the_media_library_needs_its_permission(): void
    {
        $this->actingAsStaff(role: 'catalog-manager');

        $this->get('/admin/media')->assertForbidden();
    }
}
