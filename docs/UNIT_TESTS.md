# Unit Tests

- **Framework:** Pest PHP wraps PHPUnit; assertions and helpers follow Pest syntax inside the `tests` directory.
- **Location:** Targeted unit specs live in `tests/Unit`; shared factories and helpers stay in `tests/Pest.php`.
- **Execution:** Run `php artisan test --testsuite=Unit` (or `vendor\bin\pest --group=unit` on Windows) to execute only unit coverage.
- **Purpose:** Each unit test isolates a single class or pure function, mocking external services and database calls to keep feedback fast.
- **Conventions:** Name files with the subject under test (e.g., `UserServiceTest.php`), group related expectations with Pest `describe` blocks, and prefer Laravel helpers for fakes over manual stubs.

**Example – ImageOptimizationServiceTest:** The suite boots with `Storage::fake('public')`, uploads a synthetic JPEG, and pushes it through `processPhoto`. Assertions confirm the response exposes paths for each generated size, metadata, and basic dimensions, then verifies the fake disk actually holds those files. Follow-up expectations run `deletePhoto` against the returned paths to prove every variant is removed, demonstrating how the service keeps storage tidy without touching the real filesystem.

Snapshot:

```php
// Arrange
Storage::fake('public');
$file = UploadedFile::fake()->image('test.jpg', 3840, 2160);
$path = 'photos/test';

// Act
$result = $this->service->processPhoto($file, $path);

// Assert generated variants and metadata
$this->assertArrayHasKey('thumbnail_path', $result);
$this->assertArrayHasKey('medium_path', $result);
$this->assertArrayHasKey('large_path', $result);
$this->assertArrayHasKey('original_path', $result);
$this->assertArrayHasKey('metadata', $result);
$this->assertTrue(Storage::disk('public')->exists($result['thumbnail_path']));
$this->assertTrue(Storage::disk('public')->exists($result['original_path']));

// Cleanup: all variants removed
$this->service->deletePhoto([
	$result['thumbnail_path'],
	$result['medium_path'],
	$result['large_path'],
	$result['original_path'],
]);

$this->assertFalse(Storage::disk('public')->exists($result['thumbnail_path']));
```
