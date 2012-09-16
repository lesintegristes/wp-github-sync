<?php
/*
Plugin Name: GitHub Sync
Plugin URI: http://www.lesintegristes.net/
Description: Synchronize a local git branch with a remote, using the GitHub Post-Receive Hooks.
Author: Pierre Bertet
Version: 1.0.0
Author URI: http://pierrebertet.net/
*/

// Notice messages
add_action('admin_notices', function(){
  $messages = array();
  if (!function_exists('exec')) {
    $messages[] = 'the PHP <code>exec()</code> function needs to be activated.';
  }
  if (!defined('GITHUB_SYNC_DIR')) {
    $messages[] = 'you have to define the <code>GITHUB_SYNC_DIR</code> setting.';
  }
  if (!defined('GITHUB_SYNC_REPO_ID')) {
    $messages[] = 'you have to define the <code>GITHUB_SYNC_REPO_ID</code> setting.';
  }
  foreach ($messages as $message) {
    echo '<div class="error"><p>The GitHub Sync plugin is not working: '.$message.'</p></div>';
  }
}, 0);

// Authorized IPs (default: GitHub IPs)
if (!defined('GITHUB_SYNC_IPS')) {
  define('GITHUB_SYNC_IPS', '207.97.227.253, 50.57.128.197, 108.171.174.178');
}

// Git branch (default: master)
if (!defined('GITHUB_SYNC_BRANCH')) {
  define('GITHUB_SYNC_BRANCH', 'master');
}

// Git remote (default: origin)
if (!defined('GITHUB_SYNC_REMOTE')) {
  define('GITHUB_SYNC_REMOTE', 'origin');
}

// Log file (default: NULL)
if (!defined('GITHUB_SYNC_LOG')) {
  define('GITHUB_SYNC_LOG', NULL);
}

// Repo directory (no default, required)
if (!defined('GITHUB_SYNC_DIR')) {
  return;
}

// Repo GitHub ID, owner/project (eg. bpierre/wp-github-sync)
if (!defined('GITHUB_SYNC_REPO_ID')) {
  return;
}

function log_msg($msg) {
  if (GITHUB_SYNC_LOG !== NULL) {
    file_put_contents(GITHUB_SYNC_LOG, $msg . "\n", FILE_APPEND);
  }
}

function check_request() {

  // HTTP method
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    log_msg('Error: the request method is not POST');
    return FALSE;
  }

  // Authorized IPs
  $authorized_ips = array_map(function($ip){ return trim($ip); }, explode(',', GITHUB_SYNC_IPS));
  if (!in_array($_SERVER['REMOTE_ADDR'], $authorized_ips)) {
    log_msg('Error: IP not authorized ('. $_SERVER['REMOTE_ADDR'] .')');
    return FALSE;
  }

  // Payload parameter
  if (!isset($_POST['payload'])) {
    log_msg('Error: missing "payload" parameter.');
    return FALSE;
  }

  return TRUE;
}

function update_repository($raw_content) {
  $content = json_decode($raw_content);

  if ($content == NULL
      || !property_exists($content, 'ref')
      || !property_exists($content, 'repository')
      || !property_exists($content->repository, 'name')
      || !property_exists($content->repository, 'owner')
      || !property_exists($content->repository->owner, 'name')) {
    log_msg('Error: malformed JSON.');
    return;
  }

  if ($content->ref === 'refs/heads/'.GITHUB_SYNC_BRANCH // Branch updated
      && "{$content->repository->owner->name}/{$content->repository->name}" === GITHUB_SYNC_REPO_ID) { // Valid repository
    chdir(GITHUB_SYNC_DIR);
    exec('git pull '.escapeshellarg(GITHUB_SYNC_REMOTE).' '.escapeshellarg(GITHUB_SYNC_BRANCH));
    log_msg('Repository updated.');
  } else {
    log_msg('Error: wrong branch (configured: '. GITHUB_SYNC_BRANCH .', pushed: '. end(explode('/', $content->ref)) .')');
  }
}

/* Adding the custom URL */

// First step: flush rewrite rules on activation.
register_activation_hook(__FILE__, function(){
  flush_rewrite_rules(FALSE);
});

// Second step: add a new rewrite rule.
// This new rewrite rule should redirect to an existing file.
// We use index.php with a query var.
add_filter('rewrite_rules_array', function($rules) use($wp_rewrite) {
  $new_rules = array('^github-sync\/?$' => 'index.php?github_sync=1');
  return $new_rules + $rules;
});

// But this query var is not valid, so we need to add it manually.
add_filter('query_vars', function($qvars) {
  $qvars[] = 'github_sync';
  return $qvars;
});

// And we finally intercept the request, based on the query.
add_action('template_redirect', function(){
  if (get_query_var('github_sync') == '1') {
    log_msg("\n\nNew update request. Check...");
    if (check_request()) {
      log_msg('All check tests passed. Update repository...');
      update_repository(stripcslashes($_POST['payload']));
    } else {
      wp_die(__('You are not authorized to view this page.'), '', array( 'response' => 403 ) );
    }
  }
});
