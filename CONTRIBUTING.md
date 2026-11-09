# Contributing to the MyParcel OpenCart 3 plugin

This file is the developer documentation of this repository. It describes the local
OpenCart 3 shop in Docker, the safety rules for the MyParcel API and the Git workflow.

The shop mounts the plugin from this repository live: edit the code in your IDE and
refresh the browser. You do not copy or zip anything.

## Requirements

- Docker Desktop (or OrbStack)
- `git`, `make`
- Optional: [GitHub CLI](https://cli.github.com/) (`gh`) to create pull requests

## Getting started

```bash
git clone https://github.com/myparcelnl/opencart3.git
cd opencart3
make up
```

The first `make up` copies `.env.example` to `.env`, builds the image, installs OpenCart
and installs the MyParcel extensions. This takes a few minutes. The default combination is
**G1**: OpenCart 3.0.5.0 with PHP 8.3.

| What       | URL                         |
|------------|-----------------------------|
| Shop       | http://localhost:8080       |
| Admin      | http://localhost:8080/admin |
| phpMyAdmin | http://localhost:8081       |
| Mailpit    | http://localhost:8025       |

Admin login: `OC_ADMIN_USER` and `OC_ADMIN_PASSWORD` in `.env`.

### Wait for the setup line

The setup runs in the background after the container starts. Follow it with `make logs`.
When the setup is done, the log shows exactly one of these lines:

```
[opencart] setup finished: OK
[opencart] setup finished: FAILED (<step>)
```

`<step>` is `opencart install`, `plugin link`, `plugin install` or `ocmod refresh`. Each
start of the container writes one setup line. The lines above it
tell you what failed.

## Daily commands

```
make up              start (and rebuild if the Dockerfile changed)
make down            stop
make logs            follow the OpenCart logs
make shell           bash in the OpenCart container
make link            after you add, remove or rename files in upload/
make ocmod           refresh the OCMOD modifications
make install-plugin  (re)install the MyParcel extensions
make db              MariaDB console
make zip             build dist/myparcelnl-<version>.ocmod.zip
make reset           delete everything; the next `make up` installs a fresh shop
```

`make install-plugin` installs `module/myparcelnl` and `shipping/myparcel_shipping`. To
install other extensions, give them in `EXT`, for example
`make install-plugin EXT="module/myparcelnl"`. The script checks the extension's row in
`oc_extension`, because OpenCart shows a success page also when an install fails. When an
extension is not installed, the script logs `[opencart] plugin install failed: <extension>`
and exits with code 1. The shop and the admin stay available.

## OpenCart and PHP combinations

Set the lines of a combination in `.env`, then run `make reset && make up`.

| Id | OpenCart | PHP | `.env` | Status |
|----|----------|-----|--------|--------|
| G1 | 3.0.5.0 | 8.3 | `OPENCART_VERSION=3.0.5.0`<br>`PHP_VERSION=8.3` | guaranteed, default |
| G2 | 3.0.5.0 | 8.4 | `OPENCART_VERSION=3.0.5.0`<br>`PHP_VERSION=8.4` | guaranteed |
| G3 | 3.0.5.1 | 8.3 | `OPENCART_VERSION=3.0.5.1`<br>`PHP_VERSION=8.3` | guaranteed |
| G4 | 3.0.5.1 | 8.4 | `OPENCART_VERSION=3.0.5.1`<br>`PHP_VERSION=8.4` | guaranteed |
| G5 | 3.0.5.1 | 8.5 | `OPENCART_VERSION=3.0.5.1`<br>`PHP_VERSION=8.5` | guaranteed |
| W1 | 3.0.4.1 | 8.3 | `OPENCART_VERSION=3.0.4.1`<br>`PHP_VERSION=8.3` | guaranteed, to test that the plugin refuses to install |
| W2 | 3.0.5.0 | 8.2 | `OPENCART_VERSION=3.0.5.0`<br>`PHP_VERSION=8.2` | guaranteed, to test that the plugin refuses to install |
| O1 | 3.0.5.0 | 8.5 | `OPENCART_VERSION=3.0.5.0`<br>`PHP_VERSION=8.5` | you can set it, no guarantee |
| O2 | 3.0.3.x | 7.4 to 8.1 | see below | you can set it, no guarantee |

All guaranteed combinations use `DEBIAN_VARIANT=trixie` (the default).

PHP 8.2 gets no security updates after 2026-12-31. Use it only for W2.

**Switch the OpenCart version always with `make reset`.** The OpenCart core lives in the
volume `myparcel-opencart3_opencart`. A plain `make up` after you change
`OPENCART_VERSION` builds the new image, but the existing volume keeps the old core and the
old database. To change only the PHP version, `make up` is enough, but the shop then runs
the old core on the new PHP version.

### Download and checksum of the OpenCart zip

The build downloads the release zip from GitHub and checks its SHA-256. For 3.0.4.1, 3.0.5.0
and 3.0.5.1 the URL and the SHA-256 are in `.docker/opencart-releases`. The build stops when
the checksum is different.

The asset of 3.0.4.1 has a different name than the newer releases (`3.0.4.1.zip`, not
`opencart-3.0.4.1.zip`). To give the URL yourself, set both lines in `.env`:

```
OPENCART_ZIP_URL=https://github.com/opencart/opencart/releases/download/3.0.4.1/3.0.4.1.zip
OPENCART_ZIP_SHA256=79fb91fd7cc548efd4a325237cc07828bfcea2b9b63156576f7c17d44354a114
```

GitHub publishes no digest for this asset. We calculated the SHA-256 from the download on
2026-11-09; there is no second source.

Rules for `OPENCART_ZIP_URL` and `OPENCART_ZIP_SHA256`:

- For a version in `.docker/opencart-releases`, a URL without a SHA-256 stops the build.
- A SHA-256 without a URL is checked against the zip from `.docker/opencart-releases`.
- For a version outside the table (O2), the SHA-256 may be empty. The build then writes a
  warning to the build log.

### Older combinations (O2)

The PHP image for PHP 8.1 exists on Debian `bookworm`, the images for PHP 7.4 and 8.0 only
on `bullseye`.
Xdebug `3.5.3` supports PHP 8.0 to 8.5; PHP 7.4 needs Xdebug `3.1.6`. For example:

```
OPENCART_VERSION=3.0.3.9
PHP_VERSION=8.1
DEBIAN_VARIANT=bookworm
```

### Xdebug

Set `XDEBUG_MODE=debug` in `.env`, run `make up` and listen on port 9003. PhpStorm: map
`/plugin/upload` to `<project>/upload`. The image installs Xdebug `3.5.3`, which works on
all guaranteed combinations (PHP 8.2 to 8.5). To use another version, set `XDEBUG_VERSION`
in `.env`.

### Upload limit

`.docker/php.ini` sets `upload_max_filesize = 64M`, so an upload of the plugin zip in the
Docker shop does not fail. A merchant with a default PHP configuration has a limit of 2 MB.
To test that limit, set `upload_max_filesize = 2M` in `.docker/php.ini` and run `make up`.
Do not commit that change.

## Safety rules

### Only on your own machine

All ports listen on `127.0.0.1` only. Other devices on your network cannot open the shop,
the admin, phpMyAdmin (which logs in without a password), MariaDB or Mailpit. The default
passwords in `.env.example` are acceptable only because of this. Do not change the ports
to `0.0.0.0`.

### Safety net for the MyParcel API

Local development never talks to the MyParcel production API. In the shop container these
production hosts resolve to `127.0.0.1` (`extra_hosts` in `docker-compose.yml`):

- `api.myparcel.nl`
- `iam.api.myparcel.nl`
- `order.api.myparcel.nl`
- `printing.api.myparcel.nl`
- `backoffice.myparcel.nl`
- `account.myparcel.nl`
- `admin.myparcel.nl`

The acceptance API `api.acceptance.myparcel.nl` stays reachable. Check the safety net with
`docker compose exec opencart getent hosts <host>`.

The safety net only catches server traffic from the shop container. It does not catch
requests from your browser.

`extra_hosts` has no wildcards, so each production host is in the list by name. Keep the
list complete:

- When a change adds a production host to the plugin's one place for API hosts, add the
  host in the same pull request to `extra_hosts` and to the `getent` command above.
- When a production host is hard-coded in the vendor code (for example in the SDK), it is
  also in the safety net. After each SDK update, search the built vendor:

  ```bash
  grep -rhoE '[a-z0-9.-]+\.myparcel\.(nl|me)' upload/system/library/myparcelnl/vendor | sort -u
  ```

  Add each production host from the result in the same pull request to `extra_hosts` and
  to the `getent` command above. Acceptance and testing hosts do not go in the safety net.

### API keys

The Docker configuration sets no API host and no API key. Use only an API key of the
acceptance environment, and enter it only in the admin of your local shop. Never put an API
key in `.env`, in the repository, in an issue or in a pull request. Never use a production
key or real customer data locally.

## How it works

- The OpenCart core lives in a Docker volume, not in git.
- `./upload` is mounted at `/plugin/upload`. `.docker/scripts/oc-link` symlinks every file
  into the OpenCart webroot, so changes to existing files are live. New or deleted files
  need `make link` (also runs on every `make up`).
- `oc-link` also links dotfiles such as `.htaccess`. The OpenCart installer
  (Extensions > Installer) does not. To test what protects a directory, install the zip
  through the Installer as well.
- `make ocmod` runs Extensions > Modifications > Refresh.
- The Apache log shows the path without the query string and without the referer
  (`.docker/apache-log.conf`). Admin URLs carry the `user_token`, and the log must not
  show it. The route of a request is therefore not in the log.

## Git workflow

1. Get the latest `opencart3-multicarrier`:
   ```bash
   git switch opencart3-multicarrier && git pull
   ```
2. Create a branch per Jira ticket: `<JIRA-KEY>-<short-summary-in-kebab-case>`, for example
   `INT-1234-saturday-delivery`.
3. Build and test locally in the Docker shop.
4. Commit with [Conventional Commits](https://www.conventionalcommits.org/), for example
   `build(docker): INT-1234 - <description>`.
5. Push and open a pull request to `opencart3-multicarrier`:
   ```bash
   git push -u origin HEAD
   gh pr create --fill --base opencart3-multicarrier
   ```
6. Ask a colleague for a review. A person merges after approval.
