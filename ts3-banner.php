<?php

declare(strict_types=1);

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
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

    /**
     * Generates an output by connecting to the server, initializing an image, rendering a banner,
     * and outputting the final image. Handles any exceptions that occur during the process.
     *
     * @return void
     */
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

    /**
     * Establishes a connection to the TeamSpeak 3 server using the configuration provided.
     * Constructs a server query URI based on connection details and initializes the TeamSpeak3 instance.
     *
     * @return void
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
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

    /**
     * Initializes the image for display, including loading, validating, and processing the background image.
     *
     * This method reads the background image from the configured path,
     * validates the image data, applies optional blur effects if enabled,
     * and prepares an ImageDrawer instance for rendering.
     *
     * @return void
     */
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

    /**
     * Applies a blur effect to the background image based on the configured intensity.
     *
     * This method uses the Gaussian blur filter multiple times, as specified by
     * the blur intensity in the configuration, to achieve the desired blurring effect.
     *
     * @return void
     */
	private function applyBlur(): void
	{
		$intensity = $this->config['display']['background']['blur']['intensity'];
		for ($i = 0; $i < $intensity; $i++) {
			imagefilter($this->banner, IMG_FILTER_GAUSSIAN_BLUR);
		}
	}

    /**
     * Renders the banner by drawing server and client details along with their statistics.
     *
     * This method retrieves server and client information, creates necessary colors,
     * and draws the following components on the banner:
     * - Server header with relevant details
     * - Server and client statistics
     * - Additional client information if the client is connected
     *
     * @return void
     */
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

    /**
     * Retrieves detailed information about the server, including its name, uptime, version,
     * active channels, client statistics, maximum client capacity, and system load.
     *
     * The method gathers data from the TeamSpeak 3 server instance, processes and formats
     * it where necessary, and returns the information as an associative array.
     *
     * @return array An associative array containing server details:
     *               - 'name': string (sanitized server name),
     *               - 'uptime': string (formatted server uptime),
     *               - 'version': string (server version),
     *               - 'channels': int (number of active channels),
     *               - 'clients': int (number of connected clients excluding bots),
     *               - 'maxClients': int (maximum number of allowed clients),
     *               - 'load': array (system load averages).
     */
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

    /**
     * Retrieves information about the current client and server state, including connection status,
     * the most recently joined user, and the number of administrators connected.
     *
     * This method collects data about the client's connection to the server, such as their nickname,
     * connection time, bandwidth usage, and server groups. It also identifies the last user to join
     * the server and counts the number of connected administrators.
     *
     * @return array An associative array containing client information and server state details:
     *               - 'connected': (bool) Whether the client is connected.
     *               - 'nickname': (string) The sanitized nickname of the client, if connected.
     *               - 'connected_time': (string) The formatted duration since the connection started, if connected.
     *               - 'total_connections': (int) The total number of times the client has connected, if connected.
     *               - 'upload': (int) The bandwidth upload in the last minute, if connected.
     *               - 'download': (int) The bandwidth download in the last minute, if connected.
     *               - 'country': (string) The lowercase ISO country code of the client, if connected.
     *               - 'groups': (array) A list of server group IDs the client belongs to, if connected.
     *               - 'lastJoined': (array) An associative array with details of the last joined user:
     *                   - 'time': (int) The connection time in milliseconds of the last joined user.
     *                   - 'user': (string) The sanitized nickname of the last joined user.
     *               - 'adminCount': (int) The total number of connected administrators.
     */
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

    /**
     * @return array
     */
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

    /**
     * @param array $serverInfo
     * @param array $colors
     * @return void
     */
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

    /**
     * @return void
     */
	private function outputImage(): void
	{
		header('Content-Type: image/png');
		imagepng($this->banner);
		imagedestroy($this->banner);
	}

    /**
     * @param Exception $e
     * @return void
     */
	private function handleError(Exception $e): void
	{
		header('Content-Type: text/html; charset=utf-8');
		die(sprintf('<pre><b>Error Code: %d</b> %s</pre>',
			$e->getCode(),
			htmlspecialchars($e->getMessage())
		));
	}

    /**
     * @param string $text
     * @return string
     */
	private function sanitizeText(string $text): string
	{
		return $this->config['display']['text']['ascii_only']
			? preg_replace('/[^[:ascii:]]/', '', $text)
			: $text;
	}

    /**
     * @param string $uptime
     * @return string
     */
	private function formatUptime(string $uptime): string
	{
		return str_replace(
				['D', ':'],
				['d', 'h '],
				substr($uptime, 0, -3)
			) . 'min';
	}

    /**
     * @param int $msTime
     * @return array
     */
	private function formatConnectedTime(int $msTime): array
	{
		$minutes = (round($msTime / 60000) + 1);
		return [
			'hours' => floor($minutes / 60),
			'minutes' => $minutes % 60
		];
	}

    /**
     * Retrieves the IP address of the client making the request.
     *
     * @return string The client's IP address, determined from server variables.
     */
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