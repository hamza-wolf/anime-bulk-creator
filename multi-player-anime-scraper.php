<?php
/*
Plugin Name: Multi-Player Anime Scraper
Plugin URI: https://github.com/hamza-wolf/anime-bulk-creator
APi URI: https://github.com/itzzzme/anime-api
Description: Bulk anime episode creator with simplified player setup, ad-free option, embed preservation, random slug, and flexible suffix/URL structure.
Version: 1
Author: Hami
Author URI: https://example.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: multi-player-anime-scraper
*/

if (!defined('ABSPATH')) {
    exit;
}

class MultiPlayerAnimeScraper
{
    private $api_base_url_default = 'https://your-anime-api.com'; // Dummy example
    private $default_players = [
        [
            'key' => 'megaplay',
            'url_template' => 'https://megaplay.buzz/stream/s-2/{num}/{type}',
            'hostname_template' => 'Mega',
            'display_label' => '⚡ Mega',
            'display_desc' => 'MegaPlay - Fast Streaming'
        ],
        [
            'key' => 'vidplay',
            'url_template' => 'https://vidwish.live/stream/s-2/{num}/{type}',
            'hostname_template' => 'Vidplay',
            'display_label' => '🎬 Vidplay',
            'display_desc' => 'VidWish - Fast Streaming'
        ]
    ];
    private $suffixes_sub = [
        'English Sub',
        'English Subtitle',
        'English Subbed'
    ];
    private $suffixes_dub = [
        'English Dub',
        'English Dubbed'
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('wp_ajax_create_episodes', [$this, 'handle_ajax']);
        add_action('wp_ajax_preview_episodes', [$this, 'handle_ajax']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('pre_get_posts', [$this, 'order_posts_by_episode']);
        add_filter('manage_posts_columns', [$this, 'add_columns']);
        add_action('manage_posts_custom_column', [$this, 'show_columns'], 10, 2);
        add_filter('manage_edit-post_sortable_columns', [$this, 'sortable_columns']);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Multi-Player Anime Scraper',
            'Anime Episodes',
            'manage_options',
            'multi-player-anime-scraper',
            [$this, 'admin_page'],
            'dashicons-video-alt3',
            31
        );
    }

    public function add_settings_menu()
    {
        add_submenu_page(
            'multi-player-anime-scraper',
            'Settings',
            'Settings',
            'manage_options',
            'mps-settings',
            [$this, 'settings_page']
        );
    }

