//	Insert this code into a new plugin
//	Go to the AdminCP -> Extension -> Plugins -> Create new plugin.
//    Fill out the request fields in this way:
//    Product: phpfox
//    Module: user
//    Title: send_welcome_pm
//    Hook: user.service_process_add_end
//    Active: Yes
//    PHP code: Copy this entire file into the text block
//
//
//	Before activating the plugin:
//	Create a new language phrase for your subject (old message system)
//    Product: phpfox (or core)
//    Module: core
//    Varname: welcome_internal_mail_subject
//    Text: Welcome to {site}
//	The other language phrase (core.welcome_email_content) should already be defined for the welcome email	



//Prepare the Preview text
$sMessagePreview = str_replace(array('&lt;', '&gt;'), array('<', '>'), Phpfox::getPhrase('core.welcome_email_content'));
$sMessagePreview = Phpfox::getLib('parse.bbcode')->cleanCode($sMessagePreview);
$sMessagePreview = strip_tags($sMessagePreview);
$sMessagePreview = Phpfox::getLib('parse.input')->clean($sMessagePreview, 255);

//Get the first administrator as the sender
$iAdminId = $this->database()->select('user_id')->from(Phpfox::getT('user'))->where('user_group_id = 1')->limit(1)->order('joined ASC')->execute('getField');
//Load the subject variable
$sSubject = Phpfox::getPhrase('core.welcome_internal_mail_subject', array('site' => Phpfox::getParam('core.site_title')));

//Determine if you are using threaded or legacy message handling
if(Phpfox::getParam('mail.threaded_mail_conversation'))
{
	//If using threaded messages, set up users for this thread
	$aUserInsert=array($iAdminId, $iId);
	//Define the hash_id for the mail_thread table
	$sHashId = md5(implode('', $aUserInsert));

	//Insert the new thread record into the mail_thread table
	$iMid = $this->database()->insert(Phpfox::getT('mail_thread'), array(
			'hash_id' => $sHashId,
			'time_stamp' => PHPFOX_TIME
		)
	);

	//Define a mail_thread_user record for each thread user
	foreach ($aUserInsert as $iUserId)
	{
		$this->database()->insert(Phpfox::getT('mail_thread_user'), array(
				'thread_id' => $iMid,
				'is_read' => ($iUserId == $iAdminId ? '1' : '0'),
				'is_sent' => ($iUserId == $iAdminId ? '1' : '0'),
				'is_sent_update' => ($iUserId == $iAdminId ? '1' : '0'),
				'user_id' => (int) $iUserId
			)
		);
	}

	//Insert the mail_thread_text record for your welcome message
	$iTextId = $this->database()->insert(Phpfox::getT('mail_thread_text'), array(
			'thread_id' => $iMid,
			'time_stamp' => PHPFOX_TIME,
			'user_id' => $iAdminId,
			'text' => Phpfox::getLib('parse.input')->prepare(Phpfox::getPhrase('core.welcome_email_content')),
			'is_mobile' => ('0')
		)
	);

	//Now that we have a textId we can update the last_id column in the mail_thread entry that we made above
	$this->database()->update(Phpfox::getT('mail_thread'), array('last_id' => (int) $iTextId), 'thread_id = ' . (int) $iMid);
}
else
{
	//This is for the old message system so we build an array for the mail table entry
	$aInsert = array(
	    'parent_id' => 0,
	    'subject' => $sSubject,
	    'preview' => $sMessagePreview,
	    'owner_user_id' => $iAdminId,
	    'viewer_user_id' => $iId,
	    'viewer_is_new' => 1,
	    'time_stamp' => PHPFOX_TIME,
	    'time_updated' => PHPFOX_TIME,
	    'total_attachment' => 0,
	);

	//Insert the mail table entry
	$iMailId = $this->database()->insert(Phpfox::getT('mail'), $aInsert);

	//Build an array for the mail_text table
	$aContent = array(
	    'mail_id' => $iMailId,
	    'text' => Phpfox::getLib('parse.input')->clean(Phpfox::getPhrase('core.welcome_email_content')),
	    'text_parsed' => Phpfox::getLib('parse.input')->prepare(Phpfox::getPhrase('core.welcome_email_content'))
	);

	//Insert the mail_text entry
	$this->database()->insert(Phpfox::getT('mail_text'), $aContent);
}
