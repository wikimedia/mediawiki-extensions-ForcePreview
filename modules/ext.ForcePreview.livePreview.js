$( function () {
	"use strict";

	var preview = OO.ui.infuse( "wpPreviewWidget" ),
		save = OO.ui.infuse( "wpSaveWidget" ),
		newPage = mw.config.get( "wgArticleId" ) === 0,
		message;

	// This logic was lifted from EditPage::getSaveButtonLabel,
	// which is sadly a private function
	if ( mw.config.get( "wgEditSubmitButtonLabelPublish" ) ) {
		message = newPage ? "publishpage" : "publishchanges";
	} else {
		message = newPage ? "savearticle" : "savechanges";
	}

	preview.on( "click", function enableSave() {
		save.setFlags( { primary: true, constructive: true } )
			.setDisabled( false )
			.setLabel( OO.ui.msg( message ) );

		preview.setFlags( { primary: false, constructive: false } )
			.off( "click", enableSave );
	} );
} );
