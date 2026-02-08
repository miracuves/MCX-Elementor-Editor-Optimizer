<?php
/**
 * Installation Test for Elementor Editor Optimizer
 * 
 * This file checks if your server meets the requirements for the plugin.
 * Access this file in your browser after uploading the plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    echo "Please access this file through WordPress.";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Elementor Editor Optimizer - Installation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-item { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        h1, h2 { color: #23282d; }
        .status { font-weight: bold; }
        .details { margin-top: 10px; font-size: 14px; }
        .next-steps { background: #e7f3ff; padding: 20px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Elementor Editor Optimizer - Installation Test</h1>
        
        <?php
        $tests = [];
        
        // Test PHP Version
        $php_version = PHP_VERSION;
        $tests['php_version'] = [
            'name' => 'PHP Version',
            'required' => '7.4 or higher',
            'current' => $php_version,
            'status' => version_compare($php_version, '7.4', '>=') ? 'pass' : 'fail'
        ];
        
        // Test WordPress Version
        global $wp_version;
        $tests['wp_version'] = [
            'name' => 'WordPress Version',
            'required' => '5.0 or higher',
            'current' => $wp_version,
            'status' => version_compare($wp_version, '5.0', '>=') ? 'pass' : 'fail'
        ];
        
        // Test Elementor Installation
        $elementor_installed = defined('ELEMENTOR_VERSION');
        $elementor_version = $elementor_installed ? ELEMENTOR_VERSION : 'Not installed';
        $tests['elementor'] = [
            'name' => 'Elementor',
            'required' => '3.0.0 or higher',
            'current' => $elementor_version,
            'status' => $elementor_installed && version_compare(ELEMENTOR_VERSION, '3.0.0', '>=') ? 'pass' : ($elementor_installed ? 'fail' : 'warning')
        ];
        
        // Test Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = wp_convert_hr_to_bytes($memory_limit);
        $required_bytes = wp_convert_hr_to_bytes('128M');
        $tests['memory'] = [
            'name' => 'PHP Memory Limit',
            'required' => '128M or higher (256M recommended)',
            'current' => $memory_limit,
            'status' => $memory_bytes >= $required_bytes ? 'pass' : 'warning'
        ];
        
        // Test File Permissions
        $plugin_dir = dirname(__FILE__);
        $writable = is_writable($plugin_dir);
        $tests['permissions'] = [
            'name' => 'File Permissions',
            'required' => 'Writable plugin directory',
            'current' => $writable ? 'Writable' : 'Not writable',
            'status' => $writable ? 'pass' : 'fail'
        ];
        
        // Test WordPress Functions
        $tests['wp_functions'] = [
            'name' => 'WordPress Functions',
            'required' => 'All required functions available',
            'current' => 'Checking...',
            'status' => function_exists('add_action') && function_exists('get_option') ? 'pass' : 'fail'
        ];
        
        // Display results
        $all_pass = true;
        foreach ($tests as $test) {
            if ($test['status'] === 'fail') {
                $all_pass = false;
            }
            ?>
            <div class="test-item <?php echo $test['status']; ?>">
                <h3><?php echo $test['name']; ?> - <span class="status"><?php echo ucfirst($test['status']); ?></span></h3>
                <p><strong>Required:</strong> <?php echo $test['required']; ?><br>
                <strong>Current:</strong> <?php echo $test['current']; ?></p>
                
                <?php if ($test['status'] === 'fail' || $test['status'] === 'warning'): ?>
                <div class="details">
                    <?php
                    switch ($test['name']) {
                        case 'PHP Version':
                            echo 'Please upgrade your PHP version. Contact your hosting provider for assistance.';
                            break;
                        case 'WordPress Version':
                            echo 'Please update WordPress to the latest version.';
                            break;
                        case 'Elementor':
                            if (!$elementor_installed) {
                                echo 'Please install and activate Elementor plugin.';
                            } else {
                                echo 'Please update Elementor to the latest version.';
                            }
                            break;
                        case 'PHP Memory Limit':
                            echo 'Consider increasing your memory limit. Add this to wp-config.php: <code>define(\'WP_MEMORY_LIMIT\', \'256M\');</code>';
                            break;
                        case 'File Permissions':
                            echo 'Make sure the plugin directory is writable by the web server.';
                            break;
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>
        
        <?php if ($all_pass): ?>
        <div class="next-steps">
            <h2>✅ Installation Test Passed!</h2>
            <p>Your server meets all requirements for Elementor Editor Optimizer.</p>
            
            <h3>Next Steps:</h3>
            <ol>
                <li>Activate the plugin in WordPress admin</li>
                <li>Go to <strong>Elementor → Editor Optimizer</strong></li>
                <li>Click "Scan for Unused Widgets" to analyze your site</li>
                <li>Enable optimizations that suit your needs</li>
                <li>Save changes and enjoy faster Elementor performance!</li>
            </ol>
            
            <p><strong>Important:</strong> Always test optimizations on a staging site first. Enable Safe Mode to prevent issues.</p>
        </div>
        <?php else: ?>
        <div class="test-item fail">
            <h2>❌ Installation Test Failed</h2>
            <p>Please fix the issues above before using the plugin.</p>
        </div>
        <?php endif; ?>
        
        <div class="test-item">
            <h3>Plugin Information</h3>
            <p><strong>Version:</strong> 1.0.1<br>
            <strong>Author:</strong> Miracuves<br>
            <strong>Requirements:</strong> PHP 7.4+, WordPress 5.0+, Elementor 3.0.0+</p>
        </div>
    </div>
</body>
</html>