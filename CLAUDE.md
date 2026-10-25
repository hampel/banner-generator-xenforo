# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is, and what is documented elsewhere

The **Banner Advert Generator** XenForo add-on (`Hampel/BannerGenerator`) — its own git
repository, checked out at `src/addons/Hampel/BannerGenerator` inside a XenForo install. It draws
solid-colour placeholder PNGs labelled with their dimensions, so an admin can style advert
positions before real adverts exist.

Read the install root's `AGENTS.md` for everything install-wide: `cmd.php` signatures, the
`_output/` ⇄ database import boundary, the version bump that opens a cycle of new work, and the
`xf-dev:export` prohibition. None of that is repeated here.

- `README.md` is customer-facing: requirements, the `banner()` arguments, the command and the
  options. Change it when behaviour a site owner can see changes.
- `CHANGELOG.md` is hand-maintained, one section per release.
- `TESTING.md` is the checklist of what the add-on touches in core; it is stripped from the build.

## Commands

```bash
composer install                              # vendor/ is gitignored; required before tests
vendor/bin/phpunit
vendor/bin/phpunit --testsuite Unit           # Feature holds only a placeholder
vendor/bin/phpunit --filter test_generateBanner_generates_image
vendor/bin/phpstan analyse                    # static analysis, level max
vendor/bin/phpstan analyse -c phpstan.floor.neon   # shipped code against the PHP floor
vendor/bin/xenforo-phpstan generate --check   # is the forum's generated set current?
```

**Run PHPUnit from the add-on root.** `tests/TestCase.php` boots XenForo from
`$rootDir = '../../../..'`, which is resolved against the working directory, not the file. The
suite runs against the real install, so the add-on must be installed there for its options to
exist.

`$addonsToLoad` limits that boot to this add-on — its listeners, extensions and autoloaders only.
Without it, any other installed add-on that vendors a different PHPUnit major is loaded into the
run, and the suite dies before its first test.

**Drive `banner:create` in tests through `ArgvInput`, never `ArrayInput`.** `ArrayInput` hands an
option `true` whatever its mode, so it cannot tell a flag from an optional-value option — which is
exactly the bug `--force` once had. `tests/Unit/CreateBannerTest.php` shows the form.

### Why the Flysystem pin and the ignored advisories are deliberate

**`league/flysystem-memory` is capped at `^1.0` on purpose, and must stay there.** `swapFs()`
builds a Flysystem **1.x** mount manager, and XenForo core loads `league/flysystem` 1.x at
runtime, so the 3.x adapter would neither satisfy the helper nor match the library the add-on
actually runs against. It is `require-dev` only; the add-on ships no vendor tree.

**`composer.json` therefore ignores two Flysystem advisories, with the reason beside each.**
Composer refuses to load a package carrying an advisory, and the one in question affects every
release up to 3.35.2 while 1.x is end-of-life — so there is no version to move to, and leaving it
blocked would freeze every other dependency. Revisit when XenForo core moves off Flysystem 1.x.

**`phpunit.xml` keeps `failOnPhpunitDeprecation="true"`, which the framework's own copy dropped.**
It was dropped there to support PHPUnit 10.5, which has no such attribute; this suite pins
`^12.5`, which does, so keeping it is the stricter setting.

### Static analysis runs at level max, and nothing is ignored

`hampel/xenforo-phpstan` lets PHPStan see the XenForo install without analysing it; its README is
the reference. **There is no baseline and no `ignoreErrors`, so a new report is a new problem.**

- **Shipped code is typed by docblock only.** `SubContainer\Banner` is extendable through
  `extendClass()` and the floor is PHP 7.0, so a native type added to a public method would be a
  fatal error in a subclass already written without it. The tests never ship and are typed
  natively.
- **`phpstan.floor.neon` analyses the shipped code alone with `phpVersion` min `70100`**, which
  reports syntax newer than the floor — not functions, which PHPStan reads from the PHP it runs
  on. 7.1 is as low as PHPStan goes; XenForo 2.2's 7.0 is one step below it.
- **`isValidSize()` asserts with the equality form, `=int<1, max>|string`**, which narrows an
  integer and says nothing about a string. The plain form let PHPStan conclude
  `isValidSize('auto', 50)` is always true, which `intval()` makes false.
- **A value core leaves untyped is asserted in the tests, never assumed** — `App::config()` with a
  key is `mixed` by design, since what an entry holds is a fact about the install. The package
  stubs the core methods the shipped code calls, so it needs no inline `@var`.

**`vendor/bin/xenforo-phpstan generate` writes into the forum's `internal_data/phpstan/`, shared
by every add-on analysed against that forum**, not into this repository. Ask `generate --check`
first: it writes nothing. Re-run `generate` only after a XenForo upgrade or a change to the class
extensions installed on the forum.

`cmd.php` resolves the install from its own location, so XF commands run unchanged from here:

```bash
php ../../../../cmd.php xf-dev:import --addon=Hampel/BannerGenerator
php ../../../../cmd.php banner:create --width=728 --height=90 --colour=red
php ../../../../cmd.php xf-addon:build-release Hampel/BannerGenerator
```

## Architecture

### One template function, registered by a listener — no class extension

