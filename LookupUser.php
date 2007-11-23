<?php

/**
 * Extension to to retrieve information about a user such as email address and ID.
 *
 * @addtogroup Extensions
 * @author Tim Starling
 * @copyright 2006 Tim Starling
 * @licence GNU General Public Licence
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgExtensionFunctions[] = 'wfSetupLookupUser';
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Lookup User',
	'author' => 'Tim Starling',
	'description' => 'Retrieve information about a user such as email address and ID',
);

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['LookupUser'] = $dir . 'LookupUser.i18n.php';

$wgSpecialPages['LookupUser'] = 'LookupUserPage';
$wgAvailableRights[] = 'lookupuser';

function wfSetupLookupUser() {
	global $IP;

	class LookupUserPage extends SpecialPage {
		function __construct() {
		SpecialPage::SpecialPage( 'LookupUser', 'lookupuser' );
	}

	function getDescription() {
		return wfMsg( 'lookupuser' );
	}

		function execute( $subpage ) {
			global $wgRequest, $wgUser;
			wfLoadExtensionMessages( 'LookupUser' );

			$this->setHeaders();

			if ( !$wgUser->isAllowed( 'lookupuser' ) ) {
				$this->displayRestrictionError();
				return;
			}
			
			if ( $subpage ) {
				$target = $subpage;
			} else {
				$target = $wgRequest->getText( 'target' );
			}
			$this->showForm( $target );
			if ( $target ) {
				$this->showInfo( $target );
			}
		}

		function showForm( $target ) {
			global $wgScript, $wgOut;
			$title = htmlspecialchars( $this->getTitle()->getPrefixedText() );
			$action = htmlspecialchars( $wgScript );
			$target = htmlspecialchars( $target );
			$username = wfMsg( 'username' );
			$inputformtop = wfMsg( 'lookupuser' );

			$wgOut->addWikiText( wfMsg( 'lookupuser_intro' ));

			$wgOut->addHTML( <<<EOT
<fieldset>
<legend>$inputformtop</legend>
<form method="get" action="$action">
<input type="hidden" name="title" value="{$title}" />
<table border="0">
<tr>
<td align="right">$username</td>
<td align="left"><input type="text" size="50" name="target" value="$target" />
<td colspan="2" align="center"><input type="submit" name="submit" value="OK" /></td>
</tr>
</table>
</form>
</fieldset>
EOT
			);
		}

		function showInfo( $target ) {
			global $wgOut, $wgLang;
			$user = User::newFromName( $target );
			if ( $user->getId() == 0 ) {
				$wgOut->addWikiText( '<span class="error">' . wfMsg( 'lookupuser_nonexistent', $target ) . '</span>' );
			} else {
				$authTs = $user->getEmailAuthenticationTimestamp();
				if ( $authTs ) {
					$authenticated = wfMsg( 'lookupuser_authenticated', $wgLang->timeanddate( $authTs ) );
				} else {
					$authenticated = wfMsg( 'lookupuser_not_authenticated' );
				}
				$optionsString = '';
				foreach ( $user->mOptions as $name => $value ) {
					$optionsString .= "$name = $value <br />";
				}
				$name = $user->getName();
				$wgOut->addWikiText( '*' . wfMsg( 'username' ) . ' [[User:' . $name . '|' . $name . ']] ([[User talk:' . $name . '|' . wfMsg( 'talkpagelinktext' ) . ']] | [[Special:Contributions/' . $name . '|' . wfMsg( 'contribslink' ) . ']])' );
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_id', $user->getId() ));
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_email', $user->getEmail(), $name ));
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_realname', $user->getRealName() ));
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_registration', $wgLang->timeanddate( $user->mRegistration ) ));
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_touched', $wgLang->timeanddate( $user->mTouched ) ));
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_authenticated', $authenticated ));
				$wgOut->addWikiText( '*' . wfMsg( 'lookupuser_useroptions' ) . '<br />' . $optionsString );
			}
		}
	}

}
