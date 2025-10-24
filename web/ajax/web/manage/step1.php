<?php

// Main include
$required_dataset_param = 'domain';
include($_SERVER['DOCUMENT_ROOT']."/ajax/authentication_check.php");

echo 'Ajax script: web / manage, step1, Domain: '.$domain;
?>

<br /><br />
<form id="floating-center-div-form" name="floating-center-div-form" method="post" action="/ajax/web/manage/step2.php">
    <input type="hidden" name="token" value="<?=$_SESSION['token']?>" />
    <input type="hidden" name="dataset[object]" value="<?=$_POST['dataset']['object']?>" />
    <input type="hidden" name="dataset[action]" value="<?=$_POST['dataset']['action']?>" />
    <input type="hidden" name="dataset[subaction]" value="<?=$_POST['dataset']['subaction']?>" />
    <input type="hidden" name="dataset[step]" value="2" />
    <input type="hidden" name="dataset[domain]" value="<?=$_POST['dataset']['domain']?>" />
    <input type="hidden" name="confirm_required" value="1" id="confirm_required" />
    <input type="hidden" name="confirm_required_default_value" value="yes" id="confirm_required_default_value" />
    <input type="hidden" name="confirm_required_title" value="Confirm" id="confirm_required_title" />
    <input type="hidden" name="confirm_required_content" value="Are you sure you want to continue?" id="confirm_required_content" />
    <input type="hidden" name="confirm_required_type" value="confirm" id="confirm_required_type" />
    <input type="hidden" name="confirm_required_variable_label" value="Variable FROM STEP 1" id="confirm_required_variable_label" />
    <input type="hidden" name="confirm_required_variable_name" value="var1_from_step1" id="confirm_required_variable_name" />
    <input type="hidden" name="confirm_required_variable_value" value="val1_from_step1" id="confirm_required_variable_value" />
    <input type="hidden" name="confirm_required_selected_value" value="" id="confirm_required_selected_value" />
    <input type="hidden" name="confirm_required_selected_button" value="yes" id="confirm_required_selected_button" />
    <input type="hidden" name="confirm_required_callback" value="TESTFUNCTION" id="confirm_required_callback" />
    
    <span class="ajax-label ajax-newline">Variable 1:</span>
    <input type="text" name="var1" value="val1" class="vst-input ajax-newline">
    <span class="ajax-label ajax-newline">Variable 2:</span>
    <input type="text" name="var2" value="val2" class="vst-input ajax-newline">
    <button type="submit" class="button ajax-button-margin-top">Submit</button>
</form>