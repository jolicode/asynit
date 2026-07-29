# PHPUnit engine (experimental)

Asynit stays its own binary and its own scheduler. PHPUnit is used as the *test case* engine: test classes
extend `PHPUnit\Framework\TestCase`, so `setUp()`/`tearDown()`, mocks, `expectException()`, and the whole
assertion API come for free, and `src/Assert/` shrinks to the HTTP specific assertions.

## How it fits together

| Stays asynit | Comes from PHPUnit |
| --- | --- |
| `TestsFinder` — discovery via `#[TestCase]` / `#[Test]` | `TestCase` lifecycle, hooks, mocks |
| `TestPoolBuilder` — dependency graph, cycle detection | `Assert` — every assertion |
| `PoolRunner` — fibers + concurrency semaphore | Issue/risky reporting via events |
| `Output` / `JUnitReport` | `Event\Code\Throwable` for failure details |

The scheduler calls `TestCase::run()` (it is `final` but public) from a fiber, after handing the values
produced by the dependency graph over with `TestCase::setDependencyInput()`. That is why asynit keeps its own
`#[Depend]` rather than adopting PHPUnit's `#[Depends]`: PHPUnit can only depend on another *test*, in the same
class unless the producer class happens to run first, while asynit resolves any method on any class and orders
the whole graph itself.

## Why PHPUnit classes are replaced

PHPUnit assumes one test at a time. Three pieces of process global state break when tests overlap, and all
three live in classes replaced through `exclude-from-classmap` in `composer.json`:

* **`PHPUnit\Framework\TestCase\OutputBuffer`** — the original calls `ob_start()` per test and asserts on
  `ob_get_level()` when stopping. PHP's output buffer stack is process global, not fiber local, so concurrent
  tests unwind it out of order: every test is reported risky ("did not close its own output buffers"), even
  ones that print nothing, and output lands on the wrong test. The replacement installs one process wide
  buffer with a chunk size of 1, which makes PHP call the handler on every write from the writing fiber, and
  routes each chunk to that fiber's test.
* **`PHPUnit\Framework\TestRunner\TestRunner`** — the original enables `PHPUnit\Runner\ErrorHandler`, a
  singleton bound to a single test that asserts on `!$this->enabled`. The second concurrent test aborts the
  run outright. The replacement drops it; `PoolRunner` installs one error handler for the whole run instead.

## Known limitations

* **Per test assertion counts are only accurate with `--concurrency 1`.** `Assert::$count` is a private static
  counter and the per test number is a delta read around the test, so concurrent tests inherit each other's
  assertions. On `tests/FunctionalTests.php` that shows as 8 assertions at `--concurrency 1` and 36 at 10.
  Fixing it properly means making `Assert::$count` fiber local — five lines in a 3300 line class we do not
  want to fork, so it wants an upstream change.
* **Code coverage is not collected.** `Runner\CodeCoverage` is another per test singleton with the same
  problem.
* **PHPUnit issue reporting (deprecations, notices) is lost**, since `ErrorHandler` is what produces it.
  Asynit turns PHP errors into `ErrorException` and reports them as test errors, as it did before.
* **Every replaced class is `@internal` and carries no backward compatibility promise.** A PHPUnit patch
  release can change them without warning, hence the narrow `~13.2.0` constraint in `composer.json`.
