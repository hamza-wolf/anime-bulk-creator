Multi-Player Anime Scraper Plugin: User Guide for Themesia AnimeStream Theme
This guide explains how to use the Multi-Player Anime Scraper WordPress plugin with the Themesia AnimeStream WordPress Theme to create anime episode posts with multiple streaming players. It covers adding players with an ad-free option, configuring the plugin, and ensuring compatibility with AnimeStream’s features (responsive design, SEO optimization, ab_embedgroup support). The plugin fetches episode data from a self-hosted Anime API and creates draft posts with embedded players. Sub/Dub variations and ad-free labeling are handled automatically for ease of use.
Prerequisites

WordPress: Version 5.0 or higher.
Themesia AnimeStream Theme: Version 2.2.6 or later, supporting the anime post type and ab_embedgroup meta field.
PHP: Version 7.4 or higher (AnimeStream requires PHP 7.1+).
Self-Hosted Anime API: Host your own API instance (see Set Up the Anime API).
Plugin Installed: The multi-player-anime-scraper.php plugin is installed and activated.

Installation
Install the Plugin

Place multi-player-anime-scraper.php in a folder named multi-player-anime-scraper inside wp-content/plugins/.
Alternatively, zip the folder and upload via Plugins > Add New > Upload Plugin.
Activate from Plugins > Installed Plugins.

Avoid Header Errors

If you see "The plugin does not have a valid header," ensure no extra spaces or characters exist before <?php or after ?>.
Save the file in UTF-8 without BOM using a text editor (e.g., VS Code, Notepad++).

Verify AnimeStream Theme

Ensure AnimeStream is active (Appearance > Themes).
Confirm it supports the anime post type and renders ab_embedgroup for player iframes.

Set Up the Anime API
The plugin requires a self-hosted Anime API to fetch episode data:
Host the API

Visit https://github.com/itzzzme/anime-api for instructions.
Deploy on Vercel or Render to get a public URL (e.g., https://your-anime-api.com).
Test an endpoint (e.g., https://your-anime-api.com/api/episodes/kaiju-no-8-season-2-19792) in a browser to ensure it returns JSON data.

Configure the API URL

Go to Anime Episodes > Settings in the WordPress admin.
Enter your API URL in API Base URL (e.g., https://your-anime-api.com).
Click Update API URL.

Adding and Managing Players
The plugin includes two default players (MegaPlay, Vidplay) and supports custom players. You can mark custom players as ad-free, and the plugin automatically handles Sub/Dub variations.
Understanding the Hostname Template and Ad-Free Option
The Hostname Template is the base name for the player (e.g., StreamX). The plugin automatically appends Sub or Dub to create the display name in AnimeStream’s frontend (e.g., player selector or tabs) and admin interface, stored in the ab_embedgroup meta field. The Display Label matches the Hostname Template for simplicity.

Default Players (MegaPlay, Vidplay):
Always include (Ads) in the hostname (e.g., Mega Sub (Ads), Mega Dub (Ads), Vidplay Sub (Ads), Vidplay Dub (Ads)).


Custom Players:
If Ad-Free is checked, append No Ads (e.g., StreamX Sub No Ads, StreamX Dub No Ads).
If Ad-Free is unchecked, use only the base name with Sub/Dub (e.g., StreamX Sub, StreamX Dub).



Player ID and Sub/Dub Handling
Default Players

Use the episode number extracted from the API ID.
Example: For API ID kaiju-no-8-season-2-19792?ep=141988, the ID is 141988.
URL: https://megaplay.buzz/stream/s-2/141988/sub or .../dub.
Hostname: Mega Sub (Ads) or Mega Dub (Ads).

Custom Players

Use the full API episode ID.
Example: For API ID to-be-hero-x-19591?ep=136180, the ID is to-be-hero-x-19591?ep=136180.
URL: https://your-player.com/watch?id=to-be-hero-x-19591?ep=136180&type=sub or ...&type=dub.
Hostname: StreamX Sub No Ads (if ad-free) or StreamX Sub (if not).

Steps to Add a Custom Player

Access Settings:
Go to Anime Episodes > Settings.


Add a Custom Player:
In Add Custom Player, fill out:
Player Name: Base name (e.g., AnimeStream).
URL Template: Streaming URL with {id} placeholder. Example: https://your-player.com/watch?id={id}.
Placeholder: {id} is replaced with the full episode ID (e.g., to-be-hero-x-19591?ep=136180).
Critical: Do not include ?type=sub, &type=sub, or similar in the URL template. The plugin automatically appends &type=sub or &type=dub to avoid malformed URLs (e.g., double & or encoded &#038;).
Description: Admin description (e.g., AnimeStream - High-Quality Streaming).
Ad-Free: Check to append No Ads to the hostname (e.g., AnimeStream Sub No Ads).


Click Add Player.


Example Custom Player:
Player Name: AnimeStream
URL Template: https://your-player.com/watch?id={id}
Description: AnimeStream - High-Quality Streaming
Ad-Free: Checked
Result (for ID to-be-hero-x-19591?ep=136180):
Sub: URL https://your-player.com/watch?id=to-be-hero-x-19591?ep=136180&type=sub, hostname AnimeStream Sub No Ads, display label AnimeStream.
Dub: URL https://your-player.com/watch?id=to-be-hero-x-19591?ep=136180&type=dub, hostname AnimeStream Dub No Ads, display label AnimeStream.


Ad-Free Unchecked:
Sub: Hostname AnimeStream Sub, same URL and display label.




Edit or Delete:
In Custom Players, click Edit or Delete. Default players cannot be modified.



Tips for Proper Player Setup

Test URLs: Verify URLs (e.g., https://your-player.com/watch?id=to-be-hero-x-19591?ep=136180&type=sub) load streams correctly in a browser.
Correct URL Templates: Use {id} and exclude ?type=sub/dub or &type=sub/dub in templates to prevent malformed URLs (e.g., ?ep=136180&#038;type=dub).
Ad-Free Clarity: Use the Ad-Free checkbox to indicate ad-free players in AnimeStream’s frontend.
AnimeStream Compatibility: Ensure AnimeStream renders ab_embedgroup iframes. Contact Themesia if players don’t display.
Unique Names: Use unique player names to avoid conflicts (the plugin auto-generates a unique key).
Clear Names: Choose descriptive names (e.g., AnimeStream) for clarity in AnimeStream’s frontend.

Creating Anime Episodes

Access the Plugin:
Go to Anime Episodes (video icon in the admin menu).


Fill Out the Form:
Anime Title: Enter the anime name (e.g., To Be Hero X).
API Endpoint: Enter the endpoint (e.g., to-be-hero-x-19591). Check your API docs.
Episode Type: Select Sub or Dub (affects player URLs and hostnames).


