<?php
/**
 * ForcePreview extension -- forces unprivileged users to preview before saving
 *
 * @file
 * @ingroup Extensions
 * @author Ryan Schmidt
 * @license MIT
 * @link https://www.mediawiki.org/wiki/Extension:ForcePreview Documentation
 */

use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\EditPageBeforeEditButtonsHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;

class ForcePreview implements
	BeforePageDisplayHook,
	EditPageBeforeEditButtonsHook,
	ResourceLoaderGetConfigVarsHook
{
	public function onEditPageBeforeEditButtons( $editpage, &$buttons, &$tabindex ) {
		$user = $editpage->getContext()->getUser();
		$isInitialLoad = !$editpage->preview && empty( $editpage->save );

		if (
			!$user->isAllowed( 'forcepreviewexempt' ) &&
			$isInitialLoad &&
			isset( $buttons['preview'] )
		) {
			$buttons['save']->setDisabled( true );
			$buttons['save']->setLabel( wfMessage( 'forcepreview', $buttons['save']->getLabel() )->text() );
			$buttons['save']->setFlags( [ 'primary' => false ] );
			$buttons['preview']->setFlags( [ 'primary' => true, 'progressive' => true ] );
		}
	}

	public function onBeforePageDisplay( $out, $skin ): void {
		$user = $out->getUser();
		$request = $out->getRequest();

		$services = MediaWikiServices::getInstance();

		if (
			$user->isAllowed( 'forcepreviewexempt' )
			|| !$services->getUserOptionsLookup()->getBoolOption( $user, 'uselivepreview' )
			|| !in_array( $request->getVal( 'action' ), [ 'edit', 'submit' ] )
		) {
			return;
		}

		$title = $out->getTitle();
		if ( !$services
			->getPermissionManager()
			->userCan( 'edit', $user, $title )
		) {
			return;
		}

		$out->addModules( 'ext.ForcePreview.livePreview' );
	}

	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['wgEditSubmitButtonLabelPublish'] = $config->get( 'EditSubmitButtonLabelPublish' );
	}
}
