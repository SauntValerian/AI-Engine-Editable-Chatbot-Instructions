<?php
/**
 * Plugin Name: AI Engine Chatbot Instructions Display
 * Plugin URI: https://yourwebsite.com/plugins/ai-engine-chatbot-instructions
 * Description: Provides a shortcode to display and edit AI Engine chatbot instructions on the frontend.
 * Version: 1.0.0
 * Author: SauntValerian
 * Author URI: https://prometheusfire.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-engine-chatbot-instructions
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Shortcode to Display AI Engine Chatbot Instructions
 * 
 * Usage: [chatbot_instructions name="chatbot_name" editable="1"]
 * 
 * This shortcode retrieves chatbot instructions from the mwai_chatbots
 * stored in the WordPress options table and displays them on the frontend.
 */

function display_chatbot_instructions_shortcode($atts) {
    // Define default attributes
    $attributes = shortcode_atts(array(
        'name' => '', // The botId of the chatbot
        'container_class' => 'chatbot-instructions-container',
        'title_class' => 'chatbot-instructions-title',
        'content_class' => 'chatbot-instructions-content',
        'show_title' => 'true',
        'title_text' => 'Chatbot Instructions',
        'escape_html' => 'true',
        'editable' => '0', // Enable editing (0 = no, 1 = yes)
        'min_role' => 'administrator' // Minimum user role for editing
    ), $atts);

    // Check if chatbot name is provided and validate format
    if (empty($attributes['name']) || !preg_match('/^[a-zA-Z0-9_-]+$/', $attributes['name'])) {
        return '<div class="chatbot-error">Error: Please specify a valid chatbot name using the "name" attribute.</div>';
    }

    // Retrieve the mwai_chatbots from the database
    $mwai_chatbots = get_option('mwai_chatbots', array());
    
    // Check if the chatbots data exists
    if (empty($mwai_chatbots)) {
        return '<div class="chatbot-error">Error: No AI Engine chatbots found in the database.</div>';
    }

    // Look for the specific chatbot by botId
    $chatbot_instructions = '';
    $chatbot_name = '';
    $chatbot_found = false;
    $target_bot_id = $attributes['name'];

    // Search through all chatbots to find the one with matching botId
    foreach ($mwai_chatbots as $chatbot_id => $chatbot_data) {
        if (isset($chatbot_data['botId']) && $chatbot_data['botId'] === $target_bot_id) {
            if (isset($chatbot_data['instructions'])) {
                $chatbot_instructions = $chatbot_data['instructions'];
                $chatbot_name = isset($chatbot_data['name']) ? $chatbot_data['name'] : '';
                $chatbot_found = true;
                break;
            }
        }
    }

    // Return error if chatbot not found
    if (!$chatbot_found) {
        return '<div class="chatbot-error">Error: Chatbot with botId "' . esc_html($attributes['name']) . '" not found in AI Engine chatbots.</div>';
    }

    // Return error if no instructions found
    if (empty($chatbot_instructions)) {
        return '<div class="chatbot-error">Error: No instructions found for chatbot with botId "' . esc_html($attributes['name']) . '".</div>';
    }

    // Check if editing is enabled and user has permissions
    $is_editable = ($attributes['editable'] === '1' && user_can_edit_instructions($attributes['min_role']));
    
    // Build the title with chatbot name
    $full_title = $attributes['title_text'];
    if (!empty($chatbot_name)) {
        $full_title = $chatbot_name . ' ' . $attributes['title_text'];
    }
    
    // Add unique ID for this instance
    $unique_id = 'chatbot-instructions-' . sanitize_title($attributes['name']) . '-' . wp_rand(1000, 9999);

    // Prepare the output
    $output = '<div class="' . esc_attr($attributes['container_class']) . '">';
    
    // Add the instructions content
    if ($is_editable) {
        // Add the modify link next to title
        if ($attributes['show_title'] === 'true') {
            $output .= '<div class="title-with-modify">';
            $output .= '<h3 class="' . esc_attr($attributes['title_class']) . '">' . esc_html($full_title) . '</h3>';
            $output .= '<a href="#" class="modify-instructions-link" data-target="' . esc_attr($unique_id) . '">Modify these instructions</a>';
            $output .= '</div>';
        } else {
            // If no title, still show the modify link
            $output .= '<div class="modify-link-container">';
            $output .= '<a href="#" class="modify-instructions-link" data-target="' . esc_attr($unique_id) . '">Modify these instructions</a>';
            $output .= '</div>';
        }
        
        // Editable version (starts as non-editable)
        $output .= '<div id="' . esc_attr($unique_id) . '" class="' . esc_attr($attributes['content_class']) . ' editable-instructions" data-bot-id="' . esc_attr($attributes['name']) . '" data-min-role="' . esc_attr($attributes['min_role']) . '" data-original="' . esc_attr($chatbot_instructions) . '">';
        $output .= wp_kses_post($chatbot_instructions);
        $output .= '</div>';
        
        // Add save/cancel buttons (initially hidden)
        $output .= '<div class="instructions-controls" id="controls-' . esc_attr($unique_id) . '" style="display: none;">';
        $output .= '<button type="button" class="save-instructions-btn" data-target="' . esc_attr($unique_id) . '">Save Changes</button>';
        $output .= '<button type="button" class="cancel-instructions-btn" data-target="' . esc_attr($unique_id) . '">Cancel</button>';
        $output .= '<div class="instructions-status" id="status-' . esc_attr($unique_id) . '"></div>';
        $output .= '</div>';
    } else {
        // Read-only version (original working code)
        if ($attributes['show_title'] === 'true') {
            $output .= '<h3 class="' . esc_attr($attributes['title_class']) . '">' . esc_html($full_title) . '</h3>';
        }
        
        $output .= '<div class="' . esc_attr($attributes['content_class']) . '">';
        if ($attributes['escape_html'] === 'true') {
            $output .= '<pre>' . esc_html($chatbot_instructions) . '</pre>';
        } else {
            $output .= wp_kses_post($chatbot_instructions);
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';

    return $output;
}

/**
 * Check if current user can edit instructions based on minimum role requirement
 */
function user_can_edit_instructions($min_role) {
    // If user is not logged in, they can't edit
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Define role hierarchy (from lowest to highest)
    $role_hierarchy = array(
        'subscriber' => 1,
        'contributor' => 2,
        'author' => 3,
        'editor' => 4,
        'administrator' => 5
    );
    
    // Validate min_role parameter
    if (!isset($role_hierarchy[$min_role])) {
        return false; // Invalid role defaults to no access
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    
    // Additional security: ensure user object is valid
    if (!$current_user || !$current_user->exists()) {
        return false;
    }
    
    // Get user's highest role level
    $user_role_level = 0;
    foreach ($current_user->roles as $role) {
        if (isset($role_hierarchy[$role])) {
            $user_role_level = max($user_role_level, $role_hierarchy[$role]);
        }
    }
    
    // Get minimum required role level
    $min_role_level = $role_hierarchy[$min_role];
    
    // Check if user meets minimum role requirement
    return $user_role_level >= $min_role_level;
}

// Register the shortcode
add_shortcode('chatbot_instructions', 'display_chatbot_instructions_shortcode');

/**
 * AJAX handler to save chatbot instructions
 */
function save_chatbot_instructions_ajax() {
    // Check nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'save_chatbot_instructions')) {
        wp_die('Security check failed');
    }
    
    // Check user permissions using the min_role from the shortcode
    $min_role = isset($_POST['min_role']) ? sanitize_text_field($_POST['min_role']) : 'administrator';
    if (!user_can_edit_instructions($min_role)) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    $bot_id = sanitize_text_field($_POST['bot_id']);
    $new_instructions = sanitize_textarea_field($_POST['instructions']);
    
    if (empty($bot_id)) {
        wp_send_json_error('Bot ID is required');
        return;
    }
    
    // Get current chatbots data
    $mwai_chatbots = get_option('mwai_chatbots', array());
    
    if (empty($mwai_chatbots)) {
        wp_send_json_error('No chatbots found in database');
        return;
    }
    
    // Find and update the specific chatbot
    $chatbot_found = false;
    foreach ($mwai_chatbots as $chatbot_id => &$chatbot_data) {
        if (isset($chatbot_data['botId']) && $chatbot_data['botId'] === $bot_id) {
            $chatbot_data['instructions'] = $new_instructions;
            $chatbot_found = true;
            break;
        }
    }
    
    if (!$chatbot_found) {
        wp_send_json_error('Chatbot not found');
        return;
    }
    
    // Save back to database
    $saved = update_option('mwai_chatbots', $mwai_chatbots);
    
    if ($saved) {
        wp_send_json_success('Instructions saved successfully');
    } else {
        wp_send_json_error('Failed to save instructions to database');
    }
}

// Register AJAX handlers
add_action('wp_ajax_save_chatbot_instructions', 'save_chatbot_instructions_ajax');

/**
 * Enqueue JavaScript for editable functionality
 */
function enqueue_chatbot_instructions_scripts() {
    if (is_admin()) return;
    
    wp_enqueue_script('jquery');
    
    // Add inline JavaScript
    wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            // Modify instructions link handler
            $('.modify-instructions-link').on('click', function(e) {
                e.preventDefault();
                var link = $(this);
                var targetId = link.data('target');
                var contentDiv = $('#' + targetId);
                var controlsDiv = $('#controls-' + targetId);
                
                // Hide the modify link
                link.hide();
                
                // Make content editable and focus
                contentDiv.attr('contenteditable', 'true').focus();
                
                // Show the controls
                controlsDiv.show();
                
                // Place cursor at end of content
                var range = document.createRange();
                var sel = window.getSelection();
                range.selectNodeContents(contentDiv[0]);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            });
            
            // Save button handler
            $('.save-instructions-btn').on('click', function() {
                var button = $(this);
                var targetId = button.data('target');
                var contentDiv = $('#' + targetId);
                var controlsDiv = $('#controls-' + targetId);
                var modifyLink = $('.modify-instructions-link[data-target=\"' + targetId + '\"]');
                var botId = contentDiv.data('bot-id');
                var minRole = contentDiv.data('min-role');
                var instructions = contentDiv.text();
                var statusDiv = $('#status-' + targetId);
                
                button.prop('disabled', true).text('Saving...');
                statusDiv.html('Saving...');
                
                $.ajax({
                    url: '" . admin_url('admin-ajax.php') . "',
                    type: 'POST',
                    data: {
                        action: 'save_chatbot_instructions',
                        bot_id: botId,
                        min_role: minRole,
                        instructions: instructions,
                        nonce: '" . wp_create_nonce('save_chatbot_instructions') . "'
                    },
                    success: function(response) {
                        if (response.success) {
                            statusDiv.html('✓ Saved successfully!').css('color', 'green');
                            // Update original content for future cancels
                            contentDiv.data('original', instructions);
                            
                            // After 2 seconds, exit edit mode
                            setTimeout(function() {
                                exitEditMode(targetId);
                            }, 2000);
                        } else {
                            statusDiv.html('✗ Error: ' + response.data).css('color', 'red');
                        }
                    },
                    error: function() {
                        statusDiv.html('✗ Network error').css('color', 'red');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Save Changes');
                    }
                });
            });
            
            // Cancel button handler
            $('.cancel-instructions-btn').on('click', function() {
                var button = $(this);
                var targetId = button.data('target');
                var contentDiv = $('#' + targetId);
                var originalContent = contentDiv.data('original');
                var statusDiv = $('#status-' + targetId);
                
                // Restore original content
                contentDiv.text(originalContent);
                statusDiv.html('Changes cancelled').css('color', 'orange');
                
                // After 1 second, exit edit mode
                setTimeout(function() {
                    exitEditMode(targetId);
                }, 1000);
            });
            
            // Function to exit edit mode
            function exitEditMode(targetId) {
                var contentDiv = $('#' + targetId);
                var controlsDiv = $('#controls-' + targetId);
                var modifyLink = $('.modify-instructions-link[data-target=\"' + targetId + '\"]');
                var statusDiv = $('#status-' + targetId);
                
                // Make content non-editable
                contentDiv.attr('contenteditable', 'false').blur();
                
                // Hide controls
                controlsDiv.hide();
                
                // Clear status
                statusDiv.html('');
                
                // Show modify link again
                modifyLink.show();
            }
        });
    ");
}

