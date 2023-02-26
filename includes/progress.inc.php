<?php
/**
 * ListMessenger Pro - Classic Mailing List Management
 * For the most recent version, visit https://listmessenger.com.
 *
 * @copyright 2002-2022 Silentweb https://silentweb.ca
 * @author Matt Simpson <msimpson@listmessenger.com>
 * @license /licence.html ListMessenger Software Licence Agreement
 */
?>
<iframe id="workerFrame" style="width: 0px; height: 0px; border: 0px #000000 solid; margin: 0px" src="./sender.php?qid=<?php echo (int) trim($qid).((trim($action) != '') ? '&action='.trim($action) : ''); ?>"></iframe>
<h1>Sending Message <span style="font-size: 11px">[<?php echo html_encode(checkslashes($_SESSION['message_details']['message_title'], 1)); ?>]</span></h1>
<div id="progressBar" style="width: 95%; height: 15px; background-color: #EEEEEE; border: 1px #CCCCCC solid">
	<div id="progressStatus" style="width: 0%; height: 15px; background-color: #666666; font-weight: bold; color: #EEEEEE; text-align: right"></div>
</div>
<div id="progressText" style="width: 95%" class="progress-text"></div>
<br />
<div id="buttonHTML" style="width: 95%; text-align: right; height: 25px"></div>
<br /><br />
<form>
	<textarea id="errorText" style="display: none; width: 95%; height: 200px; border: 0px; margin: 0px" class="progress-error" readonly="readonly"></textarea>
</form>