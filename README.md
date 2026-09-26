<div align="center">

**English** · [العربية](README.ar.md)

# Kleeja

**The easiest way to run your own file upload and sharing service.**
Self-hosted, PHP-powered, and trusted by webmasters since 2007.

[![Latest release](https://img.shields.io/github/v/release/kleeja/kleeja?label=release)](https://github.com/kleeja/kleeja/releases)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.0-777bb4?logo=php&logoColor=white)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Code style: Prettier](https://img.shields.io/badge/code_style-prettier-ff69b4.svg)](https://prettier.io)
[![Discord](https://img.shields.io/badge/chat-discord-5865f2?logo=discord&logoColor=white)](https://discord.gg/Mp3XVKP)

[Download](https://github.com/kleeja/kleeja/releases) ·
[Documentation](https://kleeja.net/getting-started/introduction) ·
[Features](https://kleeja.net) ·
[Changelog](CHANGELOG.md) ·
[Report a bug](https://github.com/kleeja/kleeja/issues)

<img src="https://raw.githubusercontent.com/kleeja/website/master/screenshot1.png" width="720" alt="Kleeja upload page">

</div>

> [!IMPORTANT]
> To run Kleeja on a live site, download a packaged build from the
> [releases page](https://github.com/kleeja/kleeja/releases). Don't
> deploy a clone of this repository: it tracks in-progress development.

## Features

- **Uploads and sharing**: visitors and members upload files and get shareable links.
- **Admin control panel**: manage files, users, bans and settings from the browser.
- **One-click updates**: update Kleeja itself from the admin panel.
- **Plugins and styles store**: download, install, update and remove plugins and
  themes in one click.
- **Multilingual**: ships with English and Arabic.

See the [full feature list](https://github.com/kleeja/kleeja/wiki/Key-Features-&-Highlights-of-Kleeja) on the wiki.

<div align="center">
<img src="https://raw.githubusercontent.com/kleeja/website/master/screenshot2.png" width="720" alt="Kleeja file sharing page">
</div>

## Requirements

- PHP 8.0 or later with the `pdo_mysql` or `pdo_sqlite` extension (`gd` and `zip` recommended)
- MySQL or MariaDB
- A web server such as Apache or IIS (sample `htaccess.txt` and `web.config` included)

## Installation

1. Download the latest archive from the [releases page](https://github.com/kleeja/kleeja/releases)
   and upload its contents to your web server.
2. Make the `cache/` and `uploads/` directories writable by the web server.
3. Open `https://your-site.example/install/` in a browser and follow the installer.

The [wiki](https://github.com/kleeja/kleeja/wiki) covers web server
configuration, upgrades and troubleshooting.

## Local development

### Run with Docker

The included Compose file starts Kleeja on Apache with PHP 8.2, a MySQL 8 database,
and [Mailpit](https://mailpit.axllent.org) to catch outgoing mail.

```bash
docker compose up -d --build
```

| Service    | Address                                            |
| ---------- | -------------------------------------------------- |
| Kleeja     | <http://localhost:8080> (installer at `/install/`) |
| Mailpit UI | <http://localhost:8025/mailpit>                    |
| MySQL      | `127.0.0.1:3306`                                   |

When the installer asks for database details, use these values:

| Setting  | Value     |
| -------- | --------- |
| Host     | `mysql`   |
| Database | `appdb`   |
| User     | `appuser` |
| Password | `apppass` |

To send mail through Mailpit, set the SMTP host to `mailpit` and the port to `1025`.

### Code formatting

The codebase is formatted with [Prettier](https://prettier.io) and
[@prettier/plugin-php](https://github.com/prettier/plugin-php). CI rejects pull
requests that aren't formatted.

```bash
npm install                            # install the toolchain
npm run format                         # format everything
npm run format:check                   # check only (what CI runs)
git config core.hooksPath .githooks    # enable the pre-commit and commit-msg hooks
```

Templates (`*.html`) and vendored assets are excluded; see `.prettierignore`.

## Contributing

Contributions are welcome. Before you open a pull request:

1. Run `npm run format` so the Prettier check passes.
2. Write commit messages in the [Conventional Commits](https://www.conventionalcommits.org)
   format, for example `fix(auth): sign the whole login cookie payload`.
   The `commit-msg` hook enforces this.

For questions, join the [Discord server](https://discord.gg/Mp3XVKP) or
[open an issue](https://github.com/kleeja/kleeja/issues).

## License

Kleeja is released under the [MIT License](LICENSE.md).
