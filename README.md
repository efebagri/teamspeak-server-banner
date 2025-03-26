# ⚠️ This project is not ready to use. ⚠️

![teamspeak-banner](https://user-images.githubusercontent.com/122950707/227748547-fd59aa07-4143-456b-872a-d5cf56ed578b.gif)

# 🔊 TeamSpeak 3 Banner

Fully customisable and responsive TeamSpeak 3 banner with server & client information.

# 🚀 Installation

1. Create a new folder `ts3-banner` inside the root folder of your web server and clone the repo inside of it.
   ```sh
   git clone https://github.com/efebagri/teamspeak3-banner.git ts3-banner
   ```
2. Configure the [`config.php`](https://github.com/efebagri/teamspeak3-banner/blob/main/config.php) file:
   ```php
   'ts3' => [
        'library_path' => '../vendor/planetteamspeak/ts3-php-framework/libraries/TeamSpeak3/TeamSpeak3.php',
        'connection' => [
            'username' => 'serveradmin',
            'password' => 'PASSWORD',
            'ip' => '127.0.0.1',
            'query_port' => 10011,
            'server_port' => 9987,
            'bots_count' => 0
        ]
    ],
   ```

3. Add the URL of the banner to your TeamSpeak Server via the TeamSpeak 3 Client:
   ```
   Banner Gfx URL: http://.../ts3-banner/ts3-banner.php
   ```
   Set the Gfx Interval to 60 for minute-based refresh:
   ```
   Gfx Interval: 60
   ```

# 📑 Features

- [X] Fully Customizable
- [X] Great Design
- [X] Supports Translation
- [X] Client-Side Information (e.g., upload/download stats, nickname, online time)
- [X] Server-Side Information (e.g., server name, version, uptime, active clients)
- [ ] Modular background options
- [ ] Advanced font customization options

# 🙏 Thanks to:

### 🧑🏻‍🤝‍🧑🏻 Contributors

* Dennis Abrams
* Efe Bagri

### 🚧 Used Open-Source Projects

* [TS3 PHP Framework](https://github.com/planetteamspeak/ts3phpframework)
