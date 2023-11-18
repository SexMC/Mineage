<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Permissions;

interface PermissionNodes{
	public const MINEAGE_COMMAND_DEFAULT = "mineage.command.default";
	public const MINEAGE_COMMAND_FREEZE = "mineage.command.freeze";
	public const MINEAGE_COMMAND_UNFREEZE = "mineage.command.unfreeze";
	public const MINEAGE_COMMAND_PING = "mineage.command.ping";
	public const MINEAGE_COMMAND_INFO = "mineage.command.info";
	public const MINEAGE_COMMAND_REKIT = "mineage.command.rekit";
	public const MINEAGE_COMMAND_HUB = "mineage.command.hub";
	public const MINEAGE_COMMAND_STAFFCHAT = "mineage.command.staffchat";
	public const MINEAGE_COMMAND_CLEARCHAT = "mineage.command.clearchat";

	public const MINEAGE_WORLD_LIMIT_BYPASS = "mineage.world.limit.bypass";
	public const MINEAGE_SERVER_LIMIT_BYPASS = "mineage.server.limit.bypass";
	public const MINEAGE_COMBATLOGGER_BYPASS = "mineage.combatlogger.bypass";
	public const MINEAGE_PROTECTION_BYPASS = "mineage.protection.bypass";
	public const MINEAGE_GLOBAL_CHAT_MUTE_BYPASS = "mineage.globalchat.mute.bypass";
	public const MINEAGE_CHAT_COOLDOWN_BYPASS = "mineage.chat.cooldown.bypass";
	public const MINEAGE_FREEZE_BYPASS = "mineage.freeze.bypass";
	public const MINEAGE_ANTIVPN_BYPASS = "mineage.antivpn.bypass";

	public const MINEAGE_CHANNEL_STAFF = "mineage.channel.staff";
	public const MINEAGE_CHANNEL_REPORTS = "mineage.channel.reports";
}
