# Testing the Banner Advert Generator

What this add-on touches in XenForo, what can break quietly, what the test suite settles on its
own, and what still needs a person in a browser. Read it before changing the add-on and before a
release.

## Surfaces

| surface | what it is for |
|---|---|
| listener `templater_setup` | registers the `banner()` template function, implemented by `Template\BannerFunction::render()` |
| listener `app_setup` | registers the `banner` container key, `SubContainer\Banner`, through `extendClass()` |
| options `hampelBannerGeneratorSavePath`, `…DefaultColour`, `…DefaultClasses` | where banners are written, the colour used when none is given, the classes added to every banner |
| CLI command `banner:create` | generates one banner by hand; `--force` regenerates an existing one |
| files under `data://<save path>/` | one PNG per size and colour, named `<w>x<h>-<colour>.png` |

## Fragile points

- **The `templater_setup` listener is the only thing that makes `banner()` exist.** Disabling or
  renaming it makes `{{ banner(...) }}` fail in every template that uses it, and nothing at install
  time says so.
- **The banner function turns template escaping off, so it has to escape its own output.** Every
  argument that reaches an HTML attribute is escaped and the dimensions are cast to integers. A new
  argument needs the same treatment, since a template can pass a variable where an admin would
  type a literal.
- **The arguments are positional.** 1.1.0 inserted `class` before `colour`, so a template written
  for 1.0.0 that passed a colour now passes it as a class, and gets the default colour.
- **`generateBanner()` returns `""` when the file already exists and `null` on failure.** Anything
  calling it has to tell the two apart; treating both as "nothing to do" hides failures.
- **Invalid input is logged on every render.** A size below 1 or an unknown colour writes an error
  each time the template renders, until the template is fixed. An invalid size renders nothing, so
  the surrounding template still renders.
- **Generated files are never deleted.** Changing the save path, the default colour or a banner's
  size leaves the old PNGs in `data/`.
- **The colour phrases are looked up by composed name**, so `xf-dev:unused-phrase-finder` reports
  all nine as unused. They are not; never run it with `--delete-all` on this add-on.
- **Shipped code must run on PHP 7.0**, the minimum for XenForo 2.2, and must use XenForo's
  pre-2.3 class names. The tests are not bound by this; they never ship.

## Automated

```bash
composer install
vendor/bin/phpunit                          # from the add-on root
vendor/bin/phpunit --order-by=random
```

The suite boots the installed forum with only this add-on loaded (`$addonsToLoad`), and covers:

- **`SubContainer\Banner`** — the colour table, size validation, both path builders, and
  generation into an in-memory filesystem: a new image, an existing one left alone, a forced
  regeneration, and the errors logged for an invalid size or colour. Generated images are checked
  for type, dimensions and background colour.
- **The `banner()` function**, rendered through the templater as a template calls it — the markup
  for every combination of `id`, `class` and default classes, escaping of hostile input, and empty
  output for an invalid size.
- **`banner:create`** — exit codes, `--force` and `-f`, rejection of `--force=1`, and the three
  outcomes of generation. Input is parsed with `ArgvInput`, as on the console.

`xf-dev:lint-templates` does not apply: the add-on owns no templates.

**To check a change in a booted forum without side effects**, replace two container keys before
exercising it: `fs` with a `League\Flysystem\MountManager` mounting a `MemoryAdapter` as `data`, so
no banner is written to disk, and `error` with a subclass of `XF\Error` whose `logError()` and
`logException()` record messages instead of writing them to the error log.

## Needs a human

- **How a banner looks in a real advert position** — size, label legibility against each colour,
  and whether the default classes pick up the style's advert styling.
- **The default-colour option** on the add-on's options page in the admin control panel: the select
  lists every colour by its phrase, and saving it changes the colour of newly generated banners.
- **The upgrade from the currently published release**, as a site owner would do it, from the
  release zip on a forum that has not been used for development. Check afterwards that existing
  `banner()` calls still render, bearing in mind the 1.1.0 argument change above, and that running
  the job queue after the upgrade leaves the forum working.
- **A forum on PHP 8.5**, where the suite does not run by default: generate a banner and confirm the
  error log stays empty.
