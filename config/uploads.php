<?php

return [
    'avatar' => [
        'max_kilobytes' => 10 * 1024,
        'max_megabytes' => 10,
        'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'size_message' => 'Ảnh đại diện không được vượt quá 10 MB.',
        'format_message' => 'Ảnh đại diện chỉ hỗ trợ JPG, JPEG, PNG hoặc WEBP.',
        'processing_message' => 'Không thể xử lý ảnh đại diện. Vui lòng chọn ảnh JPG, JPEG, PNG hoặc WEBP hợp lệ.',
    ],
];
