<?php
/**
 * Extension to to retrieve information about a user such as email address and ID.
 *
 * @file
 * @ingroup Extensions
 * @author Tim Starling
 * @copyright © 2006 Tim Starling
 * @licence GNU General Public Licence
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Lookup User',
	'version' => '1.4',
	'author' => 'Tim Starling',
	'url' => 'https://www.mediawiki.org/wiki/Extension:LookupUser',
	'descriptionmsg' => 'lookupuser-desc',
);

// Set up the new special page
$wgMessagesDirs['LookupUser'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['LookupUserAlias'] = __DIR__ . '/LookupUser.alias.php';
$wgAutoloadClasses['LookupUserPage'] = __DIR__ . '/LookupUser.body.php';
$wgSpecialPages['LookupUser'] = 'LookupUserPage';

// New user right, required to use the special page
$wgAvailableRights[] = 'lookupuser';

// Hooked function
$wgHooks['ContributionsToolLinks'][] = 'LookupUserPage::onContributionsToolLinks';