    public function enqueue_assets($hook)
    {
        if ('toplevel_page_multi-player-anime-scraper' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');

        wp_add_inline_script('jquery', "
            var multiPlayerScraper = {
                ajaxUrl: '" . admin_url('admin-ajax.php') . "',
                nonce: '" . wp_create_nonce('episodes_nonce') . "'
            };
        ");
    }

    public function admin_page()
    {
        $categories = get_categories(['hide_empty' => false]);
        $anime_series = get_posts([
            'post_type' => 'anime',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        $players = $this->get_players();
?>
        <div class="wrap">
            <style>
                .anime-scraper-container {
                    max-width: 800px;
                    margin: 20px 0;
                    background: #fff;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .anime-scraper-container h1 {
                    color: #333;
                    text-align: center;
                    margin-bottom: 30px;
                    font-size: 28px;
                    border-bottom: 3px solid #0073aa;
                    padding-bottom: 15px;
                }

                .form-section {
                    margin-bottom: 25px;
                    padding: 20px;
                    background: #f9f9f9;
                    border-radius: 6px;
                    border-left: 4px solid #0073aa;
                }

                .form-section h3 {
                    margin-top: 0;
                    color: #0073aa;
                    font-size: 18px;
                }

                .form-row {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                }

                .form-row .form-group {
                    flex: 1;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #333;
                }

                .form-group input,
                .form-group select {
                    width: 100%;
                    padding: 10px 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                    box-sizing: border-box;
                }

                .form-group input:focus,
                .form-group select:focus {
                    outline: none;
                    border-color: #0073aa;
                    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
                }

                .type-selector {
                    display: flex;
                    gap: 10px;
                    margin-top: 8px;
                }

                .type-btn {
                    flex: 1;
                    padding: 12px;
                    border: 2px solid #ddd;
                    border-radius: 4px;
                    background: #fff;
                    cursor: pointer;
                    text-align: center;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }

                .type-btn:hover {
                    border-color: #0073aa;
                    background: #f0f8ff;
                }

                .type-btn.active {
                    background: #0073aa;
                    color: white;
                    border-color: #0073aa;
                }

                .player-checkboxes {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                    margin-top: 15px;
                }

                .player-option {
                    display: flex;
                    align-items: center;
                    padding: 15px;
                    border: 2px solid #ddd;
                    border-radius: 6px;
                    background: #fff;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .player-option:hover {
                    border-color: #0073aa;
                    background: #f0f8ff;
                }

                .player-option.selected {
                    border-color: #0073aa;
                    background: #e6f3ff;
                }

                .player-option input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    margin-right: 15px;
                    cursor: pointer;
                }

                .player-info {
                    flex: 1;
                }

                .player-name {
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 4px;
                    font-size: 16px;
                }

                .player-desc {
                    color: #666;
                    font-size: 14px;
                }

                .button-group {
                    display: flex;
                    gap: 15px;
                    margin-top: 30px;
                }

                .btn {
                    flex: 1;
                    padding: 12px 20px;
                    border: none;
                    border-radius: 4px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .btn-primary {
                    background: #0073aa;
                    color: white;
                }

                .btn-primary:hover {
                    background: #005a87;
                }

                .btn-success {
                    background: #46b450;
                    color: white;
                }

                .btn-success:hover {
                    background: #3a9940;
                }

                .btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }

                .preview-section {
                    margin-top: 30px;
                    padding: 20px;
                    background: #f9f9f9;
                    border-radius: 6px;
                    border-left: 4px solid #46b450;
                    display: none;
                }

                .preview-section.show {
                    display: block;
                }

                .preview-section h3 {
                    color: #46b450;
                    margin-top: 0;
                }

                .episode-preview {
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 15px;
                    margin-bottom: 15px;
                }

                .episode-title {
                    font-weight: 600;
                    color: #0073aa;
                    margin-bottom: 8px;
                    font-size: 16px;
                }

                .episode-info {
                    color: #666;
                    margin-bottom: 5px;
                    font-size: 14px;
                }

                .episode-players {
                    display: flex;
                    gap: 8px;
                    margin-top: 10px;
                }

                .player-badge {
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: 500;
                    color: white;
                }

                .player-badge.megaplay {
                    background: #4caf50;
                }

                .player-badge.vidplay {
                    background: #ff9800;
                }

                .player-badge.custom {
                    background: #2196f3;
                }

                .status-message {
                    padding: 15px;
                    border-radius: 4px;
                    margin-top: 20px;
                    font-weight: 500;
                }

                .status-success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .status-error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .progress-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.7);
                    display: none;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                }

                .progress-modal.show {
                    display: flex;
                }

                .progress-content {
                    background: white;
                    border-radius: 8px;
                    padding: 30px;
                    max-width: 400px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                }

                .progress-content h3 {
                    color: #333;
                    margin-bottom: 20px;
                }

                .progress-bar {
                    width: 100%;
                    height: 20px;
                    background: #f0f0f0;
                    border-radius: 10px;
                    overflow: hidden;
                    margin: 20px 0;
                }

                .progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #0073aa, #005a87);
                    border-radius: 10px;
                    transition: width 0.3s ease;
                    width: 0%;
                }

                .hidden {
                    display: none !important;
                }

                @media (max-width: 768px) {
                    .anime-scraper-container {
                        margin: 10px;
                        padding: 20px;
                    }

                    .form-row {
                        flex-direction: column;
                    }

                    .button-group {
                        flex-direction: column;
                    }
                }
            </style>

            <div class="anime-scraper-container">
                <h1>🎬 Multi-Player Anime Scraper</h1>

                <form id="episode-form">
                    <div class="form-section">
                        <h3>📺 Anime Information</h3>

                        <div class="form-group">
                            <label for="anime_title">Anime Title *</label>
                            <input type="text" id="anime_title" name="anime_title" placeholder="Enter anime title" required>
                        </div>

                        <div class="form-group">
                            <label for="zoro_endpoint">API Endpoint *</label>
                            <input type="text" id="zoro_endpoint" name="zoro_endpoint" placeholder="e.g., kaiju-no-8-season-2-19792" required>
                        </div>

                        <div class="form-group">
                            <label>Episode Type *</label>
                            <div class="type-selector">
                                <div class="type-btn active" data-type="sub">Sub</div>
                                <div class="type-btn" data-type="dub">Dub</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_episode">Start Episode *</label>
                                <input type="number" id="start_episode" name="start_episode" value="1" min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="end_episode">End Episode *</label>
                                <input type="number" id="end_episode" name="end_episode" value="1" min="1" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="anime_category">Category *</label>
                                <select id="anime_category" name="anime_category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>"
                                            data-name="<?php echo esc_attr($category->name); ?>">
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="anime_series">Series *</label>
                                <select id="anime_series" name="anime_series" required>
                                    <option value="">Select Series</option>
                                    <?php foreach ($anime_series as $series): ?>
                                        <option value="<?php echo esc_attr($series->ID); ?>"
                                            data-category="<?php echo esc_attr(implode(',', wp_get_post_categories($series->ID))); ?>">
                                            <?php echo esc_html($series->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="episode_suffix">Episode Suffix *</label>
                                <select id="episode_suffix" name="episode_suffix" required>
                                    <option value="English Sub">English Sub</option>
                                    <option value="English Subtitle">English Subtitle</option>
                                    <option value="English Subbed">English Subbed</option>
                                    <option value="random">Randomize Each</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="url_structure">URL Structure *</label>
                                <select id="url_structure" name="url_structure" required>
                                    <option value="default">AnimeName-Episode-{num}-{suffix}</option>
                                    <option value="random">AnimeName-Episode-{num}-{suffix}-{rand}</option>
                                    <option value="short">AnimeName-Ep-{num}-{rand}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>🎮 Player Selection</h3>
                        <p style="color: #666; margin-bottom: 15px;">Select which players to include in episodes:</p>

                        <div class="player-checkboxes">
                            <?php foreach ($players as $player): ?>
                                <div class="player-option selected" data-player="<?php echo esc_attr($player['key']); ?>">
                                    <input type="checkbox" checked>
                                    <div class="player-info">
                                        <div class="player-name"><?php echo esc_html($player['display_label']); ?></div>
                                        <div class="player-desc"><?php echo esc_html($player['display_desc']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" id="preview-btn" class="btn btn-success">
                            👁️ Preview Episodes
                        </button>
                        <button type="submit" id="create-btn" class="btn btn-primary">
                            🚀 Create Episodes
                        </button>
                    </div>
                </form>

                <div id="preview-section" class="preview-section">
                    <h3>👀 Episode Preview</h3>
                    <div id="preview-content"></div>
                </div>

                <div id="status-message" class="hidden"></div>
            </div>

            <div id="progress-modal" class="progress-modal">
                <div class="progress-content">
                    <h3>Creating Episodes...</h3>
                    <div class="progress-bar">
                        <div id="progress-fill" class="progress-fill"></div>
                    </div>
                    <p id="progress-text">Initializing...</p>
                </div>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    let selectedPlayers = <?php echo wp_json_encode(array_column($players, 'key')); ?>;
                    let episodeType = 'sub';

                    $('.player-option').click(function() {
                        const player = $(this).data('player');
                        const checkbox = $(this).find('input[type="checkbox"]');

                        checkbox.prop('checked', !checkbox.prop('checked'));
                        $(this).toggleClass('selected', checkbox.prop('checked'));

                        if (checkbox.prop('checked')) {
                            if (!selectedPlayers.includes(player)) {
                                selectedPlayers.push(player);
                            }
                        } else {
                            selectedPlayers = selectedPlayers.filter(p => p !== player);
                        }
                    });

                    $('.player-option input[type="checkbox"]').click(function(e) {
                        e.stopPropagation();
                    });

                    $('.type-btn').click(function() {
                        $('.type-btn').removeClass('active');
                        $(this).addClass('active');
                        episodeType = $(this).data('type');
                        updateSuffixOptions();
                    });

                    function updateSuffixOptions() {
                        const suffixSelect = $('#episode_suffix');
                        suffixSelect.empty();

                        const suffixes = episodeType === 'sub' ? ['English Sub', 'English Subtitle', 'English Subbed', 'random'] : ['English Dub', 'English Dubbed', 'random'];

                        suffixes.forEach(suffix => {
                            const option = $('<option>').val(suffix).text(suffix === 'random' ? 'Randomize Each' : suffix);
                            suffixSelect.append(option);
                        });
                    }

                    $('#anime_category').change(function() {
                        const categoryId = $(this).val();
                        if (categoryId) {
                            $('#anime_series option').each(function() {
                                const seriesCategories = $(this).data('category').toString().split(',');
                                if (seriesCategories.includes(categoryId)) {
                                    $('#anime_series').val($(this).val());
                                    return false;
                                }
                            });
                        } else {
                            $('#anime_series').val('');
                        }
                    });

                    $('#preview-btn').click(function() {
                        const formData = getFormData();
                        if (!validateForm(formData)) return;

                        $(this).prop('disabled', true).text('Loading...');

                        $.ajax({
                            url: multiPlayerScraper.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'preview_episodes',
                                nonce: multiPlayerScraper.nonce,
                                ...formData,
                                selected_players: selectedPlayers
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#preview-content').html(response.data.html);
                                    $('#preview-section').addClass('show');
                                    showMessage('Episodes previewed successfully!', 'success');
                                } else {
                                    showMessage('Error: ' + response.data.message, 'error');
                                }
                                $('#preview-btn').prop('disabled', false).text('👁️ Preview Episodes');
                            },
                            error: function() {
                                showMessage('Failed to preview episodes', 'error');
                                $('#preview-btn').prop('disabled', false).text('👁️ Preview Episodes');
                            }
                        });
                    });

                    $('#episode-form').submit(function(e) {
                        e.preventDefault();

                        const formData = getFormData();
                        if (!validateForm(formData)) return;

                        if (selectedPlayers.length === 0) {
                            showMessage('Please select at least one player', 'error');
                            return;
                        }

                        $('#progress-modal').addClass('show');
                        $('#create-btn').prop('disabled', true).text('Creating...');

                        let progress = 0;
                        const progressInterval = setInterval(function() {
                            progress += Math.random() * 15;
                            if (progress > 90) progress = 90;
                            $('#progress-fill').css('width', progress + '%');
                            $('#progress-text').text('Processing episodes... ' + Math.round(progress) + '%');
                        }, 500);

                        $.ajax({
                            url: multiPlayerScraper.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'create_episodes',
                                nonce: multiPlayerScraper.nonce,
                                ...formData,
                                selected_players: selectedPlayers
                            },
                            timeout: 300000,
                            success: function(response) {
                                clearInterval(progressInterval);
                                $('#progress-fill').css('width', '100%');
                                $('#progress-text').text('Complete!');

                                setTimeout(function() {
                                    $('#progress-modal').removeClass('show');

                                    if (response.success) {
                                        showMessage(response.data.message, 'success');
                                    } else {
                                        showMessage('Error: ' + response.data.message, 'error');
                                    }

                                    $('#create-btn').prop('disabled', false).text('🚀 Create Episodes');
                                }, 1000);
                            },
                            error: function(xhr, status, error) {
                                clearInterval(progressInterval);
                                $('#progress-modal').removeClass('show');

                                let errorMsg = 'An error occurred. Please try again.';
                                if (status === 'timeout') {
                                    errorMsg = 'Request timed out. The process might still be running.';
                                }

                                showMessage(errorMsg, 'error');
                                $('#create-btn').prop('disabled', false).text('🚀 Create Episodes');
                            }
                        });
                    });

