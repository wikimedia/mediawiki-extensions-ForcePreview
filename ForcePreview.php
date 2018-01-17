<?php
/**
 * ForcePreview extension -- forces unprivileged users to preview before saving
 *
 * @file
 * @ingroup Extensions
 * @author Ryan Schmidt
 * @license MIT
 * @link http://www.mediawiki.org/wiki/Extension:ForcePreview Documentation
 */

class ForcePreview {
	public static function onEditPageBeforeEditButtons( &$editpage, &$buttons, &$tabindex ) {
		$user = $editpage->getContext()->getUser();
		$isInitialLoad = !$editpage->preview && empty( $editpage->save );

		if ( !$user->isAllowed( 'forcepreviewexempt' ) && $isInitialLoad ) {
			$buttons['save']->setDisabled( true );
			$buttons['save']->setLabel( wfMessage( 'forcepreview', $buttons['save']->getLabel() )->text() );
			$buttons['save']->setFlags( [ 'primary' => false ] );
			$buttons['preview']->setFlags( [ 'primary' => true, 'progressive' => true ] );
		}

		return true;
	}

	public static function onBeforePageDisplay( &$out, &$skin ) {
		$user = $out->getUser();
		$request = $out->getRequest();
		$title = $out->getTitle();
		
		if (
			!$title->userCan( 'edit' )
			|| $user->isAllowed( 'forcepreviewexempt' )
			|| !$user->getBoolOption( 'uselivepreview' )
			|| !in_array( $request->getVal( 'action' ), [ 'edit', 'submit' ] )
		) {
			return true;
		}

		$out->addModules( 'ext.ForcePreview.livePreview' );
		return true;
	}

	public static function onResourceLoaderGetConfigVars( &$vars ) {
		$config = MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
		$vars['wgEditSubmitButtonLabelPublish'] = $config->get( 'EditSubmitButtonLabelPublish' );
		return true;
	}
}
