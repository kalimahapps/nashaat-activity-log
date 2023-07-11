<?php
use PHPUnit\Framework\Assert;
/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Global hooks
|--------------------------------------------------------------------------
|*/

uses()
->beforeAll(
	function() {
		// Make sure all required plugins are active
		$required_plugins = array(
			// 'akismet/akismet.php',
			'duplicate-post/duplicate-post.php',
			'gravityforms/gravityforms.php',
		);

		foreach ($required_plugins as $plugin) {
			if (!is_plugin_active($plugin)) {
				throw new Exception("Plugin `{$plugin}` is not installed/active");
			}
		}

		// Chcek table exists
		global $wpdb;
		$table  = NASHAAT_DB_TABLE;
		$result = $wpdb->query("Select 1 from {$table} LIMIT 1");

		if ($result === false) {
			throw new Exception("Table `{$table}` doesn't exists");
		}

		wp_clear_auth_cookie();
		wp_set_current_user(1);
		wp_set_auth_cookie(1);
	})
->afterAll(function() {
		// Clear table
		global $wpdb;
		$table = NASHAAT_DB_TABLE;
		$wpdb->query("TRUNCATE TABLE {$table}");

			wp_clear_auth_cookie();
			wp_set_current_user(0);
			wp_set_auth_cookie(0);

		})->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/


/**
 * Callback for toHaveProperties.
 *
 * This has been abstracted out so that it can be called recursively.
 *
 * @param  iterable<array-key, mixed>  $incoming The incoming array
 * @param  iterable<array-key, mixed>  $expected The expected array
 * @param  string  $message The message to display if the assertion fails
 */
function assert_object(mixed $incoming, iterable $expected, string $message): void {

	$incoming_array = is_object($incoming) && method_exists($incoming, 'toArray') ? $incoming->toArray() : (array) $incoming;
	// $expected_array = is_object($expected) && method_exists($expected, 'toArray') ? $expected->toArray() : (array) $expected;

	foreach ($expected as $name => $value) {
		// Check if the key from $expected exists in $incoming
		$key          = is_int($name) && (is_string($value) || is_int($value)) ? $value : $name;
		$non_existent = $message;
		if ($non_existent === '') {
			$non_existent = "Failed asserting that `{$key}` exists";
		}

		if (array_is_list($incoming_array)) {
			Assert::assertTrue(in_array($key, $incoming_array), $non_existent);
		} else {
			Assert::assertTrue(array_key_exists($key, $incoming_array), $non_existent);
		}


		$incoming_value = $incoming_array[$key];
		// if $value is an iterable, recurse
		if (is_iterable($value)) {
			assert_object($incoming_value, $value, $message);

			continue;
		}

		// $name exists and it is not an int (not a numeric key)
		// so we can check against $value
		if (!is_int($name)) {
			Assert::assertEquals($value, $incoming_value, $message);
		}
	}
}

/**
 * Extend expect to check one object against another.
 * This is similar to `toHaveProperties` but it is recursive.
 */
expect()->extend('toIncludeProperties', function(iterable $expected, $message = '') {

		assert_object($this->value, $expected, $message);

		return $this;
	});

// expect()->intercept('toHaveProperties', 'iterable', function(iterable $expected, bool $ignore_case = false) {
// 		// expect($this->value->id)->toBe($expected->id);
// 		foreach ($expected as $name => $value) {
// 			if (is_array($value)) {
// 				return $this->toHaveProperties($value);
// 				continue;
// 			}

// 			return is_int($name) ? $this->toHaveProperty($value) : $this->toHaveProperty($name, $value);
// 		}


// 		echo $ignore_case;
// 	});

// expect()->pipe('toHaveProperties', function(Closure $next, iterable $expected) {
// 		foreach ($expected as $name => $value) {
// 			if (is_array($value)) {
// 				return $this->toHaveProperties($value);
// 				continue;
// 			}

// 			return is_int($name) ? $this->toHaveProperty($value) : $this->toHaveProperty($name, $value);
// 		}

// 		// if ($this->value instanceof Model) {
// 		// 	return expect($this->value->id)->toBe($expected->id);
// 	// }

// 	// return $next(); // Run to the original, built-in expectation...
// 	});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 *
 */
function something() {
	// ..
}