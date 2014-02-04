<?php

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function always_update(&$content, $current_version)
{
    switch ($current_version) {
        case version_compare($current_version, '1.1.0', '<'):
            $image = new \always\Resource\Image;
            $db = \Database::newDB();
            $image->createTable($db);
            $content[] = <<<EOF
<pre>1.1.0 updates
-------------------
- Added image gallery.
</pre>
EOF;
    }
    return true;
}

?>
