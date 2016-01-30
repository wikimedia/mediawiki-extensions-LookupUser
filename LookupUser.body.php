<?php
/**
 * Provides the special page to look up user info
 *
 * @file
 */
class LookupUserPage extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'LookupUser'/*class*/, 'lookupuser'/*restriction*/ );
	}

	function getDescription() {
		return $this->msg( 'lookupuser' )->text();
	}

	/**
	 * Show the special page
	 *
	 * @param mixed|null $subpage Parameter passed to the page (user name or email address)
	 */
	public function execute( $subpage ) {
		$this->setHeaders();

		# If the user doesn't have the required 'lookupuser' permission, display an error
		if ( !$this->getUser()->isAllowed( 'lookupuser' ) ) {
			$this->displayRestrictionError();
			return;
		}

		if ( $subpage ) {
			$target = $subpage;
		} else {
			$target = $this->getRequest()->getText( 'target' );
		}

		$this->showForm( $target );

		if ( $target ) {
			$emailUser = $this->getRequest()->getText( 'email_user' );
			$this->showInfo( $target, $emailUser );
		}
	}

	/**
	 * Show the LookupUser form
	 * @param mixed $target User whose info we're about to look up
	 */
	function showForm( $target ) {
		global $wgScript;

		$title = htmlspecialchars( $this->getPageTitle()->getPrefixedText(), ENT_QUOTES );
		$action = htmlspecialchars( $wgScript, ENT_QUOTES );
		$target = htmlspecialchars( $target, ENT_QUOTES );
		$ok = $this->msg( 'go' )->text();
		$username = $this->msg( 'username' )->text();
		$inputformtop = $this->msg( 'lookupuser' )->text();

		$this->getOutput()->addWikiMsg( 'lookupuser-intro' );

		$this->getOutput()->addHTML( <<<EOT
<fieldset>
<legend>$inputformtop</legend>
<form method="get" action="$action">
<input type="hidden" name="title" value="{$title}" />
<table border="0">
<tr>
<td align="right">$username</td>
<td align="left"><input type="text" size="50" name="target" value="$target" />
<td colspan="2" align="center"><input type="submit" name="submit" value="$ok" /></td>
</tr>
</table>
</form>
</fieldset>
EOT
		);
	}

	/**
	 * Retrieves and shows the gathered info to the user
	 * @param mixed $target User whose info we're looking up
	 * @param string $emailUser E-mail address (like example@example.com)
	 */
	function showInfo( $target, $emailUser = '' ) {
		global $wgScript;

		$lang = $this->getLanguage();
		$out = $this->getOutput();

		$count = 0;
		$users = array();
		$userTarget = '';

		// Look for @ in username
		if ( strpos( $target, '@' ) !== false ) {
			// Find username by email
			$emailUser = htmlspecialchars( $emailUser, ENT_QUOTES );
			$dbr = wfGetDB( DB_SLAVE );

			$res = $dbr->select(
				'user',
				array( 'user_name' ),
				array( 'user_email' => $target ),
				__METHOD__
			);

			$loop = 0;
			foreach ( $res as $row ) {
				if ( $loop === 0 ) {
					$userTarget = $row->user_name;
				}
				if ( !empty( $emailUser ) && ( $emailUser == $row->user_name ) ) {
					$userTarget = $emailUser;
				}
				$users[] = $row->user_name;
				$loop++;
			}
			$count = $loop;
		}

		$ourUser = ( !empty( $userTarget ) ) ? $userTarget : $target;
		$user = User::newFromName( $ourUser );
		if ( $user == null || $user->getId() == 0 ) {
			$out->addWikiText( '<span class="error">' . $this->msg( 'lookupuser-nonexistent', $target )->text() . '</span>' );
		} else {
			# Multiple matches?
			if ( $count > 1 ) {
				$options = array();
				if ( !empty( $users ) && is_array( $users ) ) {
					foreach ( $users as $id => $userName ) {
						$options[] = Xml::option( $userName, $userName, ( $userName == $userTarget ) );
					}
				}
				$selectForm = "\n" . Xml::openElement( 'select', array( 'id' => 'email_user', 'name' => 'email_user' ) );
				$selectForm .= "\n" . implode( "\n", $options ) . "\n";
				$selectForm .= Xml::closeElement( 'select' ) . "\n";

				$out->addHTML(
					Xml::openElement( 'fieldset' ) . "\n" .
					Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript ) ) . "\n" .
					Html::hidden( 'title', $this->getPageTitle()->getPrefixedText() ) . "\n" .
					Html::hidden( 'target', $target ) . "\n" .
					Xml::openElement( 'table', array( 'border' => '0' ) ) . "\n" .
					Xml::openElement( 'tr' ) . "\n" .
					Xml::openElement( 'td', array( 'align' => 'right' ) ) .
					$this->msg( 'lookupuser-foundmoreusers' )->text() .
					Xml::closeElement( 'td' ) . "\n" .
					Xml::openElement( 'td', array( 'align' => 'left' ) ) . "\n" .
					$selectForm . Xml::closeElement( 'td' ) . "\n" .
					Xml::openElement( 'td', array( 'colspan' => '2', 'align' => 'center' ) ) .
					Xml::submitButton( $this->msg( 'go' )->text() ) .
					Xml::closeElement( 'td' ) . "\n" .
					Xml::closeElement( 'tr' ) . "\n" .
					Xml::closeElement( 'table' ) . "\n" .
					Xml::closeElement( 'form' ) . "\n" .
					Xml::closeElement( 'fieldset' )
				);
			}

			$authTs = $user->getEmailAuthenticationTimestamp();
			if ( $authTs ) {
				$authenticated = $this->msg( 'lookupuser-authenticated', $lang->timeanddate( $authTs ) )->parse();
			} else {
				$authenticated = $this->msg( 'lookupuser-not-authenticated' )->text();
			}
			$optionsString = '';
			foreach ( $user->getOptions() as $name => $value ) {
				$optionsString .= "$name = $value <br />";
			}
			$name = $user->getName();
			if ( $user->getEmail() ) {
				$email = $user->getEmail();
			} else {
				$email = $this->msg( 'lookupuser-no-email' )->text();
			}
			if ( $user->getRegistration() ) {
				$registration = $lang->timeanddate( $user->getRegistration() );
			} else {
				$registration = $this->msg( 'lookupuser-no-registration' )->text();
			}
			$out->addWikiText( '*' . $this->msg( 'username' )->text() . ' [[User:' . $name . '|' . $name . ']] (' .
				$lang->pipeList( array(
					'[[User talk:' . $name . '|' . $this->msg( 'talkpagelinktext' )->text() . ']]',
					'[[Special:Contributions/' . $name . '|' . $this->msg( 'contribslink' )->text() . ']])'
				) ) );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-id', $user->getId() )->text() );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-email', $email, $name )->text() );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-realname', $user->getRealName() )->text() );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-registration', $registration )->text() );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-touched', $lang->timeanddate( $user->mTouched ) )->text() );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-info-authenticated', $authenticated )->text() );
			$out->addWikiText( '*' . $this->msg( 'lookupuser-useroptions' )->text() . '<br />' . $optionsString );
		}
	}

	protected function getGroupName() {
		return 'users';
	}

	/**
	 * Add a link to Special:LookupUser from Special:Contributions/USERNAME if
	 * the user has 'lookupuser' permission
	 *
	 * @return bool
	 */
	public static function onContributionsToolLinks( $id, $nt, &$links ) {
		global $wgUser;
		if ( $wgUser->isAllowed( 'lookupuser' ) && !User::isIP( $nt->getText() ) ) {
			$links[] = Linker::linkKnown(
				SpecialPage::getTitleFor( 'LookupUser' ),
				wfMessage( 'lookupuser' )->plain(),
				array(),
				array( 'target' => $nt->getText() )
			);
		}
		return true;
	}

}
