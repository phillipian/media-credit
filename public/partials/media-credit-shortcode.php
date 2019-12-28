<?php
/** Specific rewritten markup for phillipian-wp-2019 WordPress template: https://github.com/phillipian/phillipian-wp-2019 */

// get name and ID attributes from media-credit shortcode, with Phillipian-specific defaults
$a = shortcode_atts(array(
    'name' => 'The Phillipian',
    'id' => 'none'
), $atts);

// generate proper credit text
if ($a['id'] == 'none'){
    $credit = $a['name'];
}
else{
    $authorname = get_the_author_meta('user_firstname',$a['id']) . " " . get_the_author_meta('user_lastname',$a['id']);
    $credit = $authorname . "/The Phillipian";
}

// get the actual image enclosed
preg_match("/<img(.*)\/>/", $content, $array1);

?>

<div class='single-image'><?php echo $array1[0]; ?><div class='media-credit'><span><?php echo $credit; ?></span></div></div><p></p>