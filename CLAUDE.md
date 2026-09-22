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

- `README.md` is customer-facing and deliberately short — links to the resource and support thread.
- `CHANGELOG.md` is hand-maintained, one section per release.
- `TESTING.md` is the checklist of what the add-on touches in core; it is stripped from the build.

## Commands

```bash
composer install                              # vendor/ is gitignored; required before tests
vendor/bin/phpunit
vendor/bin/phpunit --testsuite Unit           # Feature holds only a placeholder
vendor/bin/phpunit --filter test_generateBanner_generates_image
```

**Run PHPUnit from the add-on root.** `tests/TestCase.php` boots XenForo from
`$rootDir = '../../../..'`, which is resolved against the working directory, not the file. The
suite runs against the real install, so the add-on must be installed there for its options to
exist.

`cmd.php` resolves the install from its own location, so XF commands run unchanged from here:

```bash
php ../../../../cmd.php xf-dev:import --addon=Hampel/BannerGenerator
php ../../../../cmd.php banner:create --width=728 --height=90 --colour=red
php ../../../../cmd.php xf-addon:build-release Hampel/BannerGenerator
```

## Architecture

### One template function, wired by two separate mechanisms

`{{ banner(width, height, id, class, colour) }}` in a template needs **both** of these, and
removing either breaks it silently:

1. **The class extension** `XF\Template\Templater` → `XF/Template/Templater.php`, which adds the
   `fnBanner()` method.
2. **The `templater_setup` listener** `Listener::templaterSetup()`, which maps the template
   function name `banner` to that method name.

Argument order is positional and was a breaking change in 1.1.0 (`class` inserted before
`colour`). Keep it stable.

### Banners are generated lazily, on every render

`fnBanner()` calls `generateBanner()` each time the template renders. That method checks
`data://<save path>/<w>x<h>-<colour>.png` on the abstracted filesystem and returns early when the
file exists, so the cost after the first render is one `has()` call. Nothing ever deletes a
banner: changing the save path, the default colour or a size leaves the old files in `data/`.

`generateBanner()` returns the path when it wrote a file, `""` when the file already existed, and
`null` on an invalid colour or a GD failure — the CLI command distinguishes the first two only.

### The `banner` sub-container owns the colours and the paths

`Listener::appSetup()` registers `$app['banner']` through `extendClass()`, so other add-ons can
extend `SubContainer/Banner.php`. It holds the colour table — background RGB, text RGB, phrase —
and builds both the `data://` path and the public URL (via `applyExternalDataUrl()`) from the same
pattern. **Adding a colour needs a matching `hampel_bannergenerator_colour_<key>` phrase**, or the
default-colour option's select renders a missing phrase.

### Options

Three options in the `hampelBannerGenerator` group: save path (default `banner-test`), default
colour (default `green`, rendered by `Option\DefaultColour::renderOption()` from the colour table),
and default classes (default `banner-ad`). Save path and default colour are read through the
static `get()` wrappers in `Option/`; default classes is read directly in `fnBanner()`.

### Setup has no schema

`Setup.php` creates nothing. Its only work is `enqueuePostUpgradeCleanUp()` on XF 2.3+, guarded by
`\XF::$versionId` because `addon.json` declares XF 2.2.0, which has no such method. XF 2.2
itself runs on PHP 7.0, so **shipped code must stay PHP 7.0-compatible** — no typed properties, no
`void` return types, no arrow functions — and must use core's pre-2.3 class names, which 2.3
aliases forward and 2.2 cannot resolve the other way. The tests may use newer syntax because they
never ship.

## Traps

- **`fnBanner()` sets `$escape = false`, so it escapes its own inputs.** It returns markup, which
  the templater would otherwise escape; in exchange every argument that reaches an attribute —
  `id`, the class list, the URL built from `colour` — goes through `\XF::escapeString()`, and the
  dimensions are cast to `int`. A new argument needs the same treatment, since a template may pass
  a variable where an admin would type a literal.
- **The generated-image tests assert a file size of 180 bytes**, which depends on the GD and
  libpng build. A size failure on a different machine is not necessarily a code change.
- **`build.json` moves every root `*.md` into the release zip root.** A new dev-only markdown file
  ships unless it is added to the `rm -fv` line that runs before that `mv`, and to the
  `export-ignore` list in `.gitattributes` for `git archive`.
