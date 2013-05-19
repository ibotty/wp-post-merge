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

/*
 $one contains the one post, $another the another post to merge.
 $one_data is the data to display.

 $fields has the fields that should be merged.

 it needs to set a 'pm-nonce'-nonce and pass every merged field in POST data
 'pm_field' (note the underscore!). it should still preserve pm-one and
 pm-another.
 */

 $one_data = (object) $one_data;
 $another_data = (object) $another_data;

function pm_echo_display_for_field($field, $record) {
  echo "<textarea style='width: 100%;' readonly='readonly'>";
  switch ($field) {
  case 'post_author':
    $userdata = get_userdata($record->$field);
    echo $userdata->user_nicename;
    break;
  default:
    echo $record->$field;
  }
  echo "</textarea>";
}

function pm_echo_input_for_field($field, $one_data, $another_data, $default="") {
  // set the fields to empty string, if not set.
  if (! isset($one_data->$field))
    $one_data->$field = '';
  if (! isset($another_data->$field))
    $another_data->$field = '';

  switch ($field){
  case 'ID':
    pm_echo_select_input($field, array($one_data->$field=>$one_data->$field, $another_data->$field=>$another_data->$field, 'new'=>__('generate new post')), 'new');
    break;
  case 'post_status':

  case 'post_title': case 'post_password': case 'guid':
    echo "<input type='text' name='pmp-$field'></input>";
    break;
  case 'post_date': case 'post_date_gmt': case 'post_modified': case 'post_modified_gmt':
    $values = array($one_data->$field, $another_data->$field, date('Y-m-d H:i:s'));
    pm_echo_select_input($field, $values, $default);
    break;
  case 'post_author':
    $values = array();
    foreach (array($one_data->$field, $another_data->$field) as $key)
      if ($key)
        $values[$key] = get_userdata($key)->user_nicename;
    $cur_user = wp_get_current_user();
    $values[$cur_user->ID] = $cur_user->user_nicename;
    pm_echo_select_input($field, $values, $default);
    break;
  default:
    echo "<textarea style='width: 100%;' name='pmp-$field'>$default</textarea>";
  }
}

/**
 * echos a select box to choose from.
 */
function pm_echo_select_input($field, $values, $default=null) {
  echo "<select name='pmp-$field'>";
  foreach ($values as $id => $value) {
    echo "<option value='$id'";
    if ($id === $default)
      echo " selected='selected'";
    echo ">$value</option>";
  }
  echo "</select>";
}

/**
 * echos an ajaxified diff view
 * (most suitable for big fields)
 */
function pm_echo_diff_input($field, $one_data, $another_data, $default="") {
  echo "<a id='pm-diff-$field-link' href='#'>elaborate</a>
    <textarea id='pmp-$field'>$default</textarea>
    <script type='text/javascript'>
      pm_diff_$field = {
        one:     JSON.parse('".json_encode($one_data)."'),
        another: JSON.parse('".json_encode($another_data)."')
      };
    </script>
    ";
}

?>

<a href='javascript:' onclick='jQuery(".pm-hidden-field").toggle()'>
  Show/hide internal fields.
</a>


<form action='' method='post'>
  <?php wp_nonce_field('pm-nonce');?>

  <table class="widefat fixed" cellspacing="0">
    <thead>
      <tr>
        <th scope="col"class="">
          <span>Field</span>
        </th>
        <th scope="col" class="">
          <span>The one post</span>
        </th>
        <th scope="col" class="">
          <span>The other post</span>
        </th>
        <th scope="col" class="">
          <span>The merged post</span>
        </th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($fields as $field) {

  /*
   * the function to return needs the following signature.
   * $display_f = function($field, $rec){...};
   *
   * the default is pm_echo_display_for_field
   */
  $display_func = apply_filters('pm_displayfield_func', 'pm_echo_display_for_field', $field, $one_data, $another_data);

  /*
   * the function to return needs the following signature.
   * $merge_f = function($field, $rec1, $rec2, $default){...};
   *
   * the default is pm_echo_input_for_field.
   * note pm_echo_select_input and pm_echo_diff_input.
   */
  $mergefunc = apply_filters('pm_mergefield_func', 'pm_echo_input_for_field', $field, $one_data, $another_data);

  $hidden = apply_filters('pm_hidden_field', false, $field, $one_data, $another_data);

  echo "<tr";
  if ($hidden)
      echo " class='pm-hidden-field' style='display:none;'";
  echo ">";
  echo "<td>$field</td>";
  echo "<td class='pm-one-field pm-field-$field'>";
  if (isset($one_data->$field))
    call_user_func($display_func, $field, $one_data);
  echo "</td>";
  echo "<td class='pm-another_data-field pm-field-$field'>";
  if (isset($another_data->$field))
    call_user_func($display_func, $field, $another_data);
  echo "</td>";
  echo "<td class='pm-merged-field pm-field-$field'>";
  if (isset($one_data->$field, $another_data->$field))
    call_user_func($mergefunc, $field, $one_data, $another_data,
      $one_data->$field === $another_data->$field ? $one_data->$field: "");
  else if (isset($one_data->$field))
    call_user_func($mergefunc, $field, $one_data, $another_data, $one_data->$field);
  else if (isset($another_data->$field))
    call_user_func($mergefunc, $field, $one_data, $another_data, $another_data->$field);
  echo "</td>";
  echo "</tr>";
}
?>
    </tbody>
  </table>

  <div class="submit-div">
    <input type="submit" value="<?php echo __("Merge Posts"); ?>" />
  </div>

</form>

<script language='javascript' >
jQuery('textarea').autoResize({
  maxHeight: 500,
  minHeight: 0,
  extraSpace: 16,
  animate: false});
</script>
<!-- vim: set ft=php.html ts=2: -->
