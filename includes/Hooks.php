<?php

namespace MediaWiki\Extension\LookupUser;

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Specials\Hook\ContributionsToolLinksHook;
use MediaWiki\Title\Title;

class Hooks implements ContributionsToolLinksHook {

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
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();
		$isIp = $userNameUtils->isIP( $nt->getText() );

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
