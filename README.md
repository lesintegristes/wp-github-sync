# GitHub Sync

A WordPress plugin to synchronize a directory with a repository hosted on GitHub.

## Requirements

- PHP [exec()](http://php.net/manual/en/function.exec.php) function
- Git
- Shell access to server

## Installation

### 1: Setup the git repository on your server.

You can use this plugin to keep a directory synchronized. This directory
can be a theme, a plugin, the whole wp-content, or a directory outside
of your WordPress installation.

Your PHP user (Apache with mod_php_, php-fpm, etc.) should have the
permission to write in the directory. Use the “Git Read-Only” URL if you
want to clone with a system user and pull with your PHP user easily.

If the repository is private, or if you need to commit / push from this
directory, you will need to generate an SSH key for your PHP user, and
to configure git. Please refer to the git / GitHub documentation.

    $ cd /path/to/wp-content/themes
    $ git clone http://github.com/my_name/my_theme.git ./my_theme

### 2: Install the plugin

Follow the classic WordPress procedure to install and activate the
plugin.

Define the following settings in your `wp-config.php`:

```php
<?php

// The repository directory (required)
define('GITHUB_SYNC_DIR', __DIR__. '/wp-content/themes/my_theme');

// The repository GitHub ID (required)
define('GITHUB_SYNC_REPO_ID', 'my_name/my_theme');

// GitHub IP addresses (optional, default below)
define('GITHUB_SYNC_IPS', '207.97.227.253, 50.57.128.197, 108.171.174.178');

// The branch to keep synchronized (optional, default: "master")
define('GITHUB_SYNC_BRANCH', 'master');

// The git remote (optional, default: "origin")
define('GITHUB_SYNC_REMOTE', 'origin');

// Enable logging to debug the plugin (optional, default: NULL)
define('GITHUB_SYNC_LOG', NULL);
```

### 3: Add a GitHub WebHook URL

On your GitHub repository, add a WebHook URL ([GitHub instructions](https://help.github.com/articles/post-receive-hooks))
with your WordPress base URL, followed by `/github-sync/`, e.g.: `http://your-wordpress-website.com/github-sync/`

## License

MIT
