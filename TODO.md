# TODO: Add University Image Background to Home Page Sections

## Tasks Completed

- [x] Update hero section background image URL in assets/css/style.css to a high-quality university students studying image
- [x] Adjust gradient overlay opacity in hero section CSS to ensure background image visibility
- [x] Add CSS class .available-books-section with matching background styling
- [x] Wrap available books section in index.php with new div class for background application
- [x] Test home page to verify background images are visible and professional
- [x] Fix hero section button clickability by adding z-index: -1 to overlay pseudo-elements
- [x] Replace Unsplash background URLs with local image 'image/universitystudents.jpg' in both hero and available-books sections

## Notes

- Background image changed to local file: assets/css/image/universitystudents.jpg
- Reduced gradient opacity from 0.7 to 0.3 to make background image visible
- Added .available-books-section class with same background styling as hero section
- Wrapped available books content in index.php with the new class
- Background now covers hero section and available books section above Recommended Books
- Fixed button clickability by setting z-index: -1 on ::before and ::after pseudo-elements
- Updated both .hero-section and .available-books-section to use local image instead of external Unsplash URL
