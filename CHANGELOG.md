# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/2.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v1.2.2...v2.0.0
[1.2.2]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/Roots-and-Fruit/rf-site-functionality/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/Roots-and-Fruit/rf-site-functionality/releases/tag/v1.2.0
