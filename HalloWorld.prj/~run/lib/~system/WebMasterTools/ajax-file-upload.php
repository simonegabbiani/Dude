<?php
# ~system/WebMasterTools/ajax-file-upload.xml

class _A8system_2FWebMasterTools_2Fajax_3Ffile_3Fupload_21xml {
  static $DS_F = array();
}
/* <part name='ajax-upload-head'> */
class _A8system_2FWebMasterTools_2Fajax_3Ffile_3Fupload_21xml_3Aajax_3Fupload_3Fhead extends Piece {
  const PART_NAME = '~system/WebMasterTools/ajax-file-upload.xml:ajax-upload-head';
  var $PART_BUILD_ID = 162;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	self::$buffer[$CONTEXT] .= "<script language=\"javascript\" src=\"".ROOT_PREFIX."/lib/~system/WebMasterTools/uploader.js\"></script><script language=\"javascript\">$(function() {\n\t\t\t\tjQuery('.dude_WMT_ajaxFileUpload_Button').each(function() {\n\t\t\t\t\tvar \n\t\t\t\t\t_this = jQuery(this),\n\t\t\t\t\t_childs = _this.children(),\n\t\t\t\t\t_file = jQuery(_childs[0]),\n\t\t\t\t\t_button = jQuery(_childs[1]),\n\t\t\t\t\t_progress = jQuery('#dude_WMT_ajaxFileUpload_ProgressBar_' + _file.attr('data-progressbar-id')),\n\t\t\t\t\tupl = new uploader(_file[0], {\n\t\t\t\t\t\turl:'".(ROOT_PREFIX.'ajax_3Fupload_3Fphp_3Fscript.php')."',\n\t\t\t\t\t\tprogress:function(ev){ var pc = Math.floor((ev.loaded/ev.total)*100); _progress.html(/*pc+'%'*/); _progress.css('width',pc+'%'); },\n\t\t\t\t\t\terror:function(ev){ console.log('error'); },\n\t\t\t\t\t\tsuccess:function(data){ \n\t\t\t\t\t\t\talert(_progress.length);\n\t\t\t\t\t\t\t_progress.html(/*'100%'*/);\n\t\t\t\t\t\t\t_progress.css('width', '100%'); \n\t\t\t\t\t\t\tvar file = _file[0].files[0], res;\n\t\t\t\t\t\t\ttry {\n\t\t\t\t\t\t\t\tres = JSON.parse(data);\n\t\t\t\t\t\t\t} catch (e) {\n\t\t\t\t\t\t\t\talert('Error uploading file: ' + data);\n\t\t\t\t\t\t\t}\n\t\t\t\t\t\t\tif (typeof res.status == 'undefined')\n\t\t\t\t\t\t\t\talert('Error uploading image (unknown error)');\n\t\t\t\t\t\t\telse if (res.status != 'Ok') \n\t\t\t\t\t\t\t\talert('Error uploading image' + res.status);\n\t\t\t\t\t\t\telse if (typeof res.result != 'undefined')\n\t\t\t\t\t\t\t\tconsole.log(file.name);\n\t\t\t\t\t\t}\n\t\t\t\t\t});\n\t\t\t\t\t_button.click(function(){\n\t\t\t\t\t\tupl.send();\n\t\t\t\t\t});\t\t\t\n\t\t\t\t});//each\n\t\t\t});</script>";;
	$this->DS_E = array(); 
  }
}

/* <part name='/ajax-upload-php-script'> */
class _A8system_2FWebMasterTools_2Fajax_3Ffile_3Fupload_21xml_3A_2Fajax_3Fupload_3Fphp_3Fscript extends Piece {
  const PART_NAME = '~system/WebMasterTools/ajax-file-upload.xml:/ajax-upload-php-script';
  var $PART_BUILD_ID = 163;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;
# upload basic
		# if there is a 'Public' folder, put there

		# pay attention to:
		# ini_get()
		# - upload_max_filesize
		# - max_execution_time
		# - max_input_time
		# - memory_limit
		# - post_max_size
		# - max_file_uploads
		# see also http://www.php.net/manual/en/features.file-upload.common-pitfalls.php very useful comments 
		# see also the limit 'Request Filtering' on IIS 7
		
		$buff = ''; $err = '';
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$buff .= 'upload-image.php runs only on POST method!';
		}
		else {
			$buff .= var_export($_FILES, true);
			if (file_exists('Public')) 
				$dest = './Public/'; 
			else 
				$dest = './';
			//tmp_name name error size type
			foreach (array_keys($_FILES) as $paramName) {
				//Already compatible with [] array param name, e.g. <input type="browse" name="files[]" />
				//compliant with PHP manual, every item of $_FILES is an array
				if (is_array($_FILES[$paramName]['tmp_name'])) {
					$files_tmp_name =& $_FILES[$paramName]['tmp_name'];
					$files_error =& $_FILES[$paramName]['error'];
					$files_name =& $_FILES[$paramName]['name'];
				}
				else {
					$files_tmp_name = array($_FILES[$paramName]['tmp_name']);
					$files_error = array($_FILES[$paramName]['error']);
					$files_name = array($_FILES[$paramName]['name']);
				}
				$msg[UPLOAD_ERR_OK] = 'Value: 0; There is no error, the file uploaded with success.';
				$msg[UPLOAD_ERR_INI_SIZE] = 'Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.';
				$msg[UPLOAD_ERR_FORM_SIZE] = 'Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
				$msg[UPLOAD_ERR_PARTIAL] = 'Value: 3; The uploaded file was only partially uploaded.';
				$msg[UPLOAD_ERR_NO_FILE] = 'Value: 4; No file was uploaded.';
				$msg[UPLOAD_ERR_NO_TMP_DIR] = 'Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
				$msg[UPLOAD_ERR_CANT_WRITE] = 'Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.';
				$msg[UPLOAD_ERR_EXTENSION] = 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.';
				foreach ($files_tmp_name as $i => $tmp_f) {
					if ($files_error[$i] != UPLOAD_ERR_OK)
						$err .= 'Error '.$msg[$files_error[$i]].' for file '.$tmp_f;
					else if (move_uploaded_file($tmp_f, $dest.$files_name[$i]) === false)
						$err .= " Error move_upload_file('$tmp_f', '$dest')\n";
				}
			}
			//in 'result' c'è la directory di destinazione
			echo json_encode(array('status' => ($err == '' ? 'Ok' : $err), 'result' => $dest));
		}
		file_put_contents('image-upload.txt', $buff . "\n\n" . $err);;
	$this->DS_E = array(); 
  }
}

/* <part name='dude-content-updater'> */
class _A8system_2FWebMasterTools_2Fajax_3Ffile_3Fupload_21xml_3Adude_3Fcontent_3Fupdater extends Piece {
  const PART_NAME = '~system/WebMasterTools/ajax-file-upload.xml:dude-content-updater';
  var $PART_BUILD_ID = 166;

  var $USED_SUBELEMENTS = array();

  public function main($CONTEXT, &$DS/*, Piece $__caller*/) {
	$this->DS =& $DS;

	$this->DS_E = array(); 
  }
}

if (!defined('AUTO_UPDATE_CACHE')) {
 Piece::$AUTO_UPDATE['~system/WebMasterTools/ajax-file-upload.xml'] = -1369558129;
 Piece::$FILES['/~system/WebMasterTools/uploader.js'] = array(true, false);
}
?>