add_action('wp_enqueue_scripts', 'enqueue_chatbot_instructions_scripts');

/**
 * Add basic CSS styles for the shortcode output
 */
function chatbot_instructions_styles() {
    ?>
    <style>
    .chatbot-instructions-container {
        margin: 20px 0;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .chatbot-instructions-title {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
        font-size: 1.2em;
    }
    
    .chatbot-instructions-content {
        line-height: 1.6;
    }
    
    .chatbot-instructions-content pre {
        white-space: pre-wrap;
        word-wrap: break-word;
        background-color: #fff;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 3px;
        font-family: monospace;
        font-size: 14px;
        overflow-x: auto;
    }
    
    .chatbot-error {
        padding: 10px;
        background-color: #ffebee;
        border: 1px solid #f44336;
        border-radius: 3px;
        color: #c62828;
        margin: 10px 0;
    }
    
    /* Editable styles */
    .editable-instructions {
        background-color: #fff;
        border: 2px solid #ddd;
        padding: 15px;
        min-height: 100px;
        white-space: pre-wrap;
        font-family: monospace;
        font-size: 14px;
        cursor: text;
    }
    
    .editable-instructions[contenteditable="false"] {
        border-style: dashed;
        cursor: default;
        background-color: #f9f9f9;
    }
    
    .editable-instructions[contenteditable="true"] {
        border-color: #0073aa;
        border-style: solid;
        background-color: #fff;
    }
    
    .editable-instructions:focus {
        outline: none;
        border-color: #0073aa;
        border-style: solid;
    }
    
    .title-with-modify {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .title-with-modify h3 {
        margin: 0;
    }
    
    .modify-link-container {
        text-align: right;
        margin-bottom: 15px;
    }
    
    .modify-instructions-link {
        color: #0073aa;
        text-decoration: none;
        font-size: 14px;
        padding: 5px 10px;
        border: 1px solid #0073aa;
        border-radius: 3px;
        background-color: #f7f7f7;
        transition: all 0.3s ease;
    }
    
    .modify-instructions-link:hover {
        background-color: #0073aa;
        color: white;
        text-decoration: none;
    }
    
    .instructions-controls {
        margin-top: 15px;
    }
    
    .save-instructions-btn, .cancel-instructions-btn {
        background-color: #0073aa;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 3px;
        cursor: pointer;
        margin-right: 10px;
    }
    
    .cancel-instructions-btn {
        background-color: #666;
    }
    
    .save-instructions-btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }
    
    .instructions-status {
        margin-top: 10px;
        font-weight: bold;
    }
    </style>
    <?php
}

// Add styles to the frontend
add_action('wp_head', 'chatbot_instructions_styles');

/**
 * Debug function to inspect the mwai_chatbots structure
 * Usage: [debug_mwai_chatbots]
 * Remove this in production!
 */
function debug_mwai_chatbots_shortcode($atts) {
    // Only show for administrators
    if (!current_user_can('manage_options')) {
        return ''; // No message, just return empty
    }
    
    $mwai_chatbots = get_option('mwai_chatbots', array());
    
    $output = '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px;">';
    $output .= '<h4>MWAI Chatbots Debug (Remove in Production)</h4>';
    
    if (empty($mwai_chatbots)) {
        $output .= '<p>No chatbots found in database.</p>';
    } else {
        $output .= '<p><strong>Available Chatbot botIds:</strong></p>';
        $output .= '<ul>';
        foreach ($mwai_chatbots as $id => $chatbot) {
            $bot_id = isset($chatbot['botId']) ? $chatbot['botId'] : 'No botId';
            $name = isset($chatbot['name']) ? $chatbot['name'] : 'No name';
            $has_instructions = isset($chatbot['instructions']) ? 'Yes' : 'No';
            $output .= '<li><strong>botId:</strong> ' . esc_html($bot_id) . ' | <strong>Name:</strong> ' . esc_html($name) . ' | <strong>Has Instructions:</strong> ' . $has_instructions . '</li>';
        }
        $output .= '</ul>';
        
        $output .= '<details style="margin-top: 20px;">';
        $output .= '<summary>Full Data Structure (click to expand)</summary>';
        $output .= '<pre style="background: white; padding: 15px; overflow-x: auto; font-size: 12px; max-height: 400px; overflow-y: auto;">';
        $output .= esc_html(print_r($mwai_chatbots, true));
        $output .= '</pre>';
        $output .= '</details>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// Register debug shortcode (remove in production)
add_shortcode('debug_mwai_chatbots', 'debug_mwai_chatbots_shortcode');
?>
