Post Merge
==============

A WordPress plugin to merge two Posts into each other.

The plugin can be divided into three phases. 
In the first, *Select Posts*, you have to select the posts to merge. 
To select posts, navigate to the posts view in the admin area and use the links
in the horizontal menu below a post.

The second phase, *Merging*, you can edit, how the merged post will look like.

Thirdly, in the *Preparing* stage, the plugin prepares the post to merge.

In the fourth phase, *Real Merging*, Post Merge will fulfill its task for real.
Thus it will update the first post to be merged with the post from phase 2 and
delete the other post.


About the name
--------------

There has always already been nothing to tell about it.


License
--------------

The plugin is licensed under the 2-claused BSD license. See LICENSE for details.


Extending the plugin
--------------

There are some hooks, that influence, what happens when.

Post-Merge supplies some of these hooks itself. They can be enabled as usual or disabled with `remove_action` or `remove_filter` or (later, when this is implemented) using the settings page. See below for a list.

The following filters can be hooked in a `pm_merge` action.

* `pm_enrich_post` is a filter to add additional data a post might have.

* `pm_merge_fields` filters fields to display when merging. You can easily remove fields not to display for merging. Note, that adding fields is not necessary 
Note, that it uses all fields from the post as default (after `pm_enrich_post`). It passes both of the posts to be merged as arguments, if you add the filter accordingly.

The following hooks can be added or removed in a `pm_real_merge` action.

* `pm_prepare_merged_post` is a filter to set specific attributes of the to be saved post.

* `pm_save_post` is an action to save additional fields. The old IDs
are available in the `old_IDs` member of the post.


Internal hooks
--------------

The following filters might be used in `pm_merge_fields`.
* `'pm_remove_id_etc'` to remove the id, the author and the date fields,
 so that a new post will be created with the metadata as if the current user just created the post.

Filters to be called in `pm_prepare_post` might be:
* With a very low priority, `'pm_prepare_basics'` to save all basic fields. 
It will save specific metadata if it is set before. If you want to remove this,
add a filter to `pm_prepare_post` that creates a post in the WordPress database.

The plugin might use these actions in `pm_save_merged_post`.
* `'pm_merge_comments'` to merge comments (to be implemented).

Is there any example code on how to extend it?
---------------

Glad you asked:

```php
# add a field to posts
function my_add_foobar($post) {
  $post->foobar = my_db_get_foobar ($post);
  return $post;
}
# remove post_author field for pages
function my_remove_author($fields, $one, $another) {
  if ($one->post_type == 'page')
    unset($fields["post_author"]);
  return $fields;
}
function my_merge() {
  add_filter('pm_enrich_post', 'my_add_foobar');
  # default priority 10
  add_filter('pm_merge_fields', 'my_remove_author', 10, 3);
}
add_action('pm_merge', 'my_merge');

function my_save_foobar($post) {
  my_db_set_foobar ($post->ID, $post->foobar);
}
function my_real_merge() {
  add_action('pm_save_merged_post','my_save_foobar');
}
add_action('pm_real_merge', 'my_real_merge');
```

Known bugs
--------------

There is one big one. In the Selecting Phase, you cannot use the Search Pages functionality of WordPress. This is a limitation in WordPress. See the [WordPress bug](https://core.trac.wordpress.org/ticket/18851) for details.

Also there is this pretty non-functional page under Tools. You can click on it,
and it will tell you, that you should go away. See that [WordPress bug]((https://core.trac.wordpress.org/ticket/18850).

I guess, there are plenty others. Please file a bug in the [bugtracker](https://github.com/ibotty/wp-post-merge/issues) if you find any.

There no Ajax workflow, because it has not been implemented.
It should be possible using html5 history support or pretty hacky with some for now missing WordPress functionality (at least for WP3.2). Feel free to send patches.


