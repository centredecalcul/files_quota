<?php
script('files_quota', 'settings-admin');
?>

<div class="section"	id="filesQuota">
	<h2><?php p($l->t('Files Quota')); ?></h2>
	    <p>
        <em>Edit here the default number of files quota and you can also manage the user quota.
        </em>
</p>

<div id="defaultFilesQuota">
	<p>
		<label for="defaultfilesnumber">Default quota as number of files</label>
		<input type="number" id="defaultfilesnumber" width="20px" value="20000" />
	</p>
	<p> Edit specific user quota
	</p>