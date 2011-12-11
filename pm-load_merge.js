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
 * pm-load_merge.js
 *
 * @author Tobias Florek <me@ibotty.net>
 *
 * candidate selection are dynamified with this script.
 * it goes to great length to fix most urls to carry the candidate to the
 * next screen.
 *
 * requires a post-list url of the form
 * .../edit.php?post_type=$type&....
 * i.e., the post_type parameter is the first parameter.
 */
var pm_select = pm_select || new function () {
  var candidate = -1;

  // remove http(s|)://server:port/ from base url
  var baseurl = '/' + document.URL.split('/').slice(3).join('/');

  this.init = function() {
    this.candidate = id_from_url(document.URL);
    jQuery('.pm-merge').click(select_clicked);
  };

  select_clicked = function (event) {
    event.preventDefault();
    id = jQuery(this).attr('id').substring('pm-'.length);
    if (candidate == id) {
      candidate = -1;
      baseurl = jQuery(this).attr('href');
      dehighlight_candidate();
    }
    else if (candidate == -1) {
      candidate = id;
      baseurl = baseurl + '&pm-candidate=' + id;
      highlight_candidate();
    } else {
      window.location = jQuery(this).attr('href');
    }
    fix_links();
  };

  fix_links = function () {
    // search form
    jQuery('input[name="_wp_http_referer"]').attr('value', baseurl);
    jQuery('#wpbody form').attr('action', baseurl);

    jQuery('#wpbody div[class="wrap"] ul[class="subsubsub"] li a, '+
        '#wpbody > div[class="wrap"] > a ,'+
        '.view-switch > a ,'+
        '.pagination-links > a ,'+
        '.wp-list-table > thead > tr > th[class="manage-column"] a ,'+
        '.wp-list-table > tfoot > tr > th[class="manage-column"] a'
        ).each(fix_elem_href);

    // merge links
    jQuery('.pm-merge').each(function (index) {
      id = jQuery(this).attr('id').substring('pm-'.length);
      if (candidate == -1) {
        jQuery(this).text('Merge');
        jQuery(this).attr('href', baseurl + '&pm-candidate=' + id);
      } else if (candidate == id) {
        jQuery(this).text('Cancel Merge');
        jQuery(this).attr('href', clean_url(baseurl));
      } else {
        jQuery(this).text('Merge with previousy selected');
        jQuery(this).attr('href', merge_link(id));
      }
    });
  };

  highlight_candidate = function () {
  };
  dehighlight_candidate = function () {
  };

  fix_elem_href = function () {
    elem = jQuery(this);
    clean = clean_url(elem.attr('href'));
    if (candidate == -1)
      url = clean;
    else
      url = clean + '&pm-candidate=' + candidate;
    elem.attr('href', url);
  };

  clean_url = function (url) {
    return url.split('&').filter(function(str) {
      return str.substring(0, 'pm-'.length) !== 'pm-';
    }).join('&');
  }

  id_from_url = function(url) {
    var arr = url.split('&').filter(function (str) {
      return (str.substring(0, 'pm-candidate='.length) === 'pm-candidate=' ||
        str.substring(0, 'pm-another'.length) === 'pm-another');
    });
    id = -1;
    if (arr.length > 0)
      id = arr[0].split('&')[0].split('=')[1];
    return id;
  };

  merge_link = function(id) {
    return '/tmp/'+candidate+id;
  };

}();

jQuery(document).ready(pm_select.init);
