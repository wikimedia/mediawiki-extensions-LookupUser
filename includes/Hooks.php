<?php

namespace MediaWiki\Extension\LookupUser;

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Specials\Hook\ContributionsToolLinksHook;
use MediaWiki\Title\Title;
use MediaWiki\User\UserNameUtils;

class Hooks implements ContributionsToolLinksHook {

	public function __construct(
		private readonly UserNameUtils $userNameUtils,
	) {
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
	public function onContributionsToolLinks( $id, Title $nt, array &$links, SpecialPage $sp ) {
		$isIp = $this->userNameUtils->isIP( $nt->getText() );

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
