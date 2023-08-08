<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Orchestra\Testbench\Factories\UserFactory;
use Tests\Feature\Fixtures\Book;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = UserFactory::new()->create();

        Book::create([
            'title' => 'test-book-title',
            'user_id' => $user->id,
        ]);
    }
}
