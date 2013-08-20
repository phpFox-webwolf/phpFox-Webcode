<?php
 
/*
STEWARD WAS HERE
Portions of this work created by stewfoxdev.com (steward at phpfox)
Licensed under creative commons (attribution 3 unported)
see http://creativecommons.org/licenses/by/3.0/deed.en...
You are free to modify and use for your purposes but please leave this credit in your work.
*/ 
/**
*
*
* @author Steward/Webwolf
* @package User Privacy Presets/tools
* @version v1.0
*/
 
define('PHPFOX_DIR', dirname(dirname(__FILE__)) . PHPFOX_DS);
 
define('PHPFOX_START_TIME', array_sum(explode(' ', microtime())));
 
// Require phpFox Init
require(PHPFOX_DIR . 'include' . PHPFOX_DS . 'init.inc.php');
 
/*
* Only the super admin can run this
*/
if (phpFox::getUserId() != 1 )
{
die('UserId not authorized.  You must be logged in to your site as the main administrator.');
}
 
class dbfix extends Phpfox_Service
{
	public function run()
	{
		$oRequest = Phpfox::getLib('request');
		$db = PhpFox::getLib('database');
 
		// Limit per page, start offset at zero
		$iOffset = (int)$oRequest->get('offset',0);
		$sAction = $oRequest->get('Confirm','Start');
		$iLimit = 200;
		$aPrivacySettings = array();
		$iPrivacySetting = 0;

		//Set form token for version 3xx
		if((int)substr(Phpfox::getVersion(), 0, 1) < 3)
		{
			$s1='v2_no_token';
			$s2='v2_no_token';
		}
		else
		{
			$s1=Phpfox::getTokenName();
			$s2=Phpfox::getService('log.session')->getToken();	
		}
 
		// Run only if Userpresets is present
		if(!phpFox::isModule('Userpresets'))
		{
			die('User Preset addon must be present.');
		}

		// Get Userpreset parameters and build the privacy array
		$aSettings = Phpfox::getService('admincp.setting')->search("product_id = 'Preset_New_User'");
		foreach($aSettings as $aSetting)
		{
			$aParts=explode('__', $aSetting['var_name']);
			if(phpFox::isModule($aParts[0]))
			{
				$sUserPrivacy = str_replace('__', '.', $aSetting['var_name']);
				$sGetParam = $aSetting['module_id'] . '.' . $aSetting['var_name'];

				if($aSetting['type_id'] == 'boolean')
				{
					if(Phpfox::getParam($sGetParam) === false)
					{
						$aPrivacySettings[$sUserPrivacy] = 1; 
					}
				}
			}
		}

		if($sAction=='Start')
		{
			//add confirm form
			$iTotUsers = $db->select('COUNT(*)')
				->from(Phpfox::getT('user'))
				->execute('getField');
			$iTotPrivacy = $db->select('COUNT(*)')
				->from(Phpfox::getT('user_notification'))
				->execute('getField');
			$iTotNewPrivacy = count($aPrivacySettings);

			$sWarn = 'This action will remove approximately ' . $iTotPrivacy . 
				' Records from ' . $iTotUsers . ' users from the user notification table and 
				replace them with approximately ' . ($iTotUsers*$iTotNewPrivacy) . 
				' records.  The new settings will be taken from the parameters that 
				you have set in the New User Privacy Setting Module.  <br /><br />
				This will not affect the operation of PhpFox but it will nullify any notification 
				settings that your users have set in favor of the ones that you will be setting.
				<br /><br />Do you want to continue?';

			echo '
			<div style="width:500px;margin:0px auto;text-align:left;padding:15px;border:1px solid #333;background-color:#eee;">
			<div> ' . $sWarn . ' </div>
			<form method="POST" name="form" id="form" action="http://' . Phpfox::getParam('core.host') . '/tools/dbfixNOTIFICATION.php">
			<div><input type="hidden" name="' . $s1 . '[security_token]" value="' . $s2 . '" /></div>
			<div style="width:400px;margin:0px auto;text-align:right;padding:15px;background-color:#eee;">
			<input type="submit" name="Confirm" value="Continue" />
			<input type="submit" name="Confirm" value="Cancel" />
			</div>
			</form>
			</div>';
		}

		if($sAction == 'Cancel')
		{
			die("No Records Changed");
		} 

		// Empty notification table at start of batch
		if($sAction == 'Continue' && $iOffset == 0)
		{
			$db->query('TRUNCATE '.Phpfox::getT('user_notification'));
			echo 'Processing records from ' . $iOffset . ' to ' . ($iOffset+$iLimit) . '<br />';
		}
 
		if($sAction == 'Continue')
		{
			// For each user
			$aRows = $this->database()->select('user_id')
				->from(Phpfox::getT('user'))
				->order('user_id')
				->limit($iOffset,$iLimit)
				->execute('getSlaveRows');
 
			$iCount=0;
			foreach($aRows AS $row)
			{
				++$iCount;
	 			$userid=$row['user_id'];
				//echo '<br>'.$userid;
 
				$s='';
				foreach($aPrivacySettings AS $k => $v)
				{
					$s.="($userid, '$k'),";
				}
 
				$s='INSERT INTO '.
				Phpfox::getT('user_notification').
				" (`user_id`, `user_notification`) VALUES".
				substr($s,0,-1);
 
				$db->query($s);
			}
 
			// Did do a full batch?
			if ($iCount == $iLimit)
			{
				// Get another batch
				$iOffset += $iLimit;


				echo 'Processing records from ' . $iOffset . ' to ' . ($iOffset+$iLimit) . '<br />';
				echo '
				<form method="POST" name="form2" id="form2" action="http://'.Phpfox::getParam('core.host').'/tools/dbfixNOTIFICATION.php?offset='.$iOffset.'">
				<div><input type="hidden" name="' . $s1 . '[security_token]" value="' . $s2 . '" /></div>
				<input type=hidden name="offset" value="' . $iOffset . '">
				<input type=hidden name="Confirm" value="Continue">
				</form>
				<script language=javascript>document.form2.submit();</script>
				';
				exit;
			}
			// count < limit we are done
			echo '<hr><h1>' . ($iCount + $iOffset) . ' Records Processed</h1>';
			return;
		}
	}
 
}
 
$dbfix=new dbfix();
$dbfix->run();
 
