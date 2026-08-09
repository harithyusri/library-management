<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Library;
use App\Models\Publisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Libraries ────────────────────────────────────────────────
        $libraries = [
            [
                'name'           => 'Perpustakaan Kuala Lumpur',
                'address'        => '1 Jalan Raja Laut, Kuala Lumpur, 50350',
                'phone'          => '+60-3-2000-1000',
                'email'          => 'kl@perpustakaan.gov.my',
                'opening_hours'  => 'Mon–Fri 08:00–21:00 | Sat–Sun 09:00–18:00',
                'latitude'       => 3.14920,
                'longitude'      => 101.70060,
                'is_active'      => true,
                'max_borrow_limit' => 10,
            ],
            [
                'name'           => 'Perpustakaan Petaling Jaya',
                'address'        => '45 Jalan SS2/24, Petaling Jaya, 47300',
                'phone'          => '+60-3-7800-2000',
                'email'          => 'pj@perpustakaan.gov.my',
                'opening_hours'  => 'Mon–Fri 09:00–20:00 | Sat 09:00–17:00',
                'latitude'       => 3.10730,
                'longitude'      => 101.60650,
                'is_active'      => true,
                'max_borrow_limit' => 7,
            ],
            [
                'name'           => 'Perpustakaan Shah Alam',
                'address'        => '12 Persiaran Makmur, Shah Alam, 40150',
                'phone'          => '+60-3-5500-3000',
                'email'          => 'shahalam@perpustakaan.gov.my',
                'opening_hours'  => 'Mon–Fri 09:00–19:00 | Sat 10:00–16:00',
                'latitude'       => 3.07380,
                'longitude'      => 101.51860,
                'is_active'      => true,
                'max_borrow_limit' => 7,
            ],
        ];

        $createdLibraries = [];
        foreach ($libraries as $data) {
            $createdLibraries[] = Library::firstOrCreate(['name' => $data['name']], $data);
        }

        [$central, $pj, $shah] = $createdLibraries;

        // ── 2. Departments ───────────────────────────────────────────────
        $departments = [
            // KL
            ['library_id' => $central->id, 'name' => 'Lending Services',       'code' => 'LS-KL',  'description' => 'Manages book loans, returns, and renewals.'],
            ['library_id' => $central->id, 'name' => 'Reference & Research',   'code' => 'RR-KL',  'description' => 'Provides reference assistance and research support.'],
            ['library_id' => $central->id, 'name' => 'Children & Youth',       'code' => 'CY-KL',  'description' => 'Programmes and collections for children and teenagers.'],
            ['library_id' => $central->id, 'name' => 'Digital Resources',      'code' => 'DR-KL',  'description' => 'Manages e-books, audiobooks, and digital subscriptions.'],
            ['library_id' => $central->id, 'name' => 'Archives & Special Collections', 'code' => 'AS-KL', 'description' => 'Preserves rare books and archival materials.'],
            // PJ
            ['library_id' => $pj->id,      'name' => 'Lending Services',       'code' => 'LS-PJ',  'description' => 'Manages book loans, returns, and renewals.'],
            ['library_id' => $pj->id,      'name' => 'Children & Youth',       'code' => 'CY-PJ',  'description' => 'Programmes and collections for children and teenagers.'],
            ['library_id' => $pj->id,      'name' => 'Community Programmes',   'code' => 'CP-PJ',  'description' => 'Organises community events and reading programmes.'],
            // Shah Alam
            ['library_id' => $shah->id,    'name' => 'Lending Services',       'code' => 'LS-SA',  'description' => 'Manages book loans, returns, and renewals.'],
            ['library_id' => $shah->id,    'name' => 'Science & Technology',   'code' => 'ST-SA',  'description' => 'Curates STEM and technical resources.'],
            ['library_id' => $shah->id,    'name' => 'Community Programmes',   'code' => 'CP-SA',  'description' => 'Organises community events and reading programmes.'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        // ── 3. Publishers ────────────────────────────────────────────────
        $publishers = [
            ['name' => 'Penguin Books',        'country' => 'United Kingdom'],
            ['name' => 'HarperCollins',        'country' => 'United States'],
            ['name' => 'Bloomsbury Publishing','country' => 'United Kingdom'],
            ['name' => 'Farrar, Straus & Giroux', 'country' => 'United States'],
            ['name' => 'Vintage Books',        'country' => 'United States'],
            ['name' => 'Oxford University Press', 'country' => 'United Kingdom'],
            ['name' => 'Dewan Bahasa & Pustaka', 'country' => 'Malaysia'],
        ];

        $publisherMap = [];
        foreach ($publishers as $pub) {
            // library_id null = shared across all libraries (not scoped)
            $publisherMap[$pub['name']] = Publisher::firstOrCreate(
                ['name' => $pub['name']],
                array_merge($pub, ['library_id' => null])
            );
        }

        // ── 4. Categories ────────────────────────────────────────────────
        $categories = [
            ['name' => 'Fiction',            'code' => 'FIC', 'slug' => 'fiction'],
            ['name' => 'Non-Fiction',         'code' => 'NF',  'slug' => 'non-fiction'],
            ['name' => 'Science & Technology','code' => 'SCI', 'slug' => 'science-technology'],
            ['name' => 'History',             'code' => 'HIS', 'slug' => 'history'],
            ['name' => 'Philosophy',          'code' => 'PHI', 'slug' => 'philosophy'],
            ['name' => 'Children',            'code' => 'CHI', 'slug' => 'children'],
            ['name' => 'Biography',           'code' => 'BIO', 'slug' => 'biography'],
            ['name' => 'Self-Help',           'code' => 'SH',  'slug' => 'self-help'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat['name']] = Category::firstOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['library_id' => null])
            );
        }

        // ── 5. Genres ────────────────────────────────────────────────────
        $genres = [
            'Literary Fiction', 'Mystery', 'Thriller', 'Science Fiction',
            'Fantasy', 'Historical Fiction', 'Romance', 'Horror',
            'Biography', 'Memoir', 'Popular Science', 'Philosophy',
            'Self-Help', 'Travel', 'Poetry', 'Graphic Novel',
        ];

        $genreMap = [];
        foreach ($genres as $name) {
            $genreMap[$name] = Genre::firstOrCreate(
                ['name' => $name],
                ['library_id' => null]
            );
        }

        // ── 6. Books ─────────────────────────────────────────────────────
        // Each entry: title, author, isbn, category, publisher, year, format, pages, language, description, genres[], copies_per_library
        $booksData = [
            [
                'title'       => 'To Kill a Mockingbird',
                'author_name' => 'Harper Lee',
                'isbn'        => '978-0-06-112008-4',
                'category'    => 'Fiction',
                'publisher'   => 'HarperCollins',
                'published_year' => 1960,
                'format'      => 'paperback',
                'pages'       => 281,
                'language'    => 'English',
                'description' => 'A novel about racial injustice and moral growth in the American South.',
                'genres'      => ['Literary Fiction', 'Historical Fiction'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => '1984',
                'author_name' => 'George Orwell',
                'isbn'        => '978-0-45-228423-4',
                'category'    => 'Fiction',
                'publisher'   => 'Penguin Books',
                'published_year' => 1949,
                'format'      => 'paperback',
                'pages'       => 328,
                'language'    => 'English',
                'description' => 'A dystopian novel set in a totalitarian society under constant surveillance.',
                'genres'      => ['Literary Fiction', 'Science Fiction'],
                'copies'      => [4, 2, 2],
            ],
            [
                'title'       => 'The Great Gatsby',
                'author_name' => 'F. Scott Fitzgerald',
                'isbn'        => '978-0-74-327356-5',
                'category'    => 'Fiction',
                'publisher'   => 'Vintage Books',
                'published_year' => 1925,
                'format'      => 'paperback',
                'pages'       => 180,
                'language'    => 'English',
                'description' => 'A portrait of the Jazz Age and the American Dream through the eyes of Nick Carraway.',
                'genres'      => ['Literary Fiction'],
                'copies'      => [3, 1, 1],
            ],
            [
                'title'       => 'Sapiens: A Brief History of Humankind',
                'author_name' => 'Yuval Noah Harari',
                'isbn'        => '978-0-06-231609-7',
                'category'    => 'Non-Fiction',
                'publisher'   => 'HarperCollins',
                'published_year' => 2011,
                'format'      => 'hardcover',
                'pages'       => 443,
                'language'    => 'English',
                'description' => 'A sweeping narrative of human history from the Stone Age to the modern era.',
                'genres'      => ['Popular Science', 'Biography'],
                'copies'      => [4, 3, 2],
            ],
            [
                'title'       => 'A Brief History of Time',
                'author_name' => 'Stephen Hawking',
                'isbn'        => '978-0-55-305340-1',
                'category'    => 'Science & Technology',
                'publisher'   => 'Vintage Books',
                'published_year' => 1988,
                'format'      => 'paperback',
                'pages'       => 212,
                'language'    => 'English',
                'description' => 'An exploration of cosmology, black holes, and the nature of time.',
                'genres'      => ['Popular Science'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'Meditations',
                'author_name' => 'Marcus Aurelius',
                'isbn'        => '978-0-14-044140-6',
                'category'    => 'Philosophy',
                'publisher'   => 'Penguin Books',
                'published_year' => 180,
                'format'      => 'paperback',
                'pages'       => 254,
                'language'    => 'English',
                'description' => 'Personal writings of the Roman Emperor on Stoic philosophy.',
                'genres'      => ['Philosophy'],
                'copies'      => [3, 2, 1],
            ],
            [
                'title'       => 'The Hitchhiker\'s Guide to the Galaxy',
                'author_name' => 'Douglas Adams',
                'isbn'        => '978-0-34-539180-3',
                'category'    => 'Fiction',
                'publisher'   => 'Vintage Books',
                'published_year' => 1979,
                'format'      => 'paperback',
                'pages'       => 193,
                'language'    => 'English',
                'description' => 'A comedic science fiction adventure through the universe.',
                'genres'      => ['Science Fiction'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'Pride and Prejudice',
                'author_name' => 'Jane Austen',
                'isbn'        => '978-0-14-143951-8',
                'category'    => 'Fiction',
                'publisher'   => 'Penguin Books',
                'published_year' => 1813,
                'format'      => 'paperback',
                'pages'       => 432,
                'language'    => 'English',
                'description' => 'A romantic novel following Elizabeth Bennet and Mr. Darcy.',
                'genres'      => ['Literary Fiction', 'Romance'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'The Selfish Gene',
                'author_name' => 'Richard Dawkins',
                'isbn'        => '978-0-19-929114-4',
                'category'    => 'Science & Technology',
                'publisher'   => 'Oxford University Press',
                'published_year' => 1976,
                'format'      => 'paperback',
                'pages'       => 360,
                'language'    => 'English',
                'description' => 'A landmark work on evolutionary biology and the gene-centred view of evolution.',
                'genres'      => ['Popular Science'],
                'copies'      => [2, 2, 1],
            ],
            [
                'title'       => 'Thinking, Fast and Slow',
                'author_name' => 'Daniel Kahneman',
                'isbn'        => '978-0-37-453355-7',
                'category'    => 'Self-Help',
                'publisher'   => 'Farrar, Straus & Giroux',
                'published_year' => 2011,
                'format'      => 'hardcover',
                'pages'       => 499,
                'language'    => 'English',
                'description' => 'An exploration of the two systems that drive the way we think.',
                'genres'      => ['Self-Help', 'Popular Science'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'The Diary of a Young Girl',
                'author_name' => 'Anne Frank',
                'isbn'        => '978-0-55-329698-7',
                'category'    => 'Biography',
                'publisher'   => 'Vintage Books',
                'published_year' => 1947,
                'format'      => 'paperback',
                'pages'       => 283,
                'language'    => 'English',
                'description' => 'The wartime diary of Anne Frank, written while in hiding during the Nazi occupation.',
                'genres'      => ['Memoir', 'Biography'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'Harry Potter and the Philosopher\'s Stone',
                'author_name' => 'J.K. Rowling',
                'isbn'        => '978-0-74-754473-2',
                'category'    => 'Children',
                'publisher'   => 'Bloomsbury Publishing',
                'published_year' => 1997,
                'format'      => 'paperback',
                'pages'       => 223,
                'language'    => 'English',
                'description' => 'A young boy discovers he is a wizard and begins his education at Hogwarts.',
                'genres'      => ['Fantasy'],
                'copies'      => [5, 3, 3],
            ],
            [
                'title'       => 'The Alchemist',
                'author_name' => 'Paulo Coelho',
                'isbn'        => '978-0-06-231500-7',
                'category'    => 'Fiction',
                'publisher'   => 'HarperCollins',
                'published_year' => 1988,
                'format'      => 'paperback',
                'pages'       => 197,
                'language'    => 'English',
                'description' => 'A philosophical novel about a young shepherd\'s journey to find treasure.',
                'genres'      => ['Literary Fiction'],
                'copies'      => [4, 2, 2],
            ],
            [
                'title'       => 'Dune',
                'author_name' => 'Frank Herbert',
                'isbn'        => '978-0-44-101359-7',
                'category'    => 'Fiction',
                'publisher'   => 'Vintage Books',
                'published_year' => 1965,
                'format'      => 'paperback',
                'pages'       => 688,
                'language'    => 'English',
                'description' => 'An epic science fiction saga set on the desert planet Arrakis.',
                'genres'      => ['Science Fiction', 'Fantasy'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'The Name of the Wind',
                'author_name' => 'Patrick Rothfuss',
                'isbn'        => '978-0-75-640407-1',
                'category'    => 'Fiction',
                'publisher'   => 'Vintage Books',
                'published_year' => 2007,
                'format'      => 'paperback',
                'pages'       => 662,
                'language'    => 'English',
                'description' => 'The first day of the autobiography of Kvothe, a legendary wizard.',
                'genres'      => ['Fantasy'],
                'copies'      => [2, 2, 1],
            ],
            [
                'title'       => 'Educated',
                'author_name' => 'Tara Westover',
                'isbn'        => '978-0-39-959050-4',
                'category'    => 'Biography',
                'publisher'   => 'Vintage Books',
                'published_year' => 2018,
                'format'      => 'hardcover',
                'pages'       => 334,
                'language'    => 'English',
                'description' => 'A memoir about a woman who grows up in a survivalist family and educates herself.',
                'genres'      => ['Memoir', 'Biography'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'The Subtle Art of Not Giving a F*ck',
                'author_name' => 'Mark Manson',
                'isbn'        => '978-0-06-245771-7',
                'category'    => 'Self-Help',
                'publisher'   => 'HarperCollins',
                'published_year' => 2016,
                'format'      => 'paperback',
                'pages'       => 224,
                'language'    => 'English',
                'description' => 'A counterintuitive approach to living a good life.',
                'genres'      => ['Self-Help'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'Gone Girl',
                'author_name' => 'Gillian Flynn',
                'isbn'        => '978-0-30-758836-4',
                'category'    => 'Fiction',
                'publisher'   => 'Vintage Books',
                'published_year' => 2012,
                'format'      => 'paperback',
                'pages'       => 422,
                'language'    => 'English',
                'description' => 'A psychological thriller about a marriage gone terribly wrong.',
                'genres'      => ['Mystery', 'Thriller'],
                'copies'      => [3, 2, 2],
            ],
            [
                'title'       => 'The Guns of August',
                'author_name' => 'Barbara W. Tuchman',
                'isbn'        => '978-0-34-538623-6',
                'category'    => 'History',
                'publisher'   => 'Vintage Books',
                'published_year' => 1962,
                'format'      => 'paperback',
                'pages'       => 511,
                'language'    => 'English',
                'description' => 'A Pulitzer Prize-winning account of the opening weeks of World War I.',
                'genres'      => ['Historical Fiction'],
                'copies'      => [2, 1, 1],
            ],
            [
                'title'       => 'Atomic Habits',
                'author_name' => 'James Clear',
                'isbn'        => '978-0-73-521129-2',
                'category'    => 'Self-Help',
                'publisher'   => 'Penguin Books',
                'published_year' => 2018,
                'format'      => 'hardcover',
                'pages'       => 320,
                'language'    => 'English',
                'description' => 'A practical guide to building good habits and breaking bad ones.',
                'genres'      => ['Self-Help'],
                'copies'      => [4, 3, 2],
            ],
        ];

        $libraryList = [$central, $pj, $shah];

        foreach ($booksData as $index => $data) {
            $category  = $categoryMap[$data['category']];
            $publisher = $publisherMap[$data['publisher']];

            // Distribute book ownership evenly across libraries
            $owningLibrary = $libraryList[$index % count($libraryList)];

            // Books are owned by the library with the most copies (KL)
            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'library_id'     => $owningLibrary->id,
                    'title'          => $data['title'],
                    'author_name'    => $data['author_name'],
                    'category_id'    => $category->id,
                    'publisher_id'   => $publisher->id,
                    'published_year' => $data['published_year'],
                    'format'         => $data['format'],
                    'pages'          => $data['pages'],
                    'language'       => $data['language'],
                    'description'    => $data['description'],
                ]
            );

            // Sync genres
            $genreIds = collect($data['genres'])->map(fn ($g) => $genreMap[$g]->id)->all();
            $book->genres()->syncWithoutDetaching($genreIds);

            // Create exact number of copies per library, keyed by library+book to avoid duplicates
            foreach ($libraryList as $index => $library) {
                $count = $data['copies'][$index] ?? 0;
                $existing = BookCopy::where('book_id', $book->id)
                    ->where('library_id', $library->id)
                    ->count();
                $toCreate = max(0, $count - $existing);
                for ($i = 0; $i < $toCreate; $i++) {
                    BookCopy::create([
                        'book_id'          => $book->id,
                        'library_id'       => $library->id,
                        'barcode'          => Str::uuid()->toString(),
                        'condition'        => 'good',
                        'status'           => 'available',
                        'acquisition_date' => now()->subDays(rand(30, 730))->toDateString(),
                    ]);
                }
            }
        }

        // ── 7. Assign staff to Central library ────────────────────────
        \App\Models\Staff::whereNull('library_id')->update(['library_id' => $central->id]);

        $this->command->info('✅ Libraries, departments, and books seeded successfully!');
    }
}
