# How asynit sits on top of PHPUnit

Asynit is PHPUnit. `bin/asynit` boots `PHPUnit\TextUI\Application` unchanged, so the CLI, the configuration
file, the extensions, the printers, the loggers and the exit codes are all PHPUnit's. Asynit contributes two
things and nothing else:

* **a scheduler** — tests run concurrently on fibers instead of one after another, so tests waiting on I/O
  overlap;
* **`#[Depend]`** — a dependency graph that decides the order and passes each producer's return value to its
  dependents.

Everything asynit used to own — its console command, its own output, its own JUnit report, its test discovery,
its assertion layer — is gone, replaced by the PHPUnit equivalent.

## The seam

`PHPUnit\TextUI\TestRunner` (76 lines) is replaced through `exclude-from-classmap`. The original walks the
suite tree and calls `$test->run()` one test at a time; asynit's flattens the tree, builds the `#[Depend]`
graph over the collected tests and hands it to `ConcurrentRunner`.

Producers pulled in by `#[Depend]` — methods something depends on that PHPUnit did not collect as tests — are
added to the suite before the run is announced, so the progress counter and the totals include them. They are
not held to the "a test must assert something" rule.

Values reach the test through `TestCase::setDependencyInput()`, the same channel PHPUnit uses for its own
`#[Depends]`. That is why asynit keeps `#[Depend]`: PHPUnit's version can only depend on another *test*, in the
same class unless the producer class happens to run first, while asynit's resolves any method of any class and
orders the whole graph.

## The other two overrides

PHPUnit assumes one test at a time. Two pieces of process global state break when tests overlap:

* **`Framework\TestCase\OutputBuffer`** — the original calls `ob_start()` per test and asserts on
  `ob_get_level()` when stopping. PHP's output buffer stack is process global, not fiber local, so concurrent
  tests unwind it out of order: every test is reported risky ("did not close its own output buffers"), even
  ones that print nothing, and output lands on the wrong test. The replacement installs one process wide
  buffer with a chunk size of 1, which makes PHP call the handler on every write from the writing fiber, and
  routes each chunk to that fiber's test.
* **`Framework\TestRunner\TestRunner`** — the original enables `Runner\ErrorHandler`, a singleton bound to a
  single test that asserts on `!$this->enabled`, so the second concurrent test aborts the run.

## Configuration

PHPUnit's CLI is not asynit's to extend, so the few settings that describe the run rather than a test case are
read from the environment:

| Variable | Default | Meaning |
| --- | --- | --- |
| `ASYNIT_CONCURRENCY` | `10` | how many tests may be in flight at once |
| `ASYNIT_HOST` | none | base URI prepended to requests made with a relative URI |
| `ASYNIT_TIMEOUT` | `10` | default HTTP timeout, in seconds |
| `ASYNIT_RETRY` | `0` | default number of HTTP retries |
| `ASYNIT_ALLOW_SELF_SIGNED_CERTIFICATE` | `false` | disable TLS peer verification |

A test class can still override its own client with `#[HttpClientConfiguration]`; it inherits `ASYNIT_HOST`
unless it sets a base URI itself.

## Known limitations

* **Per test assertion counts are only accurate with `ASYNIT_CONCURRENCY=1`.** `Assert::$count` is a private
  static counter and the per test number is a delta read around the test, so concurrent tests inherit each
  other's assertions. Fixing it properly means making `Assert::$count` fiber local — five lines in a 3300 line
  class we do not want to fork, so it wants an upstream change.
* **Code coverage is not collected.** `Runner\CodeCoverage` is another per test singleton with the same
  problem.
* **PHPUnit issue reporting (deprecations, notices) is lost**, since `ErrorHandler` is what produces it.
* **`testSuiteStarted`/`testSuiteFinished` are emitted once for the whole run**, not per class: with classes
  running concurrently there is no point at which one class is done and another has not started.
* **PHPUnit's `--order-by` is ignored**, since asynit orders by the dependency graph.
* **Every replaced class is `@internal` and carries no backward compatibility promise.** A PHPUnit patch
  release can change them without warning, hence the narrow `~13.2.0` constraint in `composer.json`.
