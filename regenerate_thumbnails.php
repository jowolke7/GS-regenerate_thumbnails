<?php
/*
Plugin Name: regenerate_thumbnails
Description: regenerate thumbnails.
Version: 1.1
Author: jjancel 15/05/2024 18:00
Author URI: http://jjancel.free.fr/

Edit: jofer 14.11.2024 DEVELOPERS VERSION

*/

 
# get correct id for plugin
$thisfile=basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile,       # Plugin id
	'Regenerate Thumbnails', # Plugin name
	'1.1',      # Plugin version
	'jjancel',  # Plugin author
	'http://jjancel.free.fr/', # author website
	'Regenerate Thumbnails', # Plugin description
	'files', # page type of plugin
	'regenerate_thumbnails_show' # main plugin function
);

# activate filter
add_action('files-sidebar','createSideMenu',array($thisfile,'Regenerate Thumbnails'));

# functions
function regenerate_thumbnails_show() {
	if (isset($_POST['submitted'])) {
		echo '<h3>list of all images in the data/uploads folder and subfolders</h3>';
		$uploaddir = '../data/uploads';
		listFolderFiles($uploaddir);
	}
	echo '<h3>How use Regenerate Thumbnails on your files?</h3>';
	echo "<p>Regenerate Thumbnails allows you to regenerate all thumbnails for all uploaded images in your data/uploads folder and sub folder.</p> <p>This is useful in situations such as:</p> <ul> 	<li>The thumbnail size has changed and you want previous uploads to have a thumbnail of this size.</li> 	<li>You've moved to a new GetSimple theme that uses thumbnail images of a different size.</li> </ul> <p>In order to free up space on the server you can delete old, unused thumbnails from the data/thumbs folder and sub folder.</p>";
	echo '<form method="post" action="';
	echo $_SERVER["PHP_SELF"];
	echo '?id=regenerate_thumbnails"> <input type="submit" name="submitted"/> </form>';
}

# functions thumbnails
function frmtFolder($Entity) {
	echo '<li style="font-weight:bold;color:black;list-style-type:none">'.$Entity;
}

function frmtFile($dEntry, $fEntry) {
	if (preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.webp)$/i', $fEntry)) {
		$subFolder = substr($dEntry, 16).'/';
		echo '<li style="list-style-type:square;" > <a href="'.$dEntry.'/'.$fEntry.'" rel="facybox_i" style="text-decoration:none;"> <img src="'.$dEntry.'/'.$fEntry.'" width="20px" style="vertical-align:middle;"> '.$fEntry.' </a><br>';



        //jf: beg
        //
        //
  		echo '<li style="list-style-type:square;">1: dEntry: >'.$dEntry."<  fEntry: >".$fEntry.'< "<br>';
        //
        //
        // 'data/uploads/' and 'data/thumbs/' seems to be fixed, so ...  
        //
        $thumbsPath = str_replace('data/uploads', 'data/thumbs', $dEntry);
  		echo '<li style="list-style-type:square;">2: thumbsPath: >'.$thumbsPath."<  fEntry: >".$fEntry.'< "<br>';
        //
        //
        // 'data/thumbs/somefolder' does NOT exist ... create it and change mode
        // Here is the point to 'mkdir' empty folders too. (If an usable file/image is inside the structure!)
        //        
		if (!(file_exists($thumbsPath))) {
			if (defined('GSCHMOD')) { 
				$chmod_value = GSCHMOD; 
			} else {
				$chmod_value = 0755;
			}
    		echo '<li style="list-style-type:square;">3. mkdir('.$thumbsPath.',...,true)<br>';
			// HERE: mkdir() w/ $recursive = true. (https://www.php.net/manual/en/function.mkdir.php)
			mkdir($thumbsPath, $chmod_value, true); 
        }
        //
        //
        // folder(s) created
        //
        if ($subFolder == '/' ) {$subFolder='';}    //jf: no "//": no "data/thumbs//", but "data/thumbs/" 
  		echo '<li style="list-style-type:square;">4: $subFolder: >'.$subFolder."<  fEntry: >".$fEntry.'< "<br>';
  		echo '<li style="list-style-type:square;">5: absolute: >'.GSTHUMBNAILPATH.$subFolder."-".$fEntry.'< "<br>';

		// generate thumbnail
		local_genStdThumb($subFolder,$fEntry);
        //
        //
        //jf: end

  		
//jf:		// generate thumbnail
//jf:		require_once('inc/imagemanipulation.php');	
//jf:		genStdThumb($subFolder,$fEntry);
    
		
	}
}

