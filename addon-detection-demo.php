<?php
/**
 * Test file to demonstrate the enhanced Elementor addon detection
 * This shows how the plugin now detects widgets from multiple popular addons
 */

// Simulate detected widgets from different addons
$detected_widgets = [
    'Elementor Core' => [
        'heading' => 'Heading',
        'image' => 'Image', 
        'text-editor' => 'Text Editor',
        'button' => 'Button',
        'video' => 'Video'
    ],
    'Elementor Pro' => [
        'pro-posts' => 'Posts',
        'pro-portfolio' => 'Portfolio',
        'pro-form' => 'Form',
        'pro-login' => 'Login',
        'pro-slides' => 'Slides'
    ],
    'The Plus Addons' => [
        'plus-accordion' => 'TP Accordion',
        'plus-gallery' => 'TP Gallery', 
        'plus-pricing-table' => 'TP Pricing Table',
        'plus-testimonials' => 'TP Testimonials'
    ],
    'Happy Addons' => [
        'happy-card' => 'Happy Card',
        'happy-slider' => 'Happy Slider',
        'happy-team-member' => 'Happy Team Member'
    ],
    'Essential Addons' => [
        'eael-post-grid' => 'EA Post Grid',
        'eael-contact-form' => 'EA Contact Form',
        'eael-pricing-table' => 'EA Pricing Table'
    ],
    'JetElements' => [
        'jet-banner' => 'Jet Banner',
        'jet-blog' => 'Jet Blog',
        'jet-portfolio' => 'Jet Portfolio'
    ],
    'PowerPack' => [
        'pp-image-hotspots' => 'PP Image Hotspots',
        'pp-info-box' => 'PP Info Box',
        'pp-team-member' => 'PP Team Member'
    ]
];

echo "Enhanced Elementor Editor Optimizer - Addon Detection Results:\n\n";

foreach ($detected_widgets as $addon => $widgets) {
    echo "📦 {$addon}: " . count($widgets) . " widgets detected\n";
    foreach ($widgets as $id => $name) {
        echo "   ✓ {$name} ({$id})\n";
    }
    echo "\n";
}

echo "🚀 Performance Impact:\n";
echo "Total widgets detected: " . array_sum(array_map('count', $detected_widgets)) . "\n";
echo "Backend optimization potential: High\n";
echo "Unused widget detection: Enabled\n";
echo "Smart addon source detection: Enabled\n\n";

echo "💡 Benefits:\n";
echo "- Identifies widgets from 6+ popular Elementor addons\n";
echo "- Groups widgets by addon source for easy management\n";
echo "- Shows which addons contribute most unused widgets\n";
echo "- Provides targeted optimization recommendations\n";
echo "- Maintains compatibility with all major Elementor extensions\n";
?>