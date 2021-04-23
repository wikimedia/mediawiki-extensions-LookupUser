<?php

use MediaWiki\MediaWikiServices;

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

	/** @inheritDoc */
	function getDescription() {
		return $this->msg( 'lookupuser' )->text();
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $subpage Parameter passed to the page (user name or email address)
	 */
	public function execute( $subpage ) {
		$this->setHeaders();

		# If the user doesn't have the required 'lookupuser' permission, display an error
		$this->checkPermissions();

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
	 *
	 * @param string $target User whose info we're about to look up
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

		$formDescriptor = [
			'textbox' => [
				'type' => 'user',
				'name' => 'target',
				'label' => $username,
				'size' => 50,
				'default' => $target,
			]
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->addHiddenField( 'title', $title )
			->setAction( $action )
			->setMethod( 'get' )
			->setSubmitName( 'submit' )
			->setSubmitText( $ok )
			->setWrapperLegend( $inputformtop )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Retrieves and shows the gathered info to the user
	 *
	 * @param string $target User whose info we're looking up
	 * @param string $emailUser E-mail address (like example@example.com)
	 */
	function showInfo( $target, $emailUser = '' ) {
		global $wgScript;

		$lang = $this->getLanguage();
		$out = $this->getOutput();

		$count = 0;
		$users = [];
		$userTarget = '';

		// Look for @ in username
		if ( strpos( $target, '@' ) !== false ) {
			// Find username by email
			$emailUser = htmlspecialchars( $emailUser, ENT_QUOTES );
			$dbr = wfGetDB( DB_REPLICA );

			$res = $dbr->select(
				'user',
				[ 'user_name' ],
				[ 'user_email' => $target ],
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
			$out->addWikiTextAsInterface( '<span class="error">' . $this->msg( 'lookupuser-nonexistent', $target )->text() . '</span>' );
		} else {
			# Multiple matches?
			if ( $count > 1 ) {
				$options = [];
				if ( !empty( $users ) && is_array( $users ) ) {
					foreach ( $users as $id => $userName ) {
						$option[$userName] = $userName;
					}
				}

				$formDescriptor = [
					'select' => [
						'type' => 'select',
						'name' => 'email_user',
						'id' => 'email_user',
						'label' => $this->msg( 'lookupuser-foundmoreusers' )->text(),
						'options' => $option,
						'value' => $userName == $userTarget,
					]
				];

				$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
				$htmlForm
					->addHiddenField( 'title', $this->getPageTitle()->getPrefixedText() )
					->addHiddenField( 'target', $target )
					->setMethod( 'get' )
					->setAction( $wgScript )
					->setSubmitText( $this->msg( 'go' )->text() )
					->setWrapperLegend( null )
					->prepareForm()
					->displayForm( false );
			}

			$authTs = $user->getEmailAuthenticationTimestamp();
			if ( $authTs ) {
				$authenticated = $this->msg( 'lookupuser-authenticated', $lang->timeanddate( $authTs ) )->parse();
			} else {
				$authenticated = $this->msg( 'lookupuser-not-authenticated' )->text();
			}
			$optionsString = '';

			if ( method_exists( MediaWikiServices::class, 'getUserOptionsLookup' ) ) {
				// MW 1.35+
				$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
				$options = $userOptionsLookup->getOptions( $user );
			} else {
				$options = $user->getOptions();
			}

			foreach ( $options as $name => $value ) {
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
			$out->addWikiTextAsInterface( '*' . $this->msg( 'username' )->text() . ' [[User:' . $name . '|' . $name . ']] (' .
				$lang->pipeList( [
					'[[User talk:' . $name . '|' . $this->msg( 'talkpagelinktext' )->text() . ']]',
					'[[Special:Contributions/' . $name . '|' . $this->msg( 'contribslink' )->text() . ']])'
				] ) );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-id', $user->getId() )->text() );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-email', $email, $name )->text() );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-realname', $user->getRealName() )->text() );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-registration', $registration )->text() );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-touched', $lang->timeanddate( $user->mTouched ) )->text() );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-info-authenticated', $authenticated )->text() );
			$out->addWikiTextAsInterface( '*' . $this->msg( 'lookupuser-useroptions' )->text() . '<br />' . $optionsString );
		}
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'users';
	}

	/**
	 * Add a link to Special:LookupUser from Special:Contributions/USERNAME if
	 * the user has 'lookupuser' permission
	 *
	 * @param int $id
	 * @param Title $nt
	 * @param array &$links
	 * @param SpecialPage $sp
	 */
	public static function onContributionsToolLinks( $id, $nt, &$links, SpecialPage $sp ) {
		if ( method_exists( MediaWikiServices::class, 'getUserNameUtils' ) ) {
			// MW 1.35+
			$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
			$isIp = $userNameUtils->isIP( $nt->getText() );
		} else {
			$isIp = User::isIP( $nt->getText() );
		}

		if ( $sp->getUser()->isAllowed( 'lookupuser' ) && !$isIp ) {
			$links[] = $sp->getLinkRenderer()->makeKnownLink(
				SpecialPage::getTitleFor( 'LookupUser' ),
				$sp->msg( 'lookupuser' )->text(),
				[],
				[ 'target' => $nt->getText() ]
			);
		}
	}

}
