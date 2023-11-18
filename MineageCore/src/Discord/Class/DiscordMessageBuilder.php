<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Discord\Class;

class DiscordMessageBuilder{
	protected ?string $content = null;
	protected ?string $avatar = null;
	protected ?string $username = null;
	/** @var DiscordEmbed[] */
	protected array $embeds = [];

	public function toArray() : array{
		return [
			"username" => $this->username,
			"content" => $this->content,
			"avatar_url" => $this->avatar,
			"embeds" => array_map(static fn(DiscordEmbed $embed) => $embed->toArray(), $this->embeds),
		];
	}

	public function addEmbed(DiscordEmbed $embed) : void{
		$this->embeds[] = $embed;
	}

	public function setContent(string $content) : static{
		$this->content = $content;
		return $this;
	}

	public function setAvatar(string $avatar) : static{
		$this->avatar = $avatar;
		return $this;
	}

	public function setUsername(string $username) : static{
		$this->username = $username;
		return $this;
	}

	public function getContent() : ?string{
		return $this->content;
	}

	public function getAvatar() : ?string{
		return $this->avatar;
	}

	public function getUsername() : ?string{
		return $this->username;
	}
}
