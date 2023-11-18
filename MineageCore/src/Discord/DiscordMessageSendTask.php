<?php
declare(strict_types=1);

namespace Mineage\MineageCore\Discord;

use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use function curl_init;
use function curl_setopt;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_TIMEOUT;

final class DiscordMessageSendTask extends AsyncTask{
	public function __construct(
		/** @var NonThreadSafeValue<list<array>> */
		private readonly NonThreadSafeValue $messages,
		private readonly string $webhook,
	){}

	public function onRun() : void{
		$curl = $this->prepareCurl();
		foreach($this->messages->deserialize() as $message){
			try{
				$this->send($curl, $message);
			}catch(\RuntimeException $exception){
				echo "[Discord Thread] Failed to send discord message: " . $exception->getMessage() . PHP_EOL;
			}
		}
		curl_close($curl);
	}

	private function prepareCurl() : \CurlHandle{
		$curl = curl_init($this->webhook);
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/json",
			],
			CURLOPT_CONNECTTIMEOUT => 1,
			CURLOPT_TIMEOUT => 3,
			CURLOPT_HEADER => true
		]);
		return $curl;
	}

	private function send(\CurlHandle $curl, array $data) : void{
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($httpCode !== 204){ // NO CONTENT
			throw new \RuntimeException("Discord webhook request failed with HTTP status code " . $httpCode . ": " . $response);
		}
	}
}
