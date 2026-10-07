# Tiny command reference

Run each command from your project folder as `php Tiny <command>`
(on Linux/macOS, after `chmod +x Tiny`, you can also run `./Tiny <command>`).
Tiny version: v0.1.0

```
php Tiny list              # list all commands
php Tiny help <command>    # full details for one command
```

## Commands

| Command | Argument / options | What it does |
|---|---|---|
| `build:all` | `--no-clean` | Builds every page, the assets and the list page using `site.baseurl`, then removes stale output. `--no-clean` keeps leftover files. |
| `build:prod` | none | Same as `build:all`, but every link uses `site.production_url`. Use this before deploying. |
| `build:single` | `<slug>` (required) | Builds one page. Exits with code 1 if the slug isn't found. |
| `clean` | none | Deletes every file Tiny generated (tracked in the manifest). Your own files in `public/` are left alone. |
| `create:page` | `[title]`<br>`--slug=`<br>`--template=`<br>`--nav` / `--no-nav`<br>`--order=`<br>`--draft` / `--no-draft`<br>`--tags=` | Creates a new content page. The title is capitalized per word. Anything you don't pass is prompted for. |
| `serve` | `--host=` (default `localhost`)<br>`--port=` (default `8000`)<br>`--no-watch` | Builds into `public/`, serves it locally and rebuilds on changes. Links point at the address being served. |
| `watch` | none | Rebuilds into `public/` whenever content, the selected theme, assets or `config.yaml` change. No server. |

## Configuration

Two URLs in `config.yaml`:

```yaml
site:
  baseurl: http://localhost:8000          # local work: build:all and watch
  production_url: https://example.com     # deployment: build:prod only
```

| Command | Links in the built pages use |
|---|---|
| `build:all`, `watch` | `site.baseurl` |
| `serve` | the address it is serving on (`--host` / `--port`), ignoring `site.baseurl` |
| `build:prod` | `site.production_url` |

`public/` always holds the result of whichever build ran last. **Run `build:prod` before every deploy**, and don't deploy straight after `serve`, `watch` or `build:all`.

Only put the values you want to change in `config.yaml`. Everything else comes from
the core's `config/defaults.yaml`, so new options never break an existing project.

## The 404 page

Add `content/404.md` to write your own. It is built like any other page, so give it
`nav: false`. Without one, Tiny generates a plain "Page not found." page. Either way
the result is `public/404.html`.

## Command details

### build:all

```
php Tiny build:all
php Tiny build:all --no-clean
php Tiny build:all -v            # also prints each output path
```

- Removes files from earlier builds that this build no longer produces (for example, the `.html` of a deleted `.md`).
- If anything fails to build, nothing is removed, so a broken template never deletes the last good page.

### build:prod

```
php Tiny build:prod
```

- Needs `site.production_url` in `config.yaml`, as a full URL starting with `http://` or `https://`. A trailing slash is ignored.
- If it is missing or invalid, the command stops with an error and `public/` is left untouched.
- Always cleans stale files. It has no `--no-clean` option.

### build:single

```
php Tiny build:single hiking-101
```

### clean

```
php Tiny clean
```

Removes only files recorded in the manifest, `.tiny-manifest.json` in the project root.
The manifest is kept outside `public/`, so it is never deployed.

### create:page

```
php Tiny create:page                       # fully interactive
php Tiny create:page "My Post"             # asks for the rest
php Tiny create:page "My Post" --no-nav --draft --tags=php,tips -n
```

- Only options you leave out are asked about.
- The template question only appears if the theme has more than one page template.
- The order question only appears if the page is in the nav.
- `--tags` takes a comma-separated string, e.g. `--tags="php, static sites"`.
- With `-n` it never prompts and uses the defaults: nav on, order 10, not a draft, no tags. The title is then required, so pass it as the argument.
- Slugs must be lowercase letters, numbers and single hyphens. A slug that already exists, or that matches the list page's slug, is rejected.

### serve

```
php Tiny serve
php Tiny serve --port=8080
php Tiny serve --no-watch
php Tiny serve -v                # also shows the server's request log
```

- Builds into `public/` with links pointing at the served address, so any port works.
- Prints a reminder that the pages contain local links and that `build:prod` is needed before deploying.
- Use `localhost` or `127.0.0.1` for `--host`. `0.0.0.0` would put `0.0.0.0` in every link.

### watch

```
php Tiny watch
```

Watches `config.yaml`, the content folder, the selected theme's folder and the assets folder. It polls every 0.5 seconds and does a full rebuild on each change. Editing a theme that isn't selected does not trigger a rebuild.

## Options every command accepts

These come from Symfony Console.

| Option | Effect |
|---|---|
| `-v`, `-vv`, `-vvv` | More output. For Tiny, `-v` shows output paths in `build:all` / `build:prod` and the server's request log in `serve`. |
| `-n`, `--no-interaction` | Never prompt. |
| `-q`, `--quiet` / `--silent` | Suppress output. |
| `-h`, `--help` | Help for a command, e.g. `help serve`. |
| `-V`, `--version` | Prints the Tiny version. |
| `--ansi` / `--no-ansi` | Force or disable colours. |

## Exit codes

| Code | Meaning |
|---|---|
| 0 | Success (or a normal Ctrl+C stop for `serve` / `watch`). |
| 1 | A build or cleanup failure, an invalid argument or config value, an unknown slug, or the server stopped unexpectedly. |