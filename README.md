# news-archiver
Automatically archive news

# Installation
`composer require traw/news-archiver`

# Configuration
Using TYPO3's extension settings or the command/ scheduler options

# How it works
The command archives news records based on the configured source folders and archive settings.

It can search for news recursively, select records either by amount, age, or all matching records, and then perform one or both of the available archive actions: move and archive.
* `amount`: Use `archiveNewsAmount` as offset - Keep the number of records configured in `archiveNewsAmount`, archive the rest
* `age`: Use `archiveNewsAmount` as age in days: Records older than X days are archived
* `all`: `archiveNewsAmount` is ignored, all records within `newsRootFolder` / `recursive` are archived

Available archive actions
* `move`: The news record and all related records, like Content, File References etc will be moved as well.
* `archive`: If the news record has no archive date, yet, the time of script execution is set as the records archive date.

`subfolders`: The archived news can be organized into year and/or month subfolders. The archive is created below the configured target page.
If the news folder is within a pagetree with a Site Configuration, the created folders are automatically translated into all active languages of the site.
* `year`: a list of years according to the records datetime is created within the archive
* `month`: same as `year`, within each year folder, a list of month according to the records datetime is created
* `none`: All records are just moved to the `targetPid`, no folders are created

The `limit` option can be used to restrict the number of news records processed during a single run, which is useful when running the command through a scheduler.

Setting `enable` to `0` will completely disable all functionality of the command

## Command
* `vendor/bin/typo3 news-archiver:run [options]`
* via Scheduler module `Execute console commands` task

If you wish to configure multiple command, consider using command options to override some or all settings

## Command/ Scheduler task options
If no option is given, the extension's settings will be used
| Option                           | Description                                                                                                                                                                           |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `-N`, `--newsRootFolder`         | **Source folder(s) for news records.** Comma-separated page UIDs. If empty, news records are fetched from all available folders.                                                      |
| `-r`, `--recursive`              | **Recursion depth when searching for news.** `0` means only the selected folder(s); higher values include nested subfolders.                                                          |
| `-P`, `--targetPid`              | **Target folder for the archive.**                                                              |
| `-A`, `--archiveAction`          | **Defines what happens to the news after they are selected for archiving:** `move`, `archive`, or `both`.                                                                             |
| `-M`, `--archiveMode`            | **Defines how news are selected for archiving:** `amount` archives a specified number of records, `age` archives records based on their age, and `all` archives all matching records. |
| `-a`, `--archiveNewsAmount`      | **Defines the archive threshold.** With `amount`, this is the number of news records to archive; with `age`, it is the age in days.                                                   |
| `-S`, `--subfolders`             | **Defines the archive folder structure:** `none` creates no subfolders, `year` creates year folders, and `month` creates year/month folders.                                          |
| `-l`, `--limit`                  | **Maximum number of news records processed per run.** Useful for limiting the workload of a single CLI/scheduler execution.                                                           |
| `--useDefaultValues`             | **Use the extension's default values instead of the configured settings.**                                                                                                            |
| `-v`, `-vv`, `-vvv`, `--verbose` | **Controls output verbosity:** `-v` for normal verbose output, `-vv` for more detailed output, and `-vvv` for debug output.                                                           |



> [!IMPORTANT]
> Backup your database before running the command


