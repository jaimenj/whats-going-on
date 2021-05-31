<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>

<div class="wgo-popup-info" id="wgo-popup-info">
    <button id="x2">X</button>
    <div class="wgo-popup-info-content" id="wgo-popup-info-content">
    Something..
    </div>
</div>

<style>
.wgo-popup-info {
    visibility: hidden;
    position: fixed;
    width: calc(100% - 20px);
    height: calc(100% - 60px);
    top: 0;
    left: 0;
    background-color: #808080;
    z-index: 9999;
    margin: 45px 10px 10px 10px;
    border-radius: 5px;
    box-shadow: 5px 5px 5px black;
}
.wgo-popup-info-content {
    height: calc(100% - 10px);
    /*background-color: white;*/
    margin: 5px;
    /*padding: 20px;*/
    border-radius: 5px;
    overflow-x: auto;
}
#x2 {
    position: absolute;
    right: 15px;
    top: 3px;
    background: transparent;
    color: black;
    border: 0px;
    font-weight: bold;
    margin-right: 0px;
    margin-top: 0px;
    height: 30px;
    font-size: 30px;
}
pre { 
    overflow-x: auto;
    white-space: pre-wrap;
    white-space: -moz-pre-wrap;
    white-space: -pre-wrap;
    white-space: -o-pre-wrap;
    word-wrap: break-word;
}
</style>

<script>
document.getElementById('x2').addEventListener('click', () => {
    document.getElementById('wgo-popup-info').style.visibility = 'hidden';
});
</script>