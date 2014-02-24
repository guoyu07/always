<?php
namespace always\Factory;
/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ImageFactory {
    public static function getProfileImages(\always\Resource\Profile $profile)
    {
        $db = \Database::newDB();
        $tbl = $db->addTable('always_image');
        $tbl->addFieldConditional('profile_id', $profile->getOriginalId());
        $result = $db->select();
        if (empty($result)) {
            return null;
        }
        $img_url = $profile->getImageUrl();
        foreach ($result as $img) {
            extract($img);
            $thumb = $img_url . 'thumbnail/' . $path;
            $url = $img_url . $path;
            $cnt[] = <<<EOF
<a href="$url" title="$caption"><img src="$thumb" alt="$caption" /></a>
EOF;
        }

        return implode("\n", $cnt);

    }
}

?>
