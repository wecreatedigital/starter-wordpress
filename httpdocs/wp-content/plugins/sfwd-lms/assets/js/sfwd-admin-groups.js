
function SelectAddRows( SS1,SS2 )	{
	var SelID='';
	var SelText='';
	// Move rows from SS1 to SS2 from bottom to top
	for ( i=SS1.options.length - 1; i>=0; i-- ) {
		if ( SS1.options[i].selected == true ) {
			SelID=SS1.options[i].value;
			SelText=SS1.options[i].text;
			var newRow = new Option( SelText, SelID );
			SS2.options[SS2.length]=newRow;
		}
	}
}

function SelectRemoveRows( SS1 ) {
	var SelID='';
	var SelText='';
	// Move rows from SS1 to SS2 from bottom to top
	for ( i=SS1.options.length - 1; i>=0; i-- ) {
		if ( SS1.options[i].selected == true ) {
			SS1.options[i]=null;
		}
	}
}

function SelectAll( ID ) {
	for ( i=ID.options.length - 1; i>=0; i-- ) {
		ID.options[i].selected = true;
	}
}

function group_user_search() {
	SS1 = document.getElementById('learndash_group_users_search');
	SS_View = document.getElementById('learndash_group_users_view');
	searchfor = document.getElementById('search_group').value.toLowerCase();
	SS_View.options.length = 0;
	length = 0;
	for ( i = 0; i < SS1.options.length; i++ ) {
		SelText=SS1.options[i].text;
		if( SelText.toLowerCase().search( searchfor ) < 0 && searchfor.length > 0 ) {
			SS1.options[i].disabled = true;
		} else {
			SS1.options[i].disabled = false;
			length++;
			SS_View.options.length = length;
			SS_View.options[length-1].value = SS1.options[i].value;
			SS_View.options[length-1].text = SS1.options[i].text;
			SS_View.options[length-1].title = SS1.options[i].title;

		}
	}
}
