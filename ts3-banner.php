<?php

declare(strict_types=1);

use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;

final class TeamSpeakBanner
{
	private readonly array $config;
	private readonly ImageDrawer $drawer;
	private TeamSpeak3 $ts3;
	private GdImage $banner;

	public function __construct()
	{
		$this->config = require_once('config.php');
		require_once('drawing.php');
		require_once($this->config['ts3']['library_path']);
	}

	public function generate(): void
	{
		try {
			$this->connectToServer();
			$this->initializeImage();
			$this->renderBanner();
			$this->outputImage();
		} catch (Exception $e) {
			$this->handleError($e);
		}
	}

	private function connectToServer(): void
	{
		$connection = $this->config['ts3']['connection'];
		$queryUri = sprintf(
			'serverquery://%s:%s@%s:%d/?server_port=%d',
			rawurlencode($connection['username']),
			rawurlencode($connection['password']),
			$connection['ip'],
			$connection['query_port'],
			$connection['server_port']
		);

		$this->ts3 = TeamSpeak3::factory($queryUri);
	}

	private function initializeImage(): void
	{
		$backgroundPath = $this->config['display']['background']['path'];
		$imageData = file_get_contents($backgroundPath);

		if ($imageData === false) {
			throw new RuntimeException("Could not read background image: $backgroundPath");
		}

		$this->banner = imagecreatefromstring($imageData);
		if ($this->banner === false) {
			throw new RuntimeException("Invalid background image data");
		}

		// Apply blur if enabled
		if ($this->config['display']['background']['blur']['enabled']) {
			$this->applyBlur();
		}

		[$width, $height] = getimagesize($backgroundPath);
		$this->drawer = new ImageDrawer($this->banner, $width, $height);
	}

	private function applyBlur(): void
	{
		$intensity = $this->config['display']['background']['blur']['intensity'];
		for ($i = 0; $i < $intensity; $i++) {
			imagefilter($this->banner, IMG_FILTER_GAUSSIAN_BLUR);
		}
	}

	private function renderBanner(): void
	{
		$serverInfo = $this->getServerInfo();
		$clientInfo = $this->getClientInfo();

		// Create colors
		$colors = $this->createColors();

		// Draw server header
		$this->drawServerHeader($serverInfo, $colors);

		// Draw statistics
		$this->drawStatistics($serverInfo, $clientInfo, $colors);

		// Draw client information
		if ($clientInfo['connected']) {
			$this->drawClientInfo($clientInfo, $colors);
		}
	}

	private function getServerInfo(): array
	{
		return [
			'name' => $this->sanitizeText($this->ts3->virtualserver_name),
			'uptime' => $this->formatUptime($this->ts3->virtualserver_uptime),
			'version' => strtok($this->ts3->virtualserver_version, ' '),
			'channels' => $this->ts3->virtualserver_channelsonline,
			'clients' => $this->ts3->virtualserver_clientsonline -
				$this->ts3->virtualserver_queryclientsonline -
				$this->config['ts3']['connection']['bots_count'],
			'maxClients' => $this->ts3->virtualserver_maxclients,
			'load' => sys_getloadavg()
		];
	}

	private function getClientInfo(): array
	{
		$clientIp = $this->getClientIp();
		$clientInfo = ['connected' => false];
		$lastJoined = ['time' => PHP_INT_MAX, 'user' => ''];
		$adminCount = 0;

		foreach ($this->ts3->clientList() as $client) {
			if ($client->client_type) {
				continue;
			}

			$connectedTime = $client->connection_connected_time;
			if ($connectedTime < $lastJoined['time']) {
				$lastJoined = [
					'time' => $connectedTime,
					'user' => $this->sanitizeText($client->client_nickname)
				];
			}

			if ($client->getProperty('connection_client_ip') === $clientIp) {
				$clientInfo = [
					'connected' => true,
					'nickname' => $this->sanitizeText($client->client_nickname),
					'connected_time' => $this->formatConnectedTime($connectedTime),
					'total_connections' => $client->client_totalconnections,
					'upload' => $client->connection_bandwidth_sent_last_minute_total,
					'download' => $client->connection_bandwidth_received_last_minute_total,
					'country' => strtolower($client->client_country),
					'groups' => array_map('intval', explode(',', $client->client_servergroups))
				];
			}

			if (in_array($this->config['admin']['group_id'],
				explode(',', $client->client_servergroups))) {
				$adminCount++;
			}
		}

		$clientInfo['lastJoined'] = $lastJoined;
		$clientInfo['adminCount'] = $adminCount;

		return $clientInfo;
	}

	private function createColors(): array
	{
		return [
			'primary' => imagecolorallocatealpha(
				$this->banner,
				...$this->config['display']['text']['colors']['primary']
			),
			'secondary' => imagecolorallocatealpha(
				$this->banner,
				...$this->config['display']['text']['colors']['secondary']
			),
			'box' => imagecolorallocatealpha(
				$this->banner,
				...$this->config['display']['box']['color']
			)
		];
	}

	private function drawServerHeader(array $serverInfo, array $colors): void
	{
		$logo = $this->config['server']['logo']['path'];
		if (empty($logo)) {
			$this->drawer->drawText(
				$this->config['display']['text']['sizes']['server'],
				$colors['primary'],
				$this->config['display']['text']['font'],
				$serverInfo['name'],
				2,
				2.2
			);
		} else {
			$this->drawer->drawImage($logo, $this->config['server']['logo']['size']);
		}
	}

	private function outputImage(): void
	{
		header('Content-Type: image/png');
		imagepng($this->banner);
		imagedestroy($this->banner);
	}

	private function handleError(Exception $e): void
	{
		header('Content-Type: text/html; charset=utf-8');
		die(sprintf('<pre><b>Error Code: %d</b> %s</pre>',
			$e->getCode(),
			htmlspecialchars($e->getMessage())
		));
	}

	private function sanitizeText(string $text): string
	{
		return $this->config['display']['text']['ascii_only']
			? preg_replace('/[^[:ascii:]]/', '', $text)
			: $text;
	}

	private function formatUptime(string $uptime): string
	{
		return str_replace(
				['D', ':'],
				['d', 'h '],
				substr($uptime, 0, -3)
			) . 'min';
	}

	private function formatConnectedTime(int $msTime): array
	{
		$minutes = (round($msTime / 60000) + 1);
		return [
			'hours' => floor($minutes / 60),
			'minutes' => $minutes % 60
		];
	}

	private function getClientIp(): string
	{
		return $_SERVER['HTTP_CLIENT_IP']
			?? $_SERVER['HTTP_X_FORWARDED_FOR']
			?? $_SERVER['REMOTE_ADDR'];
	}
}

// Execute
$banner = new TeamSpeakBanner();
$banner->generate();