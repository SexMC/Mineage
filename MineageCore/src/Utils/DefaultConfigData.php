<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Utils;

class DefaultConfigData{
	public static function combatLogger() : array{
		return [
			"enable" => true,
			"combat-duration-ticks" => 300,
			"whitelisted-commands" => [
				"ping",
			],
			"enter-combat-message" => "You have been combat tagged with @player, do not log out for 15 seconds or you will die",
			"leave-combat-message" => "Your combat tag has expired, you may now log out",
			"command-blocked-message" => "You cannot use this command while in combat."
		];
	}

	public static function forms() : array{
		return [
			"enable" => true,
			"forms" => [
				"world-picker" => [
					"action" => "world-picker",
					"title" => "world picker",
					"description" => "leave empty for nothing",
					"buttons" => [
						[
							"button-icon" => "url or texture path",
							"button-title" => "WORLD\nPlaying: @players/@max-players",
							"button-world" => "foo",
						]
					]
				],
				"commands" => [
					"action" => "commands",
					"form-type" => "simple",
					"title" => "commands",
					"description" => "say something",
					"buttons" => [
						[
							"button-icon" => "url or texture path",
							"button-title" => "test",
							"button-commands" => [
								"console:tell @player you clicked button 1"
							]
						]
					]
				],
				"custom-form" => [
					"action" => "",
					"form-type" => "custom",
					"title" => "commands",
					"buttons" => [
						[
							"alias" => "@label1",
							"type" => "label",
							"label" => "test",
						],
						[
							"alias" => "slider1",
							"type" => "slider",
							"label" => "slider 1",
							"min" => 1,
							"max" => 10,
							"default" => 1,
							"step" => 1
						],
						[
							"alias" => "dropdown1",
							"type" => "dropdown",
							"label" => "dropdown 1",
							"options" => [
								"option a",
								"option b",
								"option c",
								"option d"
							]
						],
						[
							"alias" => "toggle1",
							"type" => "toggle",
							"label" => "toggle 1",
							"options" => [
								"option a",
								"option b",
								"option c",
								"option d"
							]
						],
						[
							"alias" => "input1",
							"type" => "input",
							"label" => "input 1",
						]
					],
					"commands" => [
						"console:tell @player you set slider 1 to @slider1",
						"console:tell @player you set dropdown 1 to @dropdown1",
						"console:tell @player you set toggle 1 to @toggle1",
						"console:tell @player you set input 1 to @input1"
					]
				]
			]
		];
	}

	public static function hotbar() : array{
		return [
			"enable" => true,
			"hotbar-worlds" => ["world"],
			"cancel-drop" => true,
			"cancel-slot-change" => true,
			"items" => [
				[
					"slot" => 2,
					"item" => "compass:1:&eCUSTOM NAME HERE:&alore1;&blore2:unbreaking:3:sharpness:3",
					"interact-action" => "commands",
					"commands" => [
						"console:tell @player you used compass",
						"player:me has clicked compass"
					],
				],
				[
					"slot" => 4,
					"item" => "clock:1:&eCUSTOM NAME HERE:&alore1;&blore2",
					"interact-action" => "form",
					"form-name" => "world-picker"
				]
			]
		];
	}

	public static function kits() : array{
		return [
			"enable" => true,
			"kits" => [
				"default" => [
					"armor" => [
						"diamond_helmet:1:default::protection:1:unbreaking:1",
						"diamond_chestplate:1:default::protection:1:unbreaking:1",
						"diamond_leggings:1:default::protection:1:unbreaking:1",
						"diamond_boots:1:default::protection:1:unbreaking:1"
					],
					"items" => [
						"diamond_sword:1:default::sharpness:1",
						"steak:64"
					],
					"effects" => [
						"speed:300:1:true",
						"strength:300:1:true"
					],
					"commands" => [
						"console:tell @player you equipped the default kit",
					]
				]
			]
		];
	}

	public static function worlds() : array{
		return [
			"enable" => true,
			"lobby-world" => "world",
			"teleport-to-lobby-on-join" => true,
			"worlds" => [
				"foo" => [
					"max-players" => 10,
					"join-success" => "Successfully joined world.",
					"join-limit-reached" => "Cannot join world: limit reached.",
					"world-kit" => "default",
					"equip-kit-on-join" => true,
					"allow-rekit" => true,
					"disable-hunger" => true,
					"kill-message" => "@killer[@pot-killer] killed @player[@pot-player]",
					"force-standard-nametags" => true
				],
			]
		];
	}

