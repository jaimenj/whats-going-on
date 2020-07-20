<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap">
    <h2>Regexes errors to review</h2>

    <?php 
    $regexesErrors = file(WGOJNJ_PATH.'inc/waf.errors.log');

    if (!empty($regexesErrors)) {
        ?>

        <p>The preg_match function to check the incoming data failed because of Regexes. Here they are:</p>

        <ul>
            <?php
            foreach ($regexesErrors as $regexError) {
                echo '<li>'.$regexError.'</li>';
            }
            ?>
        </ul>
    <?php
    }
    ?>
    
</div>