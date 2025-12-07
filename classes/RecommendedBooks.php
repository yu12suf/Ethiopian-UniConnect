<?php

/**
 * RecommendedBooks Class - Fetches recommended books from web APIs
 * Demonstrates: API integration, JSON parsing, external data fetching
 */

class RecommendedBooks
{
    private $apiUrl = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * Fetch recommended books based on category
     */
    public function getRecommendedBooks($category = 'self-help', $maxResults = 10, $startIndex = 0)
    {
        // For best-selling books, use a broader search
        if ($category === 'bestsellers') {
            $query = urlencode('bestselling books');
            $url = $this->apiUrl . "?q={$query}&orderBy=relevance&maxResults={$maxResults}&startIndex={$startIndex}&printType=books";
        } else {
            $query = urlencode($category . ' best books');
            $url = $this->apiUrl . "?q=subject:{$category}&orderBy=relevance&maxResults={$maxResults}&startIndex={$startIndex}&printType=books";
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10 second timeout
                'user_agent' => 'UniConnect/1.0'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            // Fallback to hardcoded books if API fails
            return $this->getFallbackBooks($category, $startIndex, $maxResults);
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['items'])) {
            return $this->getFallbackBooks($category, $startIndex, $maxResults);
        }

        $books = [];
        foreach ($data['items'] as $item) {
            $volumeInfo = $item['volumeInfo'] ?? [];
            $accessInfo = $item['accessInfo'] ?? [];

            $book = [
                'id' => $item['id'] ?? '',
                'title' => $volumeInfo['title'] ?? 'Unknown Title',
                'authors' => $volumeInfo['authors'] ?? ['Unknown Author'],
                'description' => $volumeInfo['description'] ?? '',
                'imageLinks' => $volumeInfo['imageLinks'] ?? [],
                'previewLink' => $volumeInfo['previewLink'] ?? '',
                'infoLink' => $volumeInfo['infoLink'] ?? '',
                'isFree' => $this->isBookFree($accessInfo),
                'downloadLink' => $this->getDownloadLink($accessInfo)
            ];

            $books[] = $book;
        }