`{{ banner(width, height, id, class, colour) }}` is `Template\BannerFunction::render()`, which the
`templater_setup` listener registers with `addFunction()`. **Do not reintroduce a
`XF\Template\Templater` extension to hold it.** `addFunction()` takes any callable and the
templater passes it `($templater, &$escape, ...arguments)`, so one event at one point does the
whole job — which is where XenForo's resource standards prefer a listener. Versions up to 1.1.1
did extend the templater; after upgrading, their `XF/Template/Templater.php` may linger on disk,
unreferenced and so unreachable.

Argument order is positional and was a breaking change in 1.1.0 (`class` inserted before
`colour`). Keep it stable.

### Banners are generated lazily, on every render

`render()` calls `generateBanner()` each time the template renders. That method checks
`data://<save path>/<w>x<h>-<colour>.png` on the abstracted filesystem and returns early when the
file exists, so the cost after the first render is one `has()` call. Nothing ever deletes a
banner: changing the save path, the default colour or a size leaves the old files in `data/`.

`generateBanner()` returns the path when it wrote a file, `""` when the file already existed, and
`null` after logging an error — a size below 1, an unknown colour, or a GD failure. **Callers
must tell `""` from `null`**: `banner:create` exits 1 on `null`. Invalid input is logged on every
render until the template is fixed, and `render()` returns nothing for an invalid size, so that
the template around it survives.

**Logging every render is deliberate, and repeats are not suppressed.** One row per page view is
a lot on a busy forum, and the usual answer would be to log the first occurrence and silence it
for a while. Not here: this add-on is a tool for a developer styling advert placements, who is
working in the template that is wrong and watching the error log while they do it. A suppressed
repeat is one they expect to see and do not, which reads as the fault being fixed. The volume is
bounded by fixing the template, and a forum running this in production has a larger problem than
its log size.

### The `banner` sub-container owns the colours and the paths

`Listener::appSetup()` registers `$app['banner']` through `extendClass()`, so other add-ons can
extend `SubContainer/Banner.php`. It holds the colour table — background RGB, text RGB, phrase —
and builds both the `data://` path and the public URL (via `applyExternalDataUrl()`) from the same
pattern. **Adding a colour needs a matching `hampel_bannergenerator_colour_<key>` phrase**, or the
default-colour option's select renders a missing phrase.

**The image is drawn with GD directly, not through XenForo's `Image\Manager`, and that is
deliberate.** XenForo's resource standards ask for its image handling where possible, but its API
creates, resizes, crops and composites — it cannot fill a colour or draw text, which is the whole
of a labelled placeholder. And `Manager::createImage()` returns an Imagick image on a forum whose
driver is Imagick, which GD's drawing functions cannot touch. `addon.json` requires `php-ext/gd`
for this reason.

### Options

Three options in the `hampelBannerGenerator` group: save path (default `banner-test`), default
colour (default `green`, rendered by `Option\DefaultColour::renderOption()` from the colour table),
and default classes (default `banner-ad`). Save path and default colour are read through the
static `get()` wrappers in `Option/`; default classes is read directly in `render()`.

### Setup has no schema

`Setup.php` creates nothing. Its only work is `enqueuePostUpgradeCleanUp()` on XF 2.3+, guarded by
`\XF::$versionId` because `addon.json` declares XF 2.2.0, which has no such method. XF 2.2
itself runs on PHP 7.0, so **shipped code must stay PHP 7.0-compatible** — no typed properties, no
`void` return types, no arrow functions — and must use core's pre-2.3 class names, which 2.3
aliases forward and 2.2 cannot resolve the other way. The tests may use newer syntax because they
never ship. `phpstan.floor.neon` checks the syntax half of this; the class names it cannot check.

### No `config` or `validate` commands, deliberately

**This add-on has nothing to self-check, so it ships neither command**, against the three
questions in the `xenforo-addon-validate` skill:

- **Nothing is opaque.** The save path, the colour and the default classes are each their own
  evidence — an unknown colour is refused at render and logged, and a changed save path just
  writes banners elsewhere.
- **Nothing runs unattended.** No cron, no jobs, no outbound calls: a banner is generated during
  a page render or by `banner:create`.
- **`config` would restate one ACP screen.** All three options live on it, and no `config.php`
  key is read.

GD is not a reason either: `addon.json` requires `php-ext/gd`, so XenForo refuses to install
without it, and a GD failure at render is logged.

**Reopen this** if the add-on gains a credential or an endpoint, anything scheduled, or state a
reader cannot see on the options screen. Every command costs two lines of `cmd.php list` on every
forum that installs the add-on.

## Traps

- **`BannerFunction::render()` sets `$escape = false`, so it escapes its own inputs.** It returns
  markup, which the templater would otherwise escape; in exchange every argument that reaches an
  attribute — `id`, the class list, the URL built from `colour` — goes through
  `\XF::escapeString()`, and the dimensions are cast to `int`. A new argument needs the same
  treatment, since a template may pass a variable where an admin would type a literal.
- **`build.json` moves every root `*.md` into the release zip root.** A new dev-only markdown file
  ships unless it is added to the `rm -fv` line that runs before that `mv`, and to the
  `export-ignore` list in `.gitattributes` for `git archive`.
