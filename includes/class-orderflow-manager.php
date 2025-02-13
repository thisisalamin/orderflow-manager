<?php
/*
Plugin Name: My Custom Plugin
Description: A custom plugin for demonstration purposes.
Version: 1.0
Author: Your Name
*/

class MyCustomPlugin {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        // Enqueue existing scripts
        wp_enqueue_script('jquery');
        // Remove html2pdf library - no longer needed
    }
}

new MyCustomPlugin();
?>
