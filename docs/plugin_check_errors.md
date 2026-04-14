# Plugin Check Report

**Plugin:** Slow Plugin Scanner
**Generated at:** 2026-04-14 07:26:20


## `admin/ui.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 132 | 78 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 222 | 33 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |

## `.env.example`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | hidden_files | Hidden files are not permitted. |  |

## `config.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |

## `readme.txt`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | trademarked_term | The plugin name includes a restricted term. Your chosen plugin name - "Slow Plugin Scanner" - contains the restricted term "plugin" which cannot be used at all in your plugin name. |  |

## `slow-plugin-scanner.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | trademarked_term | The plugin name includes a restricted term. Your chosen plugin name - "Slow Plugin Scanner" - contains the restricted term "plugin" which cannot be used at all in your plugin name. |  |
| 0 | 0 | WARNING | trademarked_term | The plugin slug includes a restricted term. Your plugin slug - "slow-plugin-scanner" - contains the restricted term "plugin" which cannot be used at all in your plugin slug. |  |
