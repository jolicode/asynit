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

Replacements live under `override/`, in a path mirroring the namespace they take over, so what is being
substituted is legible from the file name and diffable against PHPUnit's own tree:

```
override/PHPUnit/TextUI/TestRunner.php                 -> PHPUnit\TextUI\TestRunner
override/PHPUnit/Framework/TestCase/OutputBuffer.php   -> PHPUnit\Framework\TestCase\OutputBuffer
```

`src/` is asynit's own code and holds nothing that pretends to be PHPUnit's.

Producers pulled in by `#[Depend]` — methods something depends on that PHPUnit did not collect as tests — are
added to the suite before the run is announced, so the progress counter and the totals include them. They are
not held to the "a test must assert something" rule.

Values reach the test through `TestCase::setDependencyInput()`, the same channel PHPUnit uses for its own
`#[Depends]`.

**Both dependency dialects go through the graph.** `#[Depend]` is asynit's, and can point at any method of any
class whether or not it is a test. PHPUnit's `#[Depends]`, `#[DependsExternal]` and their `UsingDeepClone` /
`UsingShallowClone` variants are read too, and deliberately so: PHPUnit resolves those at run time against the
tests that have already passed, which under concurrency means a dependent can start before its producer
finished and be skipped for no reason. Reading them into the graph turns a race into an ordering constraint.
`#[DependsOnClass]` is not supported.

## Why classes are overridden at all, and why only two

PHPUnit has no dependency injection: there is no container, no factory, no setter. Every collaborator is
constructed with `new` at its point of use, and the extension API (`Runner\Extension\Extension`) only lets you
subscribe to events, register a tracer and replace output. So a class can only be replaced by taking over its
name, through `exclude-from-classmap` in `composer.json`.

Two are replaced, and both because there is genuinely no other way in:

* **`TextUI\TestRunner`** — `Application::run()` does `$runner = new TestRunner;` inline, and `Application` is
  `final readonly`. Nothing can be injected, subclassed or configured.
* **`Framework\TestCase\OutputBuffer`** — `TestCase::__construct()` does `$this->outputBuffer = new OutputBuffer;`.
  The property is `private OutputBuffer`, typed, so even reflecting into it only accepts that exact class, and
  the class is `final`, so it cannot be subclassed. Replacing the name is the only option.

  It has to be replaced because the original calls `ob_start()` per test and asserts on `ob_get_level()` when
  stopping. PHP's output buffer stack is process global, not fiber local, so concurrent tests unwind it out of
  order: every test is reported risky ("did not close its own output buffers"), even ones that print nothing,
  and output lands on the wrong test. The replacement installs one process wide buffer whose handler runs on
  every output call, in the fiber that made it, and routes the output to that fiber's test.

  That is `ob_start()`'s `$chunk_size`, which the class names `FLUSH_PER_OUTPUT_CALL` rather than `1`: PHP
  flushes as soon as an output call brings the buffer to at least that many bytes, so the smallest value makes
  the handler run once per `echo`, with whatever that single call wrote. It is not "once per character" - a
  100 KB `echo` is one invocation - and costs about 30ns per output call over a plain `ob_start()`.

  Two alternatives were tried and do not work. The extension API's `replaceOutput()` family only sets flags
  telling PHPUnit's own printer to stay quiet so an extension can print instead; it has nothing to do with
  capturing what a test writes. Saving and restoring the buffer stack around fiber switches - which
  `Revolt\EventLoop::setDriver()` would let us hook - fails for a different reason: PHPUnit's `OutputBuffer`
  flags itself destroyed as soon as its handler sees `PHP_OUTPUT_HANDLER_FINAL`, which any `ob_end_clean()`
  raises, so `stop()` would report "closed output buffers other than its own" anyway.

`Framework\TestRunner\TestRunner` used to be overridden too, to get away from `Runner\ErrorHandler` — a
singleton bound to a single test that asserts on `!$this->enabled`, so the second concurrent test aborts the
run. It no longer is: `TestCase::runBare()` is `final public`, so `Asynit\Runner\TestExecutor` calls it
directly and does the surrounding bookkeeping itself, in asynit's own namespace. The only thing skipping
`TestCase::run()` costs is its `handleDependencies()`, which is handled below.

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
* **`#[RunInSeparateProcess]` and `#[RunTestsInSeparateProcesses]` are ignored.** Process isolation cannot be
  reconciled with running a test on a fiber alongside others.
* **`#[DependsOnClass]` is not supported**; use `#[DependsExternal]` or asynit's `#[Depend]`.
* **PHPUnit's `--order-by` is ignored**, since asynit orders by the dependency graph.
* **Every replaced class is `@internal` and carries no backward compatibility promise.** A PHPUnit patch
  release can change them without warning, hence the narrow `~13.2.0` constraint in `composer.json`.
