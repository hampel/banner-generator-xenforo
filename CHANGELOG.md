# Changelog

## 1.2.0 (2026-09-22)

- upgrading from 1.0.0: `banner()` takes `class` before `colour` since 1.1.0 — a template passing
  a colour as the fourth argument now gets the default colour; pass an empty class instead, as in
  `banner(300, 250, 'top-ad', '', 'red')`
- requires XenForo 2.2.0 or later
- `banner()` now escapes its `id`, `class` and `colour` arguments
- a banner size below 1 is logged and renders nothing, instead of breaking the template around it
- no deprecation notice on PHP 8.5 when generating a banner
- `banner:create`: `--force` works without a value, and an invalid `--colour` or a failed
  generation exits with an error
- the `banner()` function no longer extends `XF\Template\Templater`

## 1.1.1 (2025-12-11)

- run `enqueuePostUpgradeCleanUp` during upgrades if we're running XF2.3+

## 1.1.0 (2020-10-02)

- rename CLI command to `banner:create`
- new option to set default classes to add to all banners
- breaking change: changed parameters on `banner` function to include class

## 1.0.0 (2020-07-11)

- initial working version
