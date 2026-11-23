# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/2.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2026-11-23

Site Functionality now picks the safest writable snippet directory automatically on locked-down hosts, with zero manual setup.

### Added

- Read-only **Snippet Storage** status on **Plugins → Site Functionality → Settings** showing the resolved directory, active storage mode, fallback reason (when applicable), and web-access protection check, with a copyable Nginx `deny all` rule if direct HTTP access is still open.
- Automatic loopback HTTP probe when snippets are stored inside `wp-content/rf-snippets/`, surfaced in both Settings and **Tools → Site Health**.

### Changed

- When the parent directory of WordPress (`ABSPATH`) is not writable or blocked by `open_basedir`, Site Functionality now automatically falls back to a locked-down `wp-content/rf-snippets/` folder instead of failing or writing snippets to an ephemeral system temp directory. Snippets previously stored in the system temp folder are migrated forward automatically.
- Explicit `RF_SITE_FUNCTIONALITY_SNIPPETS_DIR` constants in `wp-config.php` continue to take highest precedence.

### Fixed

- Fixed activation and snippet creation failures on managed hosts where `open_basedir` is empty but PHP does not have permission to create a sibling directory next to `/public_html`.
- Fixed sibling directory path resolution on case-sensitive Linux and macOS filesystems when parent folder names contain uppercase letters.

### Security

- Hardened `wp-content/rf-snippets/` against direct web access by writing `index.php`, Apache 2.2/2.4 and LiteSpeed `.htaccess` rules (`Options -Indexes` plus deny rules), and an IIS `web.config`.
- Moved the safe-mode secret from plain-text `safe-mode.key` into a PHP-guarded `safe-mode-key.php` file (`exit;` before the comment) so servers like Nginx that ignore `.htaccess` cannot serve the key over HTTP. Existing keys are migrated automatically and the old plain-text file is deleted.

## [2.0.0] - 2026-10-21

Site Functionality keeps small pieces of PHP as files beside WordPress. A fatal error disables the file that caused it instead of taking the site down.

### Changed

- **Breaking:** The plugin name is Site Functionality and the slug is `rf-site-functionality`. Activate this folder as a new plugin. Snippet files already stored in the `mc-snippets` directory keep loading. Ability names now start with `rf-site-functionality/`.
- The plugin screen uses the Roots and Fruit layout. CodeMirror Theme has a live preview, and Safe Mode is a switch that stops every snippet or turns them back on.
- PHP memory, execution time, the snippet directory, and safe mode are reported on Tools → Site Health. A time limit of 0, or a memory limit of -1, is reported as no limit.

## [1.2.2] - 2026-09-28

The snippet lint check now reads PHP tokens, and the admin Enable action runs it before renaming a file.

### Changed

- Turning a disabled snippet on checks the source first. A file that calls eval, exec, system, shell_exec, passthru, or assert, or that uses include, require, or backticks, stays disabled and shows the error. A space before the parenthesis does not get past the check. Saving a snippet uses the same check.
- A call stored in a variable, such as `$fn()`, is still allowed. So is `call_user_func()`. This is the obvious-call check, not a sandbox.

## [1.2.1] - 2026-09-28

Snippets are no longer published from `wp-content/mc-snippets`.

### Changed

- **Breaking:** The old safe-mode key is deleted. A new key is written in the private folder. An old safe-mode bookmark will not turn snippets off.
- On a normal host the folder sits next to the WordPress directory and is named `mc-snippets-` plus a short hash of the site path. On WordPress Studio, PHP cannot write next to the site, so the folder is in the system temp directory. Set `RF_SITE_FUNCTIONALITY_SNIPPETS_DIR` if you want a permanent directory PHP is allowed to use.
- Files on disk end in `.mcphp`. The editor and the abilities still call a snippet `example.php`.
- A fatal error renames only the snippet that was running. A fatal in a theme or plugin file stays where it is.
- PHP lint runs on every `.php` save and on enable, including a file whose header says css or js. The direct-access guard is inserted as the first real statement.

## [1.2.0] - 2026-09-28

### Added

- Snippet abilities for listing, reading, and changing snippet files.

### Fixed

- Unconditional snippets load when plugin files are already available, and before the theme.

[Unreleased]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v2.1.0...HEAD
[2.1.0]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v1.2.2...v2.0.0
[1.2.2]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/Roots-and-Fruit/rf-site-functionality/releases/tag/v1.2.0