                    function getFormData() {
                        return {
                            anime_title: $('#anime_title').val().trim(),
                            zoro_endpoint: $('#zoro_endpoint').val().trim(),
                            episode_type: $('.type-btn.active').data('type'),
                            start_episode: $('#start_episode').val(),
                            end_episode: $('#end_episode').val(),
                            anime_category: $('#anime_category').val(),
                            anime_series: $('#anime_series').val(),
                            episode_suffix: $('#episode_suffix').val(),
                            url_structure: $('#url_structure').val()
                        };
                    }

                    function validateForm(data) {
                        if (!data.anime_title || !data.zoro_endpoint) {
                            showMessage('Please fill in all required fields', 'error');
                            return false;
                        }

                        if (!data.anime_category || !data.anime_series) {
                            showMessage('Please select category and series', 'error');
                            return false;
                        }

                        if (parseInt(data.start_episode) > parseInt(data.end_episode)) {
                            showMessage('Start episode cannot be greater than end episode', 'error');
                            return false;
                        }

                        return true;
                    }

                    function showMessage(text, type) {
                        $('#status-message').removeClass('hidden status-success status-error')
                            .addClass('status-message status-' + type)
                            .html(text);
                    }

                    $('#anime_title').focus();
                    updateSuffixOptions();
                });
            </script>
        </div>
    <?php
    }

    public function settings_page()
    {
        $message = '';

        if (isset($_POST['add_player'])) {
            $hostname_template = sanitize_text_field($_POST['hostname_template']);
            $url_template = sanitize_text_field($_POST['url_template']);
            $display_label = wp_kses_post($_POST['hostname_template']);
            $display_desc = sanitize_text_field($_POST['display_desc']);
            $ad_free = isset($_POST['ad_free']) ? 1 : 0;

            if (empty($hostname_template) || empty($url_template) || empty($display_desc)) {
                $message = '<div class="notice notice-error"><p>All fields are required.</p></div>';
            } else {
                $key = sanitize_key($hostname_template);
                $all_keys = array_merge(array_column($this->default_players, 'key'), array_column(get_option('mps_custom_players', []), 'key'));
                if (in_array($key, $all_keys)) {
                    $message = '<div class="notice notice-error"><p>Player name already exists.</p></div>';
                } else {
                    $custom_players = get_option('mps_custom_players', []);
                    $custom_players[] = [
                        'key' => $key,
                        'url_template' => $url_template,
                        'hostname_template' => $hostname_template,
                        'display_label' => $display_label,
                        'display_desc' => $display_desc,
                        'ad_free' => $ad_free
                    ];
                    update_option('mps_custom_players', $custom_players);
                    $message = '<div class="notice notice-success"><p>Player added successfully.</p></div>';
                }
            }
        } elseif (isset($_POST['edit_player'])) {
            $hostname_template = sanitize_text_field($_POST['hostname_template']);
            $url_template = sanitize_text_field($_POST['url_template']);
            $display_label = wp_kses_post($_POST['hostname_template']);
            $display_desc = sanitize_text_field($_POST['display_desc']);
            $ad_free = isset($_POST['ad_free']) ? 1 : 0;
            $old_key = sanitize_key($_POST['old_key']);

            if (empty($hostname_template) || empty($url_template) || empty($display_desc)) {
                $message = '<div class="notice notice-error"><p>All fields are required.</p></div>';
            } else {
                $key = sanitize_key($hostname_template);
                $custom_players = get_option('mps_custom_players', []);
                $key_exists = false;
                foreach ($custom_players as $p) {
                    if ($p['key'] === $key && $key !== $old_key) {
                        $key_exists = true;
                        break;
                    }
                }
                if ($key_exists) {
                    $message = '<div class="notice notice-error"><p>Player name already exists.</p></div>';
                } else {
                    foreach ($custom_players as &$p) {
                        if ($p['key'] === $old_key) {
                            $p['key'] = $key;
                            $p['url_template'] = $url_template;
                            $p['hostname_template'] = $hostname_template;
                            $p['display_label'] = $display_label;
                            $p['display_desc'] = $display_desc;
                            $p['ad_free'] = $ad_free;
                            break;
                        }
                    }
                    update_option('mps_custom_players', $custom_players);
                    $message = '<div class="notice notice-success"><p>Player updated successfully.</p></div>';
                }
            }
        } elseif (isset($_POST['update_api'])) {
            $api_base_url = sanitize_url($_POST['api_base_url']);
            if (!empty($api_base_url)) {
                update_option('mps_api_base_url', $api_base_url);
                $message = '<div class="notice notice-success"><p>API Base URL updated successfully.</p></div>';
            } else {
                $message = '<div class="notice notice-error"><p>API Base URL cannot be empty.</p></div>';
            }
        }

        if (isset($_GET['delete'])) {
            $delete_key = sanitize_key($_GET['delete']);
            $custom_players = get_option('mps_custom_players', []);
            $custom_players = array_filter($custom_players, function ($p) use ($delete_key) {
                return $p['key'] !== $delete_key;
            });
            update_option('mps_custom_players', array_values($custom_players));
            $message = '<div class="notice notice-success"><p>Player deleted successfully.</p></div>';
        }

        $api_base_url = $this->get_api_base_url();
        $custom_players = get_option('mps_custom_players', []);
        $edit_key = isset($_GET['edit']) ? sanitize_key($_GET['edit']) : '';
        $edit_player = null;
        if ($edit_key) {
            foreach ($custom_players as $p) {
                if ($p['key'] === $edit_key) {
                    $edit_player = $p;
                    break;
                }
            }
        }

    ?>
        <div class="wrap">
            <?php echo $message; ?>
            <h1>Multi-Player Anime Scraper Settings</h1>

            <h2>API Base URL</h2>
            <p>You must host your own instance of the Anime API. Follow instructions at <a href="https://github.com/itzzzme/anime-api" target="_blank">https://github.com/itzzzme/anime-api</a> to set up your API server (e.g., on Vercel or Render).</p>
            <form method="post">
                <input type="url" name="api_base_url" value="<?php echo esc_attr($api_base_url); ?>" size="50" required placeholder="e.g., https://your-anime-api.com">
                <p class="description">Enter the base URL of your hosted Anime API.</p>
                <input type="submit" name="update_api" value="Update API URL" class="button button-primary">
            </form>

            <h2>Default Players</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Player Name</th>
                        <th>URL Template</th>
                        <th>Display Label</th>
                        <th>Display Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->default_players as $player): ?>
                        <tr>
                            <td><?php echo esc_html($player['hostname_template']); ?> (Ads)</td>
                            <td><?php echo esc_html($player['url_template']); ?></td>
                            <td><?php echo esc_html($player['display_label']); ?></td>
                            <td><?php echo esc_html($player['display_desc']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Custom Players</h2>
            <p>Add your own streaming players. Check "Ad-Free" to append "No Ads" to the player name. Sub/Dub variations are handled automatically.</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Player Name</th>
                        <th>URL Template</th>
                        <th>Display Label</th>
                        <th>Display Description</th>
                        <th>Ad-Free</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($custom_players)): ?>
                        <tr>
                            <td colspan="6">No custom players added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($custom_players as $player): ?>
                            <tr>
                                <td><?php echo esc_html($player['hostname_template']); ?><?php echo $player['ad_free'] ? ' No Ads' : ''; ?></td>
                                <td><?php echo esc_html($player['url_template']); ?></td>
                                <td><?php echo esc_html($player['display_label']); ?></td>
                                <td><?php echo esc_html($player['display_desc']); ?></td>
                                <td><?php echo $player['ad_free'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mps-settings&edit=' . urlencode($player['key']))); ?>">Edit</a> |
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=mps-settings&delete=' . urlencode($player['key']))); ?>" onclick="return confirm('Are you sure you want to delete this player?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php echo $edit_player ? 'Edit Custom Player' : 'Add Custom Player'; ?></h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="hostname_template">Player Name</label></th>
                        <td><input type="text" name="hostname_template" id="hostname_template" value="<?php echo esc_attr($edit_player['hostname_template'] ?? ''); ?>" size="50" required placeholder="e.g., StreamX">
                            <p class="description">Name of the player (e.g., StreamX). Sub/Dub will be added automatically (e.g., StreamX Sub, StreamX Dub).</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="url_template">URL Template</label></th>
                        <td><input type="text" name="url_template" id="url_template" value="<?php echo esc_attr($edit_player['url_template'] ?? ''); ?>" size="50" required placeholder="e.g., https://your-player.com/watch?id={id}">
                            <p class="description">Streaming URL with {id} placeholder (e.g., kaiju-no-8-season-2-19792?ep=141988). Do not include &type=sub/dub; it’s added automatically.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="display_desc">Description</label></th>
                        <td><input type="text" name="display_desc" id="display_desc" value="<?php echo esc_attr($edit_player['display_desc'] ?? ''); ?>" size="50" required placeholder="e.g., StreamX - High-Quality Streaming">
                            <p class="description">Description shown in admin.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ad_free">Ad-Free</label></th>
                        <td><input type="checkbox" name="ad_free" id="ad_free" value="1" <?php echo isset($edit_player['ad_free']) && $edit_player['ad_free'] ? 'checked' : ''; ?>>
                            <p class="description">Check to append "No Ads" to the player name (e.g., StreamX Sub No Ads).</p>
                        </td>
                    </tr>
                </table>
                <?php if ($edit_player): ?>
                    <input type="hidden" name="old_key" value="<?php echo esc_attr($edit_key); ?>">
                    <input type="submit" name="edit_player" value="Update Player" class="button button-primary">
                <?php else: ?>
                    <input type="submit" name="add_player" value="Add Player" class="button button-primary">
                <?php endif; ?>
            </form>
        </div>
<?php
    }

    private function get_players()
    {
        $custom_players = get_option('mps_custom_players', []);
        return array_merge($this->default_players, $custom_players);
    }

    private function get_api_base_url()
    {
        return get_option('mps_api_base_url', $this->api_base_url_default);
    }

    public function handle_ajax()
    {
        $action = $_POST['action'] ?? '';

        if ($action === 'preview_episodes') {
            $this->handle_preview();
        } elseif ($action === 'create_episodes') {
            $this->handle_create_episodes();
        }
    }

    private function handle_preview()
    {
        check_ajax_referer('episodes_nonce', 'nonce');

        $endpoint = sanitize_text_field($_POST['zoro_endpoint'] ?? '');
        $anime_title = sanitize_text_field($_POST['anime_title'] ?? '');
        $episode_type = sanitize_text_field($_POST['episode_type'] ?? 'sub');
        $start_episode = absint($_POST['start_episode'] ?? 1);
        $end_episode = absint($_POST['end_episode'] ?? 1);
        $selected_players = $_POST['selected_players'] ?? [];
        $episode_suffix = sanitize_text_field($_POST['episode_suffix'] ?? 'English Sub');
        $url_structure = sanitize_text_field($_POST['url_structure'] ?? 'default');

        if (empty($endpoint) || empty($anime_title)) {
            wp_send_json_error(['message' => 'Anime title and endpoint are required']);
        }

        $api_data = $this->fetch_api_data($endpoint);

        if (isset($api_data['error'])) {
            wp_send_json_error(['message' => $api_data['error']]);
        }

        $html = $this->generate_preview_html($api_data, $anime_title, $start_episode, $end_episode, $episode_type, $selected_players, $episode_suffix, $url_structure);
        wp_send_json_success(['html' => $html]);
    }

    private function fetch_api_data($endpoint)
    {
        $api_base = $this->get_api_base_url();
        $api_url = $api_base . '/api/episodes/' . $endpoint;

        $response = wp_remote_get($api_url, [
            'timeout' => 30,
            'headers' => ['Accept' => 'application/json']
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $response_data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($response_data['success'])) {
            return ['error' => 'Invalid API response or endpoint not found. Please ensure your API is hosted correctly. See <a href="https://github.com/itzzzme/anime-api" target="_blank">https://github.com/itzzzme/anime-api</a> for setup instructions.'];
        }

        return $response_data['results'] ?? [];
    }

    private function generate_preview_html($api_data, $anime_title, $start_episode, $end_episode, $episode_type, $selected_players, $episode_suffix, $url_structure)
    {
        $episodes = $api_data['episodes'] ?? [];
        $total_episodes = $api_data['totalEpisodes'] ?? 0;

        $html = '<div style="margin-bottom: 15px;">';
        $html .= '<p style="color: #666; margin-bottom: 5px;">Total episodes available: <strong>' . esc_html($total_episodes) . '</strong></p>';
        $html .= '<p style="color: #0073aa; margin-bottom: 15px;">Episode Type: <strong>' . esc_html(ucfirst($episode_type)) . '</strong></p>';
        $html .= '</div>';

        $found_episodes = 0;
        for ($i = $start_episode; $i <= $end_episode; $i++) {
            $episode = null;
            foreach ($episodes as $ep) {
                if (intval($ep['episode_no']) === $i) {
                    $episode = $ep;
                    break;
                }
            }
            if (!$episode) continue;

            $title = esc_html($episode['title'] ?? 'Unknown Title');
            $japanese_title = esc_html($episode['japanese_title'] ?? '');
            $episode_id = esc_html($episode['id'] ?? '');
            $is_generic = $this->is_generic_episode_title($title, $i);
            $suffix = $episode_suffix === 'random'
                ? ($episode_type === 'sub' ? $this->suffixes_sub[array_rand($this->suffixes_sub)] : $this->suffixes_dub[array_rand($this->suffixes_dub)])
                : $episode_suffix;

            $post_title = "{$anime_title} Episode $i $suffix";

            $slug = $this->generate_episode_slug($anime_title, $i, $suffix, $url_structure);

            $html .= '<div class="episode-preview">';
            $html .= '<div class="episode-title">' . esc_html($post_title) . '</div>';
            $html .= '<div class="episode-info"><strong>Episode Title:</strong> ' . ($is_generic ? "Episode $i" : "Episode $i: $title") . '</div>';

            if ($japanese_title && $japanese_title !== $title) {
                $html .= '<div class="episode-info">Japanese: ' . $japanese_title . '</div>';
            }

            $html .= '<div class="episode-info">Episode ID: ' . $episode_id . '</div>';
            $html .= '<div class="episode-info"><strong>Slug:</strong> <code>' . esc_html($slug) . '</code></div>';

            $html .= '<div class="episode-players">';
            foreach ($selected_players as $player) {
                $html .= '<span class="player-badge ' . ($player === 'megaplay' || $player === 'vidplay' ? esc_attr($player) : 'custom') . '">' . esc_html(ucfirst($player)) . '</span>';
            }
            $html .= '</div>';

            $html .= '</div>';
            $found_episodes++;
        }

        if ($found_episodes === 0) {
            $html .= '<p style="color: #721c24;">No episodes found in the specified range (' . esc_html($start_episode) . '-' . esc_html($end_episode) . ')</p>';
        } else {
            $html .= '<p style="color: #155724; margin-top: 15px;">Found <strong>' . esc_html($found_episodes) . '</strong> episodes to create with <strong>' . esc_html(count($selected_players)) . '</strong> player(s) each</p>';
        }

        return $html;
    }

    private function is_generic_episode_title($title, $episode_no)
    {
        $generic_patterns = [
            "Episode $episode_no",
            "Episode " . str_pad($episode_no, 2, '0', STR_PAD_LEFT),
            "Ep $episode_no",
            "Ep " . str_pad($episode_no, 2, '0', STR_PAD_LEFT),
            "$episode_no",
        ];

        foreach ($generic_patterns as $pattern) {
            if (strcasecmp(trim($title), $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    private function handle_create_episodes()
    {
        check_ajax_referer('episodes_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized access']);
        }

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $data = $this->sanitize_input($_POST);
        $validation = $this->validate_input($data);

        if (!$validation['valid']) {
            wp_send_json_error(['message' => $validation['message']]);
        }

        $result = $this->process_episodes($data);
        wp_send_json($result);
    }

    private function sanitize_input($post_data)
    {
        return [
            'anime_title' => sanitize_text_field($post_data['anime_title'] ?? ''),
            'zoro_endpoint' => sanitize_text_field($post_data['zoro_endpoint'] ?? ''),
            'episode_type' => sanitize_text_field($post_data['episode_type'] ?? 'sub'),
            'start_episode' => absint($post_data['start_episode'] ?? 1),
            'end_episode' => absint($post_data['end_episode'] ?? 1),
            'category_id' => absint($post_data['anime_category'] ?? 0),
            'anime_series_id' => absint($post_data['anime_series'] ?? 0),
            'selected_players' => array_map('sanitize_text_field', $post_data['selected_players'] ?? []),
            'episode_suffix' => sanitize_text_field($post_data['episode_suffix'] ?? 'English Sub'),
            'url_structure' => sanitize_text_field($post_data['url_structure'] ?? 'default')
        ];
    }

    private function validate_input($data)
    {
        if (empty($data['anime_title']) || empty($data['zoro_endpoint'])) {
            return ['valid' => false, 'message' => 'Anime title and endpoint are required'];
        }

        if (!in_array($data['episode_type'], ['sub', 'dub'])) {
            return ['valid' => false, 'message' => 'Invalid episode type'];
        }

        if ($data['start_episode'] < 1 || $data['end_episode'] < $data['start_episode']) {
            return ['valid' => false, 'message' => 'Invalid episode range'];
        }

        if (empty($data['category_id']) || empty($data['anime_series_id'])) {
            return ['valid' => false, 'message' => 'Please select category and series'];
        }

        if (empty($data['selected_players'])) {
            return ['valid' => false, 'message' => 'Please select at least one player'];
        }

        return ['valid' => true];
    }

    private function process_episodes($data)
    {
        $api_data = $this->fetch_api_data($data['zoro_endpoint']);

        if (isset($api_data['error'])) {
            return ['success' => false, 'data' => ['message' => 'API error: ' . $api_data['error']]];
        }

        $episodes = $api_data['episodes'] ?? [];
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        for ($i = $data['start_episode']; $i <= $data['end_episode']; $i++) {
            $episode = null;
            foreach ($episodes as $ep) {
                if (intval($ep['episode_no']) === $i) {
                    $episode = $ep;
                    break;
                }
            }
            if (!$episode) continue;

            $episode_info = $this->build_episode_info($data, $i, $episode);

            if ($existing_id = $this->episode_exists($episode_info, $data)) {
                $update_result = $this->update_episode_embeds($existing_id, $episode, $data);
                if ($update_result) {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            } else {
                $create_result = $this->create_episode($episode_info, $data, $episode);
                if ($create_result['success']) {
                    $stats['created']++;
                } else {
                    $stats['errors'][] = $create_result['error'];
                }
            }
        }

        return $this->format_response($stats);
    }

    private function build_episode_info($data, $episode_no, $episode)
    {
        $episode_title = $episode['title'] ?? 'Unknown Title';
        $is_generic = $this->is_generic_episode_title($episode_title, $episode_no);
        $suffix = $data['episode_suffix'] === 'random'
            ? ($data['episode_type'] === 'sub' ? $this->suffixes_sub[array_rand($this->suffixes_sub)] : $this->suffixes_dub[array_rand($this->suffixes_dub)])
            : $data['episode_suffix'];

        $post_title = "{$data['anime_title']} Episode $episode_no $suffix";

        $slug = $this->generate_episode_slug($data['anime_title'], $episode_no, $suffix, $data['url_structure']);

        return [
            'season_episode' => $episode_no,
            'title' => $episode_title,
            'japanese_title' => $episode['japanese_title'] ?? '',
            'episode_id' => $episode['id'] ?? '',
            'episode_title' => $is_generic ? "Episode $episode_no" : "Episode $episode_no: $episode_title",
            'post_title' => $post_title,
            'is_generic_title' => $is_generic,
            'episode_type' => $data['episode_type'],
            'slug' => $slug,
            'suffix' => $suffix
        ];
    }

    private function generate_episode_slug($anime_title, $episode_no, $suffix, $url_structure)
    {
        $base = sanitize_title($anime_title);
        $suffix_slug = sanitize_title($suffix);
        $rand = substr(md5(uniqid(rand(), true)), 0, 6);
        switch ($url_structure) {
            case 'random':
                return "{$base}-episode-{$episode_no}-{$suffix_slug}-{$rand}";
            case 'short':
                return "{$base}-ep-{$episode_no}-{$rand}";
            case 'default':
            default:
                return "{$base}-episode-{$episode_no}-{$suffix_slug}";
        }
    }

    private function episode_exists($episode_info, $data)
    {
        global $wpdb;

        $sql = "SELECT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                WHERE p.post_type = 'post' 
                AND p.post_status IN ('publish', 'draft', 'private')
                AND p.post_name = %s
                AND pm2.meta_key = 'ero_seri'
                AND pm2.meta_value = %s";

        $params = [$episode_info['slug'], $data['anime_series_id']];
        $existing_id = $wpdb->get_var($wpdb->prepare($sql, $params));

        if ($existing_id) return $existing_id;

        $title_sql = "SELECT p.ID FROM {$wpdb->posts} p
                      INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                      INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                      WHERE p.post_type = 'post' 
                      AND p.post_status IN ('publish', 'draft', 'private')
                      AND p.post_title = %s
                      AND tt.term_id = %s AND tt.taxonomy = 'category'
                      LIMIT 1";

        $manually_created = $wpdb->get_var($wpdb->prepare($title_sql, [$episode_info['post_title'], $data['category_id']]));

        if ($manually_created) {
            $this->add_episode_meta_to_existing($manually_created, $episode_info, $data);
            return $manually_created;
        }

        return false;
    }

    private function add_episode_meta_to_existing($post_id, $episode_info, $data)
    {
        $meta_data = [
            '_anime_title' => $data['anime_title'],
            '_episode_number' => $episode_info['season_episode'],
            '_episode_title' => $episode_info['title'],
            '_episode_id' => $episode_info['episode_id'],
            '_is_generic_title' => $episode_info['is_generic_title'] ? 'yes' : 'no',
            '_episode_type' => $episode_info['episode_type'],
            'ero_seri' => $data['anime_series_id']
        ];

        foreach ($meta_data as $key => $value) {
            if (!get_post_meta($post_id, $key, true)) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }

    private function create_episode($episode_info, $data, $episode)
    {
        $post_id = wp_insert_post([
            'post_title' => $episode_info['post_title'],
            'post_name' => $episode_info['slug'],
            'post_content' => '<div class="episode-content"></div>',
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        ], true);

        if (is_wp_error($post_id)) {
            return ['success' => false, 'error' => 'Failed to create episode: ' . $post_id->get_error_message()];
        }

        if ($data['category_id']) {
            wp_set_post_categories($post_id, [$data['category_id']]);
        }

        $sub_episode_value = ($data['episode_type'] === 'dub') ? 'Dub' : 'Sub';

        $meta_data = [
            '_anime_title' => $data['anime_title'],
            '_episode_number' => $episode_info['season_episode'],
            '_episode_title' => $episode_info['title'],
            '_episode_id' => $episode_info['episode_id'],
            '_is_generic_title' => $episode_info['is_generic_title'] ? 'yes' : 'no',
            '_episode_type' => $episode_info['episode_type'],
            'ero_subepisode' => $sub_episode_value,
            'ero_episodebaru' => $episode_info['season_episode'],
            'ero_episodetitle' => $episode_info['episode_title'],
            'ero_seri' => $data['anime_series_id'],
            'yoast_wpseo_focuskw' => "{$data['anime_title']} {$episode_info['episode_title']}"
        ];

        foreach ($meta_data as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        $this->add_multi_player_embeds($post_id, $episode_info['episode_id'], $data['episode_type'], $data['selected_players']);

        return ['success' => true];
    }

    private function add_multi_player_embeds($post_id, $episode_id, $episode_type, $selected_players)
    {
        $existing_embeds = get_post_meta($post_id, 'ab_embedgroup', true) ?: [];

        foreach ($selected_players as $player_key) {
            $embed_data = $this->generate_embed_data($player_key, $episode_id, $episode_type);
            if ($embed_data) {
                $existing_embeds[] = $embed_data;
            }
        }

        update_post_meta($post_id, 'ab_embedgroup', $existing_embeds);
    }

    private function generate_embed_data($player_key, $episode_id, $episode_type)
    {
        $players = $this->get_players();
        $player = null;
        foreach ($players as $p) {
            if ($p['key'] === $player_key) {
                $player = $p;
                break;
            }
        }
        if (!$player) {
            return null;
        }

        $type = $episode_type;
        $type_cap = ucfirst($type);
        $type_param = "type=$type";

        // Use full episode ID for custom players, extract number for default players
        $id = $episode_id;
        $num = $episode_id;
        if (preg_match('/ep=(\d+)/', $episode_id, $matches)) {
            $num = $matches[1];
        }

        // Use num for default players, full id for custom players
        $is_default_player = in_array($player_key, ['megaplay', 'vidplay']);
        $id_to_use = $is_default_player ? $num : $id;

        // Replace placeholders in URL template
        $embed_url = $player['url_template'];
        $embed_url = str_replace('{id}', $id_to_use, $embed_url);
        $embed_url = str_replace('{num}', $num, $embed_url);
        $embed_url = str_replace('{type}', $type, $embed_url);

        // Append type_param for custom players with proper query string handling
        if (!$is_default_player) {
            // Split URL into base and query parts
            $url_parts = parse_url($embed_url);
            $base_url = $url_parts['scheme'] . '://' . $url_parts['host'] . ($url_parts['path'] ?? '');
            $query_params = [];
            if (!empty($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
            }

            // Add type parameter
            $query_params['type'] = $type;

            // Rebuild query string
            $query_string = http_build_query($query_params);
            $embed_url = $base_url . ($query_string ? '?' . $query_string : '');
        }

        // Append (Ads) for default players, or No Ads for ad-free custom players
        $hostname = $player['hostname_template'] . ' ' . $type_cap;
        if ($is_default_player) {
            $hostname .= ' (Ads)';
        } elseif (!empty($player['ad_free'])) {
            $hostname .= ' No Ads';
        }

        // Create iframe with escaped URL
        $iframe = sprintf(
            '<iframe width="100%%" height="100%%" src="%s" frameborder="0" allowfullscreen></iframe>',
            $embed_url
        );

        return [
            'ab_hostname' => $hostname,
            'ab_embed' => $iframe,
            '_state' => 'expanded'
        ];
    }

    private function update_episode_embeds($post_id, $episode, $data)
    {
        $episode_id = $episode['id'] ?? '';
        if (empty($episode_id)) {
            return false;
        }

        $existing_embeds = get_post_meta($post_id, 'ab_embedgroup', true) ?: [];
        $added_new = false;

        foreach ($data['selected_players'] as $player_key) {
            $embed_data = $this->generate_embed_data($player_key, $episode_id, $data['episode_type']);
            if ($embed_data) {
                $embed_exists = false;
                foreach ($existing_embeds as $existing_embed) {
                    if (
                        !empty($existing_embed['ab_hostname']) &&
                        $existing_embed['ab_hostname'] === $embed_data['ab_hostname']
                    ) {
                        $embed_exists = true;
                        break;
                    }
                }

                if (!$embed_exists) {
                    $existing_embeds[] = $embed_data;
                    $added_new = true;
                }
            }
        }

        if ($added_new) {
            update_post_meta($post_id, 'ab_embedgroup', $existing_embeds);
        }

        return $added_new;
    }

    private function format_response($stats)
    {
        $messages = [];

        if ($stats['created']) {
            $messages[] = sprintf('✅ Created <strong>%d</strong> episodes as drafts', $stats['created']);
        }

        if ($stats['updated']) {
            $messages[] = sprintf('🔄 Updated <strong>%d</strong> episodes with new embeds', $stats['updated']);
        }

        if ($stats['skipped']) {
            $messages[] = sprintf('⏭️ Skipped <strong>%d</strong> existing episodes', $stats['skipped']);
        }

        if (!empty($stats['errors'])) {
            $messages[] = '❌ <strong>Errors:</strong> ' . implode(', ', $stats['errors']);
        }

        return $messages ?
            ['success' => true, 'data' => ['message' => implode('<br>', $messages)]] :
            ['success' => false, 'data' => ['message' => 'No episodes processed']];
    }

    public function order_posts_by_episode($query)
    {
        if (!is_admin() || !$query->is_main_query() || $GLOBALS['pagenow'] !== 'edit.php') return;

        if (($_GET['orderby'] ?? '') === 'episode_number') {
            $query->set('meta_key', '_episode_number');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
        }
    }

    public function add_columns($columns)
    {
        $new_columns = array_slice($columns, 0, 2);
        $new_columns['episode_number'] = 'Episode';
        $new_columns['episode_title'] = 'Episode Title';
        $new_columns['episode_type'] = 'Type';
        $new_columns['players'] = 'Players';
        return array_merge($new_columns, array_slice($columns, 2));
    }

    public function show_columns($column, $post_id)
    {
        switch ($column) {
            case 'episode_number':
                echo esc_html(get_post_meta($post_id, '_episode_number', true) ?: '-');
                break;
            case 'episode_title':
                echo esc_html(get_post_meta($post_id, '_episode_title', true) ?: '-');
                break;
            case 'episode_type':
                $episode_type = get_post_meta($post_id, '_episode_type', true);
                if ($episode_type === 'dub') {
                    echo '<span style="color: #2196f3; font-weight: bold;">DUB</span>';
                } elseif ($episode_type === 'sub') {
                    echo '<span style="color: #4caf50; font-weight: bold;">SUB</span>';
                } else {
                    echo '-';
                }
                break;
            case 'players':
                $embeds = get_post_meta($post_id, 'ab_embedgroup', true) ?: [];
                $players = [];
                foreach ($embeds as $embed) {
                    if (!empty($embed['ab_hostname'])) {
                        $hostname = $embed['ab_hostname'];
                        $style = '';
                        if (strpos($hostname, 'Mega') !== false) {
                            $style = 'color: #4caf50;';
                            $label = 'Mega';
                        } elseif (strpos($hostname, 'Vidplay') !== false) {
                            $style = 'color: #ff9800;';
                            $label = 'Vidplay';
                        } else {
                            $style = 'color: #2196f3;';
                            $label = esc_html($hostname);
                        }
                        $players[] = '<span style="font-weight: bold; ' . $style . '">' . $label . '</span>';
                    }
                }
                echo implode(' ', array_unique($players)) ?: '-';
                break;
        }
    }

    public function sortable_columns($columns)
    {
        $columns['episode_number'] = 'episode_number';
        return $columns;
    }
}

new MultiPlayerAnimeScraper();
?>