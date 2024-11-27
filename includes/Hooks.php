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

namespace MediaWiki\Extension\ForcePreview;

use MediaWiki\Config\Config;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Hook\EditPageBeforeEditButtonsHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\User\Options\UserOptionsLookup;
use Skin;

class Hooks implements
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

	/**
	 * @param EditPage $editpage Current EditPage object
	 * @param array &$buttons Array of edit buttons, "Save", "Preview", "Live", and "Diff"
	 * @param int &$tabindex HTML tabindex of the last edit check/button
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEditPageBeforeEditButtons( $editpage, &$buttons, &$tabindex ) {
		$context = $editpage->getContext();
		$isInitialLoad = !$editpage->preview && !$editpage->save;

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

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return void This hook must not abort, it must return no value
	 */
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

	/**
	 * @param array &$vars `[ variable name => value ]`
	 * @param string $skin
	 * @param Config $config
	 * @return void This hook must not abort, it must return no value
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['wgEditSubmitButtonLabelPublish'] = $config->get( 'EditSubmitButtonLabelPublish' );
	}
}
