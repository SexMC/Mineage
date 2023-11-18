<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Moderation\Task;

use InvalidArgumentException;
use Mineage\MineageCore\Moderation\PlayerIPInfo;
use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\AssumptionFailedError;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function curl_setopt_array;
use function is_string;
use function json_decode;
use function var_dump;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

final class IPInfoFetchTask extends AsyncTask{
	private const TLS_KEY_PLAYER = "player";
	private const TLS_KEY_CALLBACK = "callback";
	private const TLS_KEY_LOGGER = "logger";

	private static \CurlHandle $curl;
	private readonly string $ip;

	public function __construct(Player $player, \Closure $callback, ?\Logger $logger = null){
		$this->ip = $player->getNetworkSession()->getIp();
		$this->storeLocal(self::TLS_KEY_PLAYER, $player);
		$this->storeLocal(self::TLS_KEY_CALLBACK, $callback);
		$this->storeLocal(self::TLS_KEY_LOGGER, $logger);
	}

	public function onRun() : void{
		$curl = curl_init("http://ip-api.com/json/$this->ip?fields=country,timezone,proxy");
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 1,
			CURLOPT_TIMEOUT => 3,
		]);
		$result = curl_exec($curl);
		if($result === false){
			$this->setResult(curl_error($curl));
		}else{
			$result = json_decode($result, true);
			$this->setResult(new PlayerIPInfo($result["country"], $result["timezone"], $result["proxy"]));
		}
	}

	public function onCompletion() : void{
		/** @var Player $player */
		$player = $this->fetchLocal(self::TLS_KEY_PLAYER);
		if(!$player->isConnected()){
			return;
		}

		try{
			$logger = $this->fetchLocal(self::TLS_KEY_LOGGER);
		}catch(InvalidArgumentException){
			$logger = null;
		}

		$result = $this->getResult();
		if($result instanceof PlayerIPInfo){
			$callback = $this->fetchLocal(self::TLS_KEY_CALLBACK);
			$callback($player, $result);
		}elseif(is_string($result)){
			$logger?->error("Failed to fetch IP info for player {$player->getName()}. Error: $result");
		}else{
			throw new AssumptionFailedError("Task result has unexpected type");
		}
	}

	private function getCurlHandle() : \CurlHandle{
		if(isset(self::$curl)){
			return self::$curl;
		}
		var_dump("a");
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 1,
			CURLOPT_TIMEOUT => 3,
		]);
		self::$curl = $curl;

		return $curl;
	}
}