	public static function messages() : array{
		return [
			"join-message" => "&e@player joined the game",
			"quit-message" => "&e@player left the game",
			"ping-command" => [
				"ping-self" => "Your ping: @pingms",
				"ping-other" => "@player's ping: @pingms",
				"player-not-found" => "'@player' was not found, is player online?"
			],
			"hub-command" => [
				"success" => "§aYou've been teleported to the hub"
			],
			"info-command" => [
				"player-not-found" => "'@player' was not found, is player online?",
				 "output" => "- @player's Info -\nDevice: @device\nControl: @control\nIP: @ip\nCountry: @country\nTimezone: @timezone\nProxy: @proxy"
			]
		];
	}

	public static function discord() : array{
		return [
			"webhook" => "",
		];
	}

	public static function reports() : array{
		return [
			"log-to-discord" => true,
			"report-cooldown" => 60,
			"report-cooldown-message" => "Cannot report for another @cooldown seconds.",
			"report-confirmation" => "Your report has been sent: @origin reports @player with reason: @reason",
			"report-to-staff" => "@origin reports @player with reason: @reason",
			"report-player-offline" => "Cannot report '@player' because they are not online.",
			"report-no-self" => "You cannot report yourself.",
			"report-discord-message" => [
				"embeds" => [
					[
						"title" => "Report from @origin",
						"color" => 0xff0000,
						"fields" => [
							[
								"name" => "player",
								"value" => "@player",
								"inline" => false
							],
							[
								"name" => "reason",
								"value" => "@reason",
								"inline" => false
							]
						]
					]
				]
			]
		];
	}

	public static function chat() : array{
		return [
			"clearchat" => [
				"clear" => "@sender cleared chat."
			],
			"globalchat" => [
				"mute" => "@sender muted global chat.",
				"unmute" => "@sender unmuted global chat.",
				"muted" => "The chat has been muted by staff."
			],
			"cooldown" => [
				"time" => 5,
				"message" => "Please wait @seconds seconds to use chat again."
			],
			"staffchat" => [
				"format" => "[STAFF] @player: @message"
			]
		];
	}

	public static function moderation() : array{
		return [
			"freeze" => [
				"cannot-freeze-again" => "Please unfreeze the current player before freezing another one.",
				"player-not-found" => "'@player' was not found, is player online?",
				"cannot-freeze-already-frozen" => "You cannot freeze that player, they are frozen by @staff.",
				"cannot-freeze-self" => "You cannot freeze yourself.",
				"cannot-freeze-staff" => "You cannot freeze staff.",
				"freeze-player-message" => "You have been frozen by @staff.",
				"freeze-staff-message" => "You froze @player.",
				"cannot-unfreeze-self" => "You cannot unfreeze yourself.",
				"cannot-unfreeze-not-frozen" => "That player is not frozen.",
				"cannot-unfreeze-not-frozen-by" => "You cannot unfreeze that player, they are frozen by @staff.",
				"unfreeze-player-message" => "You have been unfrozen by @staff.",
				"unfreeze-staff-message" => "You unfroze @player.",
				"unfreeze-staff-message-logout" => "@player has been unfrozen due to logging out.",
				"unfreeze-player-message-logout" => "@staff logged out, you have been unfrozen.",
				"command-blocked" => "This command is blocked because you are frozen.",
				"world" => "foo",
				"whitelisted-commands" => ["tell", "w", "msg"],
				"scoreboard" => [
					"title" => "SCREENSHARE",
					"lines" => [
						"name: @staff",
						"time: @time",
						"suspect: @player"
					]
				]
			],
			"antivpn" => [
				"kick-message" => "§cAccess Denied: Your connection is being blocked due to the use of a VPN.\n§cTo request a VPN Bypass, please join our Discord server and contact the network administrators for assistance.\n§7Find out more: discord.gg/mineagenetwork",
			]
		];
	}

	public static function broadcast() : array{
		return [
			"period" => 30,
			"messages" => [
				"thanks for playing mineage!",
				"cheating is not allowed!",
				"frawnh is a nigger"
			]
		];
	}
}
