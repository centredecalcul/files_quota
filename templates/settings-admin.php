<?php
	script('files_quota', ['chosen.jquery.min', 'script']);
	style('files_quota', ['chosen.min', 'style']);
?>

<form id="files_quota" class="section files_quota">
	<h2><?php p($l->t('Files Quota')); ?></h2>
	<p>
    <em>Edit here the default number of files quota and you can also manage the user quota.
    </em>
	</p>
	<div id="defaultfilesquota">
	<p>
		<label for="defaultfilesnumber">Default quota as number of files</label>
		<input type="number" id="defaultfilesnumber" name="defaultquota" width="40px" value="<?php p($_['defaultNbFiles']); ?>"/>
		<input type="button" name="submitNewDefaultQuota" id="submitNewDefaultQuota"
				   value="<?php p($l->t( 'Save' )); ?>"/>
	</p>
	<p>Edit specific user quota</p>
	</div>
    <select class="chosen-select" id="filesquota-user-select" width="200px" name="username" data-placeholder="Select username">
        <option value="0" selected=""></option>
        <?php foreach($_['userList'] as $row):?>
        <option value="<?php p($row['uid']);?>">
           	<?php p($row['uid']);?>
        </option>
        <?php endforeach;?>
    </select>
    <div id="filesquota-user-block" style="display: none;">
    <label for="userquotafiles">New user quota</label>
    <input type="number" id="userquotafiles" name="userquota" width="40px" value=""/>
	<input type="button" name="submitNewUserQuota" id="submitNewUserQuota"
				   value="<?php p($l->t( 'Save' )); ?>"/>
    </div>
</form>