# Kleeja

The powerful and easiest way to run File Upload/sharing Service on your website.
Trusted by thousands of webmasters since 2007.

PLEASE DOWNLOAD THE SCRIPT FROM RELEASE PAGE, DON'T DOWNLOAD THIS REPOSITORY DIRECTLY

<p align="center">
<img src="https://raw.githubusercontent.com/kleeja-official/website/master/screenshot1.png" width="650" height="auto" alt="github php files uploading">
</p>

<p align="center">
<img src="https://raw.githubusercontent.com/kleeja-official/website/master/screenshot2.png" width="650" height="auto" alt="github php files sharing">
</p>

| 🎶                                                                                                             |
| -------------------------------------------------------------------------------------------------------------- |
| 🔥 [Features & Highlights](https://github.com/kleeja-official/kleeja/wiki/Key-Features-&-Highlights-of-Kleeja) |
| ⬇️ [Download](https://github.com/kleeja-official/kleeja/releases)                                              |
| 📚 [How-To/documentations](https://github.com/kleeja-official/kleeja/wiki)                                     |
| ⏰ [ChangeLog](https://github.com/kleeja-official/kleeja/blob/master/CHANGELOG.md)                             |
| 🐞 [Report an issue/bug](https://github.com/kleeja-official/kleeja/issues)                                     |
| 🗣 [Chat Support - Discord](https://discord.gg/Mp3XVKP)                                                         |

## Development

### Code formatting

The codebase is formatted with [Prettier](https://prettier.io) (PHP support via
[@prettier/plugin-php](https://github.com/prettier/plugin-php)).

```bash
npm install                       # install the toolchain
npm run format                    # format everything
npm run format:check              # check without writing (what CI runs)
git config core.hooksPath .githooks   # enable the pre-commit / commit-msg hooks
```

Templates (`*.html`) and vendored assets are excluded — see `.prettierignore`.
