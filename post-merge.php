<?php
/*
Plugin Name: Post Merge
Plugin URI: http://github.com/ibotty/post-merge
Description: A plugin to merge two post.
Version: 0.1
Author: Tobias Florek <me@ibotty.net>
Author URI: http://github.com/ibotty
License: BSD
*/
?>

<?php
/*
Copyright (c) 2011, Tobias Florek.  All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

  1. Redistributions of source code must retain the above copyright notice,
     this list of conditions and the following disclaimer.

  2. Redistributions in binary form must reproduce the above copyright notice,
     this list of conditions and the following disclaimer in the documentation
     and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDER ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once(ABSPATH . '/wp-admin/includes/plugin.php');
require_once(ABSPATH . '/wp-admin/includes/template.php');

if (! class_exists("PostMerge")) {
class PostMerge {

  function __construct() {
    $this->init();
  }

  function init() {
    $this->merge_page_slug = 'pm-merge';

    register_activation_hook (__FILE__, array ($this, 'install'));
    register_deactivation_hook (__FILE__, array ($this, 'deinstall'));

    add_action('admin_init', array($this, 'admin_init'));
    add_filter("admin_head", array($this, "admin_head"), 10, 2);
    add_filter("admin_menu", array($this, "admin_menu"));

    add_filter("post_row_actions", array($this, "row_actions"), 10, 2);
    add_filter("page_row_actions", array($this, "row_actions"), 10, 2);
  }

  function install() {
  }

  function deinstall() {
  }

  function admin_init() {
    wp_register_script('jquery.fn.autoresize',
      plugins_url('/vendor/jquery.fn.autoResize/jquery.autoresize.js', __FILE__));

    wp_register_script('pm-merge-script', plugins_url('/pm-merge.js', __FILE__));
    wp_register_style('pm-merge-style', plugins_url('/pm-merge.css', __FILE__));
  }

  function admin_head() {
    // add css to highlight selected candidate row
    if (isset($_GET['pm-candidate'])) {
      $candidate = intval($_GET['pm-candidate']);
      echo "<style type='text/css'> #post-$candidate {background:rgba(255,0,0,0.2);} </style>";
    }
  }

  function row_actions($actions, $post) {
    $cur_url = $_SERVER['REQUEST_URI'];
    $merge_url = menu_page_url($this->merge_page_slug, false);

    # if a merge candidate is already set
    if (isset($_GET['pm-candidate'])) {
      $candidate = intval($_GET['pm-candidate']);

      # remove candidate status if same post
      if ($post->ID === $candidate) {
        $link = remove_query_arg('pm-candidate', $cur_url);
        $displaytext = 'Cancel merge';
      } else { # merge
        $link = esc_url(add_query_arg(array(
          'pm-one'=>$candidate, 'pm-another'=>$post->ID), $merge_url));
        $displaytext = 'Merge with selected '.$_GET['post_type'];
      }
    } else { # no merge candidate set
      $link = esc_url(add_query_arg('pm-candidate', $post->ID, $cur_url));
      $displaytext = 'Merge';
    }
    $str = '<a class="pm-merge" href="'.$link.'">'.__($displaytext).'</a>';
    $actions["posts_merge"] = $str;
    return $actions;
  }

  function admin_menu() {
    $page = add_management_page(__('Merge Posts'), __('Merge Posts'),
      'edit_published_posts', $this->merge_page_slug, array($this, 'tools_page'));
    add_action("admin_print_styles-$page",
      array($this, 'merge_styles_register'));
  }

  function merge_styles_register() {
    wp_enqueue_script('jquery.fn.autoresize');
    wp_enqueue_script('pm-merge-script');
    wp_enqueue_style('pm-merge-style');
  }

  function tools_page() {
    global $wpdb;

    if (! isset($_REQUEST['pm-one']) || ! isset($_REQUEST['pm-another']))
      wp_die(__('Please select two posts to merge in the post overview.'));

    $one = get_post(intval($_REQUEST['pm-one']));
    $another = get_post(intval($_REQUEST['pm-another']));

    if (! current_user_can('edit_others_posts') &&
      ($one->post_author != $another->post_author || $one-post_author != get_current_user_id()))
        wp_die(__('You do not have sufficient permissions to access this page.'));


    // compare and select for merge
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {

      do_action('pm_merge');

      $one_data = array_filter(apply_filters("pm_change_data", (array) $one, $one));
      $another_data = array_filter(apply_filters("pm_change_data", (array) $another, $another));

      // somehow array_unique is required...
      $fields = array_unique(array_merge(array_keys($one_data), array_keys($another_data)));

      $fields = apply_filters("pm_merge_fields", $fields, $one, $another);

      // can (and should) use $fields, $one, $one_data and $another, $another_data
      include "includes/merge.php";
    }
    // POST: save merged post
    else {
      check_admin_referer('pm-nonce');

      $old_post_ids = array($_REQUEST["pm-one"], $_REQUEST["pm-another"]);
      $merged_post = array();

      foreach ($_POST as $key=>$val)
        // add fields that begin with 'pmp-' to the post
        if (substr($key, 0, strlen('pmp-')) == 'pmp-')
          $merged_post[substr($key, strlen('pmp-'))] = $val;

      $merged_post = (object) $merged_post;
      $merged_post = apply_filters('pm_prepare_merged_post' , $merged_post);

      $wp_post_cols = array('ID', 'post_author', 'post_date',
        'post_date_gmt', 'post_content', 'post_content_filtered',
        'post_title', 'post_excerpt', 'post_status', 'post_type',
        'comment_count', 'comment_status', 'ping_status', 'post_password',
        'post_name', 'to_ping', 'pinged', 'post_modified',
        'post_modified_gmt', 'post_parent', 'menu_order', 'post_mime_type',
        'guid');

      // the "main" part of the new post
      $wp_post = array();
      foreach ($wp_post_cols as $key)
        if (isset($merged_post->$key)) {
          $wp_post[$key] = $merged_post->$key;
          unset($merged_post->$key);
        }

      $new_id = $wp_post["ID"];

      if ($new_id == "new") {
        $new_id = wp_insert_post($wp_post);
        foreach ($old_post_ids as $oldid)
          wp_trash_post($oldid);
      } else {
        // check, whether someone did something bad first (changed ids)
        if (! in_array($new_id, $old_post_ids))
          wp_die("tsetsetse! nice try though.");

        $old_post_ids = array_diff($old_post_ids, array($new_id));

        // there is only one id left in old_post_ids
        wp_trash_post(current($old_post_ids));
        $old_post_ids[] = wp_update_post($wp_post);
      }

      do_action('pm_save_post', $merged_post, $new_id);

      include 'includes/saved.php'; // can (and should use) $old_post_ids and $new_id
    }
  }

  function tools_styles() {
    wp_enqueue_style('pm_tools.css');
  }
}
}
new PostMerge();
?>
