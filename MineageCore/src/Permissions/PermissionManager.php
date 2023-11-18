<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Permissions;

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager as IPermissionManager;

class PermissionManager{
	public function registerDefaultPermissions() : void{
		$default_permissions = [
			[DefaultPermissions::ROOT_USER, PermissionNodes::MINEAGE_COMMAND_DEFAULT],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_COMMAND_FREEZE],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_COMMAND_UNFREEZE],
			[DefaultPermissions::ROOT_USER, PermissionNodes::MINEAGE_COMMAND_PING],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_COMMAND_INFO],
			[DefaultPermissions::ROOT_USER, PermissionNodes::MINEAGE_COMMAND_HUB],
			[DefaultPermissions::ROOT_USER, PermissionNodes::MINEAGE_COMMAND_REKIT],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_SERVER_LIMIT_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_WORLD_LIMIT_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_COMBATLOGGER_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_PROTECTION_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_CHANNEL_STAFF],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_CHANNEL_REPORTS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_COMMAND_CLEARCHAT],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_GLOBAL_CHAT_MUTE_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_CHAT_COOLDOWN_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_FREEZE_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_ANTIVPN_BYPASS],
			[DefaultPermissions::ROOT_OPERATOR, PermissionNodes::MINEAGE_COMMAND_STAFFCHAT]
		];

		foreach($default_permissions as [$root_permission, $default_permission]){
			IPermissionManager::getInstance()->addPermission(new Permission($default_permission));
			IPermissionManager::getInstance()->getPermission($root_permission)->addChild($default_permission, true);
		}
	}
}
