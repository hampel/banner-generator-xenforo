# Banner advert generator for XenForo

This XenForo add-on generates placeholder banner adverts — solid-colour images labelled with their
size — to help you style advert placements before real adverts exist.

By [Simon Hampel](https://xenforo.com/community/members/sim.4264/).

- [Addon: Banner Advert Generator](https://xenforo.com/community/resources/banner-advert-generator.7901/)
- [Discussion and support: Banner Advert Generator](https://xenforo.com/community/threads/banner-advert-generator.182747/)

## Requirements

- XenForo 2.2.0 or later
- The PHP GD extension

## Usage

Call the `banner()` function from any template, including an advert's HTML:

```text
{{ banner(728, 90) }}
{{ banner(300, 250, 'sidebar-ad', 'my-class', 'blue') }}
```

The arguments are positional:

| argument | required | what it does |
|---|---|---|
| `width`, `height` | yes | the banner's size in pixels |
| `id` | no | an `id` attribute for the wrapping `<div>` |
| `class` | no | classes for the wrapping `<div>`, added after the default classes option |
| `colour` | no | one of `black`, `red`, `green`, `blue`, `yellow`, `cyan`, `magenta`, `grey` or `white`; the default colour option when omitted |

It renders a `<div>` of that size containing the image. Each size and colour is generated once, the
first time it is rendered, and saved under `data/` — generated images are never deleted, so
changing a size or colour leaves the old image behind. An invalid size or colour renders nothing
and logs an error.

### Upgrading from 1.0.0

Version 1.1.0 added `class` before `colour`. A template written for 1.0.0 that passes a colour as
the fourth argument now gets the default colour, with the colour name added as a class. Pass an
empty class to fix it:

```text
{{ banner(300, 250, 'sidebar-ad', 'red') }}        1.0.0
{{ banner(300, 250, 'sidebar-ad', '', 'red') }}    1.1.0 and later
```

## Command line

Generate a banner without rendering a template:

```bash
php cmd.php banner:create --width=728 --height=90 --colour=red
php cmd.php banner:create -x 728 -y 90 --force        # regenerate an existing banner
```

`--colour` defaults to the default colour option. The command exits non-zero when the size or
colour is invalid or the image could not be generated.

## Options

Under the add-on's options group in the admin control panel:

| option | default | what it does |
|---|---|---|
| Banner save path | `banner-test` | where banners are saved, relative to the `data` directory |
| Default banner colour | `green` | the colour used when a template does not give one |
| Default CSS classes | `banner-ad` | classes added to every banner's wrapping `<div>` |
