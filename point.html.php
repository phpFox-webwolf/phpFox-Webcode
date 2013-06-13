<?php 
/**
 * [PHPFOX_HEADER]
 * 
 * @copyright  	
 * @author  		Webwolf
 * @package  		
 * @version 		
 */
 
defined('PHPFOX') or exit('NO DICE!'); 

?>

<ul class="block_listing">
{foreach from=$aUsers key=iKey name=friend item=aUser}
	<li style="padding-bottom:0 px;">
		<div class="block_listing_image">
			{img user=$aUser suffix='_20_square' max_width=20 max_height=20}
		</div>
		<div class="block_listing_title" style="padding-left:56px;">
			{$aUser|user:'':'':40}<br />{$aUser.activity_points} Activity Points
		</div>
		<div class="clear"></div>
	</li>
{/foreach}
</ul>

