
  	//Define vars for user and friend.  initialize admin test var
		$iFriendId = $aFriend['friend_user_id'];
		$iUserId = $aFriend['user_id'];
		$iFriendIsAdmin = 0;

		//Check user table for usergroup for target friend
		$iFriendIsAdmin = $this->database()->select('COUNT(*)')
			->from(Phpfox::getT('user'), 'u')
			->where('u.user_id = ' . (int) $iFriendId . ' AND u.user_group_id = 1')
			->execute('getSlaveField');		

		//If target friend was admin, restore the friend table entries
		if ($iFriendIsAdmin > 0)
		{

			$this->database()->insert(Phpfox::getT('friend'), array(
					'list_id' => 0,
					'user_id' => $iFriendId,
					'friend_user_id' => $iUserId,
					'time_stamp' => PHPFOX_TIME
				)
			);

			$this->database()->insert(Phpfox::getT('friend'), array(
					'list_id' => 0,
					'user_id' => $iUserId,
					'friend_user_id' => $iFriendId,
					'time_stamp' => PHPFOX_TIME
				)
			);

			//Also restore the counters	
			Phpfox::getService('friend.process')->updateFriendCount($iId, $iFriendId);
			Phpfox::getService('friend.process')->updateFriendCount($iFriendId, $iId);
		}