function listFolderFiles($dir) {
	$ffs = scandir($dir);
	unset($ffs[array_search('.', $ffs, true)]);
	unset($ffs[array_search('..', $ffs, true)]);
	unset($ffs[array_search('index.html', $ffs, true)]);
	// prevent empty ordered elements
	if (count($ffs) < 1) {return;}
	echo '<div class="All Images Images iimage"><ul>';
	foreach ($ffs as $ff) {
		if (is_dir($dir . '/' . $ff)) {
			frmtFolder($dir);
		} else {
			frmtFile($dir, $ff);
		}
		if (is_dir($dir . '/' . $ff)) {
			listFolderFiles($dir . '/' . $ff);
		}
		echo '</li>';
	}
	echo '</ul></div>';
}



//jf: beg Replacement of core function genStdThumb (inc/imagemanipulation.php)
//
// Because of two points:
//
// 1. mkdir() w/ $recursive = true to create a folder AND all missing parent folders 
//    (https://www.php.net/manual/en/function.mkdir.php)
// 
// 34 mkdir($thumbsPath, $chmod_value);
//    mkdir($thumbsPath, $chmod_value, true);
//   
//
// 
// 2. Doesn't get thunbnails from .jpeg
//      Not never and not ever. I dont know. Maybe it is due to an entangled quantum in Alpha Centauri ;-)
//
//	75  switch(lowercase(pathinfo($targetFile)['extension'])) {
//	        case "jpeg":                                               //jf: w/o "jpeg" it works sometimes, but not everytimes. I don't know why!
//	        case "jpg":
//
function local_genStdThumb_jf($path,$name){

	echo '<li style="list-style-type:square;">6: inside local_genStdThumb()" <br>';                //jf:
	echo '<li style="list-style-type:square;">7: path: >'.$path."<  name: >".$name.'< "<br>';      //jf:

	//gd check
	$php_modules = get_loaded_extensions();
	if(!in_arrayi('gd', $php_modules)) return;

	if (!defined('GSIMAGEWIDTH')) {
		$width = 200; //New width of image  	
	} else {
		$width = GSIMAGEWIDTH;
	}

	$ext = lowercase(pathinfo($name,PATHINFO_EXTENSION));	
	
	if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'gif' || $ext == 'png' || $ext == 'webp' )	{
		
		$thumbsPath = GSTHUMBNAILPATH.$path;
		
		if (!(file_exists($thumbsPath))) {
			if (defined('GSCHMOD')) { 
				$chmod_value = GSCHMOD; 
			} else {
				$chmod_value = 0755;
			}
			mkdir($thumbsPath, $chmod_value, true);
		}
	}

	$targetFile = GSDATAUPLOADPATH.$path.$name;
	echo '<li style="list-style-type:square;">8: targetFile: >'.$targetFile."<  name: >".$name.'< "<br>';  //jf:
	
	//thumbnail for post
	$imgsize = getimagesize($targetFile);
		
	switch($ext){
			case "jpeg":
			case "jpg":
					$image = imagecreatefromjpeg($targetFile);    
			break;
			case "png":
					$image = imagecreatefrompng($targetFile);
			break;
			case "gif":
					$image = imagecreatefromgif($targetFile);
			break;
			case "webp":
					$image = imagecreatefromwebp($targetFile);
			break;
			default:
					return;
			break;
	}
		
	$height = $imgsize[1]/$imgsize[0]*$width; //This maintains proportions
	
	$src_w = $imgsize[0];
	$src_h = $imgsize[1];
	
	$picture = @imagecreatetruecolor($width, $height);
	imagealphablending($picture, false);
	imagesavealpha($picture, true);
	$bool = @imagecopyresampled($picture, $image, 0, 0, 0, 0, $width, $height, $src_w, $src_h); 
	
	if($bool)	{	
		$thumbnailFile = $thumbsPath . "thumbnail." . $name;
    	echo '<li style="list-style-type:square;">9: $thumbnailFile >'.$thumbnailFile.'< "<br>';    //jf:
		
	    switch(lowercase(pathinfo($targetFile)['extension'])) {
	        case "jpeg":                                               //jf: w/o "jpeg" it works sometimes, but not everytimes. I don't know why!
	        case "jpg":
	            $bool2 = imagejpeg($picture,$thumbnailFile,85);
	        break;
	        case "png":
	            imagepng($picture,$thumbnailFile);
	        break;
	        case "gif":
	            imagegif($picture,$thumbnailFile);
	        break;
	        case "webp":
	            imagewebp($picture,$thumbnailFile,85);
	        break;
	    }
	}
	
	imagedestroy($picture);
	imagedestroy($image);

	return true;
}
//jf: end Replacement of core function genStdThumb (inc/imagemanipulation.php)


?>