        return $books;
    }

    /**
     * Get available categories
     */
    public function getCategories()
    {
        return [
            'bestsellers' => 'Best Sellers',
            'self-help' => 'Self Help',
            'business' => 'Business',
            'fiction' => 'Fiction',
            'science' => 'Science',
            'biography' => 'Biography',
            'history' => 'History',
            'technology' => 'Technology',
            'health' => 'Health & Wellness',
            'psychology' => 'Psychology'
        ];
    }

    /**
     * Check if book has free access
     */
    private function isBookFree($accessInfo)
    {
        // Check for public domain or free access
        if (isset($accessInfo['publicDomain']) && $accessInfo['publicDomain']) {
            return true;
        }

        // Check for free epub/pdf access
        if (isset($accessInfo['epub']['isAvailable']) && $accessInfo['epub']['isAvailable']) {
            return true;
        }

        if (isset($accessInfo['pdf']['isAvailable']) && $accessInfo['pdf']['isAvailable']) {
            return true;
        }

        // Check if preview is available (limited access)
        if (!empty($accessInfo['webReaderLink'])) {
            return true;
        }

        return false;
    }

    /**
     * Get download/view link if available
     */
    private function getDownloadLink($accessInfo)
    {
        // Prefer epub download
        if (isset($accessInfo['epub']['isAvailable']) && $accessInfo['epub']['isAvailable'] && isset($accessInfo['epub']['downloadLink'])) {
            return $accessInfo['epub']['downloadLink'];
        }

        // Then pdf
        if (isset($accessInfo['pdf']['isAvailable']) && $accessInfo['pdf']['isAvailable'] && isset($accessInfo['pdf']['downloadLink'])) {
            return $accessInfo['pdf']['downloadLink'];
        }

        // Then web reader
        if (isset($accessInfo['webReaderLink'])) {
            return $accessInfo['webReaderLink'];
        }

        // Fallback to preview
        if (isset($accessInfo['previewLink'])) {
            return $accessInfo['previewLink'];
        }

        return '';
    }

    /**
     * Fallback books if API fails - featuring best-selling books
     */
    private function getFallbackBooks($category = 'bestsellers', $startIndex = 0, $maxResults = 10)
    {
        $allBooks = [
            // Best Sellers
            [
                'id' => 'fallback1',
                'title' => 'The Power of Now',
                'authors' => ['Eckhart Tolle'],
                'description' => 'A Guide to Spiritual Enlightenment - The international bestseller that has inspired millions',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=9GqJDwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/The_Power_of_Now.html?id=9GqJDwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/The_Power_of_Now.html?id=9GqJDwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/The_Power_of_Now.pdf?id=9GqJDwAAQBAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback2',
                'title' => 'Atomic Habits',
                'authors' => ['James Clear'],
                'description' => 'An Easy & Proven Way to Build Good Habits & Break Bad Ones - Transform your life with tiny changes',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=fFC3DwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/Atomic_Habits.html?id=fFC3DwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/Atomic_Habits.html?id=fFC3DwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/Atomic_Habits.pdf?id=fFC3DwAAQBAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback3',
                'title' => 'Sapiens: A Brief History of Humankind',
                'authors' => ['Yuval Noah Harari'],
                'description' => 'The bestselling history of humankind from the Stone Age to the modern age',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=1EiJAwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/Sapiens.html?id=1EiJAwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/Sapiens.html?id=1EiJAwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/Sapiens.pdf?id=1EiJAwAAQBAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback4',
                'title' => 'The Subtle Art of Not Giving a F*ck',
                'authors' => ['Mark Manson'],
                'description' => 'A Counterintuitive Approach to Living a Good Life - The life-changing international bestseller',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=7ZKYCwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/The_Subtle_Art_of_Not_Giving_a_F_ck.html?id=7ZKYCwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/The_Subtle_Art_of_Not_Giving_a_F_ck.html?id=7ZKYCwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/The_Subtle_Art_of_Not_Giving_a_F_ck.pdf?id=7ZKYCwAAQBAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback5',
                'title' => 'Educated',
                'authors' => ['Tara Westover'],
                'description' => 'A Memoir - The Sunday Times and New York Times #1 bestseller',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=4stHDAAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/Educated.html?id=4stHDAAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/Educated.html?id=4stHDAAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/Educated.pdf?id=4stHDAAAQBAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback6',
                'title' => 'The Alchemist',
                'authors' => ['Paulo Coelho'],
                'description' => 'A Fable About Following Your Dream - The international bestseller',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=hnvHxwEACAAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/The_Alchemist.html?id=hnvHxwEACAAJ',
                'infoLink' => 'https://books.google.com/books/about/The_Alchemist.html?id=hnvHxwEACAAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/The_Alchemist.pdf?id=hnvHxwEACAAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback7',
                'title' => 'Thinking, Fast and Slow',
                'authors' => ['Daniel Kahneman'],
                'description' => 'Winner of the Nobel Prize in Economics - How we make decisions',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=CxPctX8LwkQC&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/Thinking_Fast_and_Slow.html?id=CxPctX8LwkQC',
                'infoLink' => 'https://books.google.com/books/about/Thinking_Fast_and_Slow.html?id=CxPctX8LwkQC',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/Thinking_Fast_and_Slow.pdf?id=CxPctX8LwkQC&output=pdf',
                'category' => 'bestsellers'
            ],
            [
                'id' => 'fallback8',
                'title' => 'The Psychology of Money',
                'authors' => ['Morgan Housel'],
                'description' => 'Timeless lessons on wealth, greed, and happiness - The instant #1 New York Times bestseller',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=2eqYDwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/The_Psychology_of_Money.html?id=2eqYDwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/The_Psychology_of_Money.html?id=2eqYDwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/The_Psychology_of_Money.pdf?id=2eqYDwAAQBAJ&output=pdf',
                'category' => 'bestsellers'
            ],
            // Self-help books
            [
                'id' => 'fallback9',
                'title' => 'The 7 Habits of Highly Effective People',
                'authors' => ['Stephen R. Covey'],
                'description' => 'Powerful Lessons in Personal Change',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=3WDKDwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/The_7_Habits_of_Highly_Effective_People.html?id=3WDKDwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/The_7_Habits_of_Highly_Effective_People.html?id=3WDKDwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/The_7_Habits_of_Highly_Effective_People.pdf?id=3WDKDwAAQBAJ&output=pdf',
                'category' => 'self-help'
            ],
            [
                'id' => 'fallback10',
                'title' => 'How to Win Friends and Influence People',
                'authors' => ['Dale Carnegie'],
                'description' => 'The Only Book You Need to Lead You to Success',
                'imageLinks' => ['thumbnail' => 'https://books.google.com/books/content?id=4WDKDwAAQBAJ&printsec=frontcover&img=1&zoom=5&edge=curl&source=gbs_api'],
                'previewLink' => 'https://books.google.com/books/about/How_to_Win_Friends_and_Influence_People.html?id=4WDKDwAAQBAJ',
                'infoLink' => 'https://books.google.com/books/about/How_to_Win_Friends_and_Influence_People.html?id=4WDKDwAAQBAJ',
                'isFree' => true,
                'downloadLink' => 'https://books.google.com/books/download/How_to_Win_Friends_and_Influence_People.pdf?id=4WDKDwAAQBAJ&output=pdf',
                'category' => 'self-help'
            ]
        ];

        // Filter by category and paginate
        $filteredBooks = array_filter($allBooks, function ($book) use ($category) {
            return $book['category'] === $category || $category === 'bestsellers';
        });

        return array_slice($filteredBooks, $startIndex, $maxResults);
    }
}
