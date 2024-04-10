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
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\User\UserOptionsLookup;

class ForcePreview implements
	BeforePageDisplayHook,
	EditPageBeforeEditButtonsHook,
	ResourceLoaderGetConfigVarsHook
{
	private PermissionManager $permissionManager;
	private UserOptionsLookup $userOptionsLookup;

	public function __construct(
		PermissionManager $permissionManager,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->permissionManager = $permissionManager;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	public function onEditPageBeforeEditButtons( $editpage, &$buttons, &$tabindex ) {
		$context = $editpage->getContext();
		$isInitialLoad = !$editpage->preview && empty( $editpage->save );

		if (
			!$context->getUser()->isAllowed( 'forcepreviewexempt' ) &&
			$isInitialLoad &&
			isset( $buttons['preview'] )
		) {
			$buttons['save']->setDisabled( true );
			$buttons['save']->setLabel( $context->msg( 'forcepreview', $buttons['save']->getLabel() )->text() );
			$buttons['save']->setFlags( [ 'primary' => false ] );
			$buttons['preview']->setFlags( [ 'primary' => true, 'progressive' => true ] );
		}
	}

	public function onBeforePageDisplay( $out, $skin ): void {
		$user = $out->getUser();
		$request = $out->getRequest();

		if (
			$user->isAllowed( 'forcepreviewexempt' )
			|| !$this->userOptionsLookup->getBoolOption( $user, 'uselivepreview' )
			|| !in_array( $request->getRawVal( 'action' ), [ 'edit', 'submit' ] )
		) {
			return;
		}

		$title = $out->getTitle();
		if ( !$this->permissionManager->userCan( 'edit', $user, $title ) ) {
			return;
		}

		$out->addModules( 'ext.ForcePreview.livePreview' );
	}

	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['wgEditSubmitButtonLabelPublish'] = $config->get( 'EditSubmitButtonLabelPublish' );
	}
}
