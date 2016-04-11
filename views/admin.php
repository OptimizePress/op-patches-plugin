<div class="wrap">

    <?php foreach ($types as $type) : ?>


        <h2>WordPress 4.5 Changes</h2>
        <p>
            Due to changes in the WordPress core framework, the OptimizePress LiveEditor is no longer compatible with WordPress versions 4.5 or newer.  This issue has been patched in the latest versions of OptimizePress and for versions up to 1 year old from April 2015 (the oldest patched version being 2.4).
        </p>
        <p>
            We highly recommend maintaining an up-to-date version of OptimizePress to ensure you benefit from the latest features, bug fixes and platform improvements.  If you do not have an active support and updates license, (OptimizePress includes 1 year of support and updates with your purchase), you will need to renew this.
            You can do this by accessing your account at <a href="http://members.optimizepress.com" target="_blank">OptimizePress Members Area</a> and click the blue bar at the top of the members home page to see your renewal options after you login.
        </p>
        <p>
            Please note, the visitor-facing pages of your website will still work as normal and be unaffected by this change, however the main symptom of this incompatibility is the functionality of the LiveEditor.
        </p>

    <?php if (!isset($patches[$type]) || empty($patches[$type])) : ?>
            <p><?php _e('There are no patches available for your version.', 'optimizepress_patch'); ?></p>
    <?php else : ?>
            <h3 class="title"><?php printf(__('Available patches for <em>%s</em>', 'optimizepress_patch'), 'OptimizePress ' . $type); ?></h3>
    <?php if (version_compare(OP_VERSION, '2.4.0', '>=') === true) : ?>
        <p><?php _e('Please install the patch from the list below: ', 'optimizepress_patch'); ?></p>
    <?php endif; ?>
    <?php if (version_compare(OP_VERSION, '2.4.0', '<') === true) : ?>
        <p>
            <strong><em>Unfortunately, you are using OptimizePress older than 2.4.0 and need to upgrade at <a href="http://members.optimizepress.com" target="_blank">OptimizePress Members Area</a>.</em></strong>
        </p>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed posts" cellspacing="0">
        <thead>
            <tr>
                <th><?php _e('Name', 'optimizepress_patch'); ?></th>
                <th><?php _e('Description', 'optimizepress_patch'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $a = 0; foreach ($patches[$type] as $patch) : $a += 1; ?>
            <tr<?php if ($a % 2) echo ' class="alternate"'; ?>>
                <td>
                    <p><strong><?php echo $patch->name; ?></strong></p>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'optimizepress_patch', 'tab' => 'patches', 'patch_id' => $patch->id, 'patch_name' => urlencode($patch->name), 'type' => $type), admin_url('tools.php'))); ?>"><?php _e('Install', 'optimizepress_patch'); ?></a>
                </td>
                <td>
                    <p><?php echo $patch->description; ?></p>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php endforeach; ?>

</div>