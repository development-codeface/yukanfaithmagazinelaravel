<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Annual Faith Conference 2026',
                'subtitle' => 'Convention Center - May 30, 2026',
                'description' => 'Join us for our annual faith conference featuring inspiring speakers and workshops.',
                'image' => 'uploads/banners/1770176316.jpg',
                'button_link' => 'https://events.example.com/faith-conference-2026',
                'status' => 1,
            ],
            [
                'title' => 'Community Wellness Day',
                'subtitle' => 'City Park - May 15, 2026',
                'description' => 'A day dedicated to physical, mental, and spiritual wellness for all community members.',
                'image' => 'uploads/banners/1770176636.jpeg',
                'button_link' => 'https://events.example.com/wellness-day',
                'status' => 1,
            ],
            [
                'title' => 'Magazine Launch Party',
                'subtitle' => 'Grand Ballroom - May 10, 2026',
                'description' => 'Celebrate the launch of our latest magazine issue with exclusive previews and networking.',
                'image' => 'uploads/banners/1770176650.jpeg',
                'button_link' => 'https://events.example.com/magazine-launch',
                'status' => 1,
            ],
            [
                'title' => 'Youth Leadership Workshop',
                'subtitle' => 'Training Center - June 15, 2026',
                'description' => 'Empowering young leaders to make a positive impact in their communities.',
                'image' => 'uploads/banners/1770176650.jpeg',
                'button_link' => 'https://events.example.com/youth-leadership',
                'status' => 1,
            ],
            [
                'title' => 'Spiritual Retreat Weekend',
                'subtitle' => 'Mountain Retreat - June 30, 2026',
                'description' => 'A weekend of reflection, meditation, and spiritual growth in a peaceful setting.',
                'image' => 'uploads/banners/1770176663.jpeg',
                'button_link' => 'https://events.example.com/spiritual-retreat',
                'status' => 1,
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                ['title' => $event['title']],
                $event
            );
        }
    }
}
