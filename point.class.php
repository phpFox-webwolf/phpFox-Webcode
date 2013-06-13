<?php
/**
 * [PHPFOX_HEADER]
 */

defined('PHPFOX') or exit('NO DICE!');

/**
 * 
 * 
 * @copyright  	
 * @author  		Webwolf
 * @package  	
 * @version 		
 */
class User_Component_Block_Points extends Phpfox_Component
{
	/**
	 * Class process method wnich is used to execute this component.
	 */
	public function process()
	{
		//Set How many records to return
		$iLimit = 9;				

		$aUsers = Phpfox::getLib('database')->select('u.user_id, ua.activity_points, ' . Phpfox::getUserField())  
				->from(Phpfox::getT('user'), 'u')
				->join(Phpfox::getT('user_activity'), 'ua', 'u.user_id = ua.user_id')  
				->order('ua.activity_points DESC')
				->limit($iLimit)
				->execute('getSlaveRows'); 		

		$this->template()->assign(array(
				'sHeader' => Phpfox::getPhrase('user.point_title'),
				'aUsers' => $aUsers
			)
		);

		return 'block';

  }

}

?>
