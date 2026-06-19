<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SEO Configuration
    |--------------------------------------------------------------------------
    */
    'title' => env('SEO_TITLE', 'IMG - International Mattress Gallery'),
    'description' => env('SEO_DESCRIPTION', 'Toko kasur dan perlengkapan tidur terpercaya. Temukan kasur premium, springbed, bantal, dan aksesori tidur berkualitas di International Mattress Gallery.'),
    'keywords' => env('SEO_KEYWORDS', 'kasur premium, springbed, perlengkapan tidur, bantal, aksesori tidur, mattress gallery, IMG, kasur jakarta'),
    'author' => env('SEO_AUTHOR', 'IMG International Mattress Gallery'),
    'og_image' => env('SEO_OG_IMAGE', 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800'),
    'robots' => env('SEO_ROBOTS', 'index, follow'),


    /*
    |--------------------------------------------------------------------------
    | Business Profile Details (for structured data)
    |--------------------------------------------------------------------------
    | Helps search engines and LLMs understand physical locations and store info.
    */
    'business' => [
        'type' => 'FurnitureStore', // E-commerce mattress store fits FurnitureStore/Store
        'name' => 'IMG International Mattress Gallery',
        'telephone' => env('BUSINESS_PHONE', '+62-812-3456-7890'),
        'email' => env('BUSINESS_EMAIL', 'halo@img.id'),
        'price_range' => '$$$',
        'logo' => env('BUSINESS_LOGO', '/favicon.ico'),
        'image' => env('BUSINESS_IMAGE', 'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=80&w=1200&h=800'),
        'address' => [
            'street' => 'Jl. Tidur Nyenyak No. 99',
            'locality' => 'Jakarta Selatan',
            'region' => 'DKI Jakarta',
            'postal_code' => '12345',
            'country' => 'ID',
        ],
        'opening_hours' => 'Mo-Su 10:00-22:00',
        'social_links' => [
            'https://www.facebook.com/img.mattress',
            'https://www.instagram.com/img.mattress',
            'https://twitter.com/img.mattress',
        ],
    ]
];
