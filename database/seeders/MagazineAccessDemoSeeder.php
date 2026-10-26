<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\MagazineIssue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MagazineAccessDemoSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::orderBy('id')->firstOrFail();

        DB::transaction(function () use ($author) {
            $category = Category::whereRaw('LOWER(category_name) = ?', ['past magazines'])->first()
                ?? Category::create(['category_name' => 'Past Magazines', 'description' => 'Magazine previews and back issues.']);

            $dates = ['2025-10-01', '2026-07-01', '2026-09-01', '2026-10-01'];
            foreach ($dates as $date) {
                $month = \Carbon\Carbon::parse($date)->format('F Y');
                $issue = MagazineIssue::firstOrCreate(['title' => '[TEST] Faith Magazine - '.$month], [
                    'issue_date' => $date,
                    'published_at' => $date.' 09:00:00',
                    'description' => 'Sample '.$month.' issue for testing subscription coverage and permanent single purchases. Contains three sample articles.',
                    'price' => 49,
                    // A clearly labelled destination for checking authorised redirects.
                    'pdf_url' => 'https://example.com/?test-magazine='.$date,
                ]);

                foreach (['Finding Hope in Everyday Life', 'Building a Caring Community', 'A Moment of Reflection'] as $index => $topic) {
                    $article = Article::firstOrCreate([
                        'title' => '[TEST] '.$month.' - '.$topic,
                    ], [
                        'category_id' => $category->id,
                        'author_id' => $author->id,
                        'issue_id' => $issue->id,
                        'summary' => 'Sample article '.($index + 1).' from the '.$month.' test magazine. Open this preview to check the magazine Buy Now flow.',
                        'content' => '<p>This is sample content for <strong>'.$topic.'</strong> in the '.$month.' issue.</p><p>Use this issue to verify subscription dates, magazine access, and single purchases. This article is test data and can be removed after testing.</p>',
                        'access_type' => $index === 0 ? 'free' : 'paid',
                        'status' => 'published',
                        'published_at' => $date.' 09:00:00',
                    ]);
                    $article->categories()->syncWithoutDetaching([$category->id]);
                }

                $this->command?->info('Test issue #'.$issue->id.': '.$issue->title.' | '.$date.' | '.$issue->articles()->count().' articles');
            }
        });
    }
}
