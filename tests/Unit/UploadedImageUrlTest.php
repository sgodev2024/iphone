<?php

namespace Tests\Unit;

use Tests\TestCase;

class UploadedImageUrlTest extends TestCase
{
    public function test_uploaded_images_are_rendered_with_storage_helper(): void
    {
        $views = [
            resource_path('views/admin/Importproduct/add.blade.php'),
            resource_path('views/Themes/pages/Inventory/add.blade.php'),
            resource_path('views/Themes/admin/configuration/config.blade.php'),
        ];

        foreach ($views as $view) {
            $contents = file_get_contents($view);

            $this->assertStringNotContainsString("asset('storage/' .", $contents, $view);
            $this->assertStringNotContainsString('asset($item->images[0]->image_path)', $contents, $view);
            $this->assertStringNotContainsString('asset($data->logo)', $contents, $view);
        }
    }
}
