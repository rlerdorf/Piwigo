<?php
// +-----------------------------------------------------------------------+
// | This file is part of Piwigo.                                          |
// |                                                                       |
// | For copyright and license information, please view the COPYING.txt    |
// | file that was distributed with this source code.                      |
// +-----------------------------------------------------------------------+

if (!defined('PHPWG_ROOT_PATH'))
{
  die ('This page cannot be loaded directly, load upgrade.php');
}
else
{
  if (!defined('PHPWG_IN_UPGRADE') or !PHPWG_IN_UPGRADE)
  {
    die ('Hacking attempt!');
  }
}

// +-----------------------------------------------------------------------+
// |             Fill upgrade table without applying upgrade               |
// +-----------------------------------------------------------------------+

// retrieve already applied upgrades
$query = '
SELECT id
  FROM '.PREFIX_TABLE.'upgrade
;';
$applied = array_from_query($query, 'id');

// retrieve existing upgrades
$existing = get_available_upgrade_ids();

// which upgrades need to be applied?
$to_apply = array_diff($existing, $applied);
$inserts = array();
foreach ($to_apply as $upgrade_id)
{
  if ($upgrade_id >= 98)
  {
    break;
  }
  
  array_push(
    $inserts,
    array(
      'id' => $upgrade_id,
      'applied' => CURRENT_DATE,
      'description' => '[migration from 2.2.0 to '.PHPWG_VERSION.'] not applied',
      )
    );
}

if (!empty($inserts))
{
  mass_inserts(
    '`'.UPGRADE_TABLE.'`',
    array_keys($inserts[0]),
    $inserts
    );
}

// +-----------------------------------------------------------------------+
// |                          Perform upgrades                             |
// +-----------------------------------------------------------------------+

ob_start();
echo '<pre>';

for ($upgrade_id = 98; $upgrade_id <= 111; $upgrade_id++)
{
  if (!file_exists(UPGRADES_PATH.'/'.$upgrade_id.'-database.php'))
  {
    break;
  }
  
  unset($upgrade_description);

  echo "\n\n";
  echo '=== upgrade '.$upgrade_id."\n";

  // include & execute upgrade script. Each upgrade script must contain
  // $upgrade_description variable which describe briefly what the upgrade
  // script does.
  include(UPGRADES_PATH.'/'.$upgrade_id.'-database.php');

  // notify upgrade
  $query = '
INSERT INTO `'.PREFIX_TABLE.'upgrade`
  (id, applied, description)
  VALUES
  (\''.$upgrade_id.'\', NOW(), \'[migration from 2.2.0 to '.PHPWG_VERSION.'] '.$upgrade_description.'\')
;';
  pwg_query($query);
}

echo '</pre>';
ob_end_clean();

// now we upgrade from 2.3.0
include_once(PHPWG_ROOT_PATH.'install/upgrade_2.3.0.php');
?>
