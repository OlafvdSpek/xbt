<?

//-------------------------------------------------------------------
// Pretend we're a css sheet but process a saved cache file and convert
// over the img_dir stuff.
//
// Sneaky!
//-------------------------------------------------------------------


$root_path = './';

$in     = explode( "_", $_GET['d'], 2 );

$css_id = intval($in[0]);
$img_id = preg_replace( "/.css$/", "", $in[1] );
$data   = "";

if ( $css_id && $img_id )
{
	if ( $FH = @fopen( $root_path."cache/css_{$css_id}.css", 'r' ) )
	{
		$data = @fread( $FH, @filesize($root_path."cache/css_{$css_id}.css") );
		@fclose( $FH );
	}
}

$data = str_replace( "<#IMG_DIR#>", $img_id, $data );

@header( "Content-type: text/css" );
print $data;
exit();
	 
?>