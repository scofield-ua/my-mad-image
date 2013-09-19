<?php
App::uses('Component', 'Controller');
class MyMadImageComponent extends Component {
	private $errors;
	private $upload_result;
	private $data;

	private $default_params = array(
        'folder' => '', // will be initialized
		'security_check' => true,
        'rewrite' => false,
        'create_folder_if_not_exist' => true,
		'security_check_function' => '_defaultCheck',
		'return_url' => false,
		'file_types' =>  array('image/gif','image/jpeg','image/pjpeg','image/png', 'image/jpg', 'image/x-png', 'image/bmp'),
		'file_exts' => array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'pjpeg', 'bmp')// white list of file extensions
    );

	public function initialize(Controller $controller) {
        $this->default_params['folder'] = IMAGES_URL.'uploaded';
    }

	private function _addError($str, $fileindex = null) {
		$input_name = key($this->data);
		$str = __($str);

		if($fileindex === null) {
			$this->errors[] = $str;
		} else {
			$this->errors[][$fileindex] = array(
				'message' => $str,
				'file_name' => $this->data[$input_name]['name'][$fileindex]
			);
		}
	}

	/*
	*	@about - check passed parameters
	*	@param array $params - parameters
	*	@param bool $make_stuff - make other things like creating not existing folder
	*/
    private function _checkParams($params, $make_stuff = true) {
		foreach($this->default_params as $key => $value) {
			if(!array_key_exists($key, $params)) {
				$params[$key] = $value;
			}
		}

		if($make_stuff) {
			if($params['create_folder_if_not_exist']) {
				if(!is_dir($params['folder'])) mkdir($params['folder']);
			}
			if(!method_exists($this, $params['security_check_function'])) {
				$this->_addError("Method {$params['security_check_function']} do not exist");
				return false;
			}
		}

		$this->default_params = $params;
		return $params;
    }

	/*
	*	@about - Default function for checking image files
	*	@info - 1mb = 1024000 bytes
	*/
	private function _defaultCheck($data = array()) {
		if(empty($data)) $data = $this->data;

		$input_name = key($data);

		if(empty($data) || empty($input_name)) {
			$this->_addError("There are no files to upload");
			return false;
		}
		if(empty($data[$input_name])) {
			$this->_addError("There are no files to upload");
			return false;
		}

		foreach($data as $file) {
			foreach($file['name'] as $file_index => $filename) {
				$upload_this = true;
				if($file['error'][$file_index] == 4) {
					$this->_addError("There are no file to upload", $file_index);
					$upload_this = false;
				} else if(!in_array($file['type'][$file_index], $this->default_params['file_types'])) { // check file type
					$this->_addError("File type is forbidden", $file_index);
					$upload_this = false;
				}

				if($upload_this) {
					if(!in_array($this->getFileExtension($file['name'][$file_index]), $this->default_params['file_exts'])) {
						$this->_addError("File type is forbidden", $file_index);
						$upload_this = false;
					} else {
						$w = getimagesize($file['tmp_name'][$file_index]);
						if(empty($w)) {
							$this->_addError("File type is forbidden", $file_index);
							$upload_this = false;
						} else {
							if(!in_array($w['mime'], $this->default_params['file_types'])) {
								$this->_addError("File type is forbidden", $file_index);
								$upload_this = false;
							} else {
								if(filesize($file['tmp_name'][$file_index]) > 1024000 * 10) { // max 10mb
									$this->_addError("File is too large", $file_index);
									$upload_this = false;
								}
							}
						}
					}
				}

				$this->data[$input_name]['upload_this'][$file_index] = $upload_this;
			}
		}
	}

    /**
    *   Upload images to the server
    *   @params:
    *   	$data array - the array containing the form files. It's must be $_FILES
    *   	$params['folder'] string - the folder to upload the files
    *   	$params['rewrite'] bool - if true that the file with the same name will be rewrited (default false)
    *   @return array - will return an array with the success of each file upload
    */
    function upload($data, $params = array()) {
		$this->data = $data;

		$params = $this->_checkParams($params);

		if($params['security_check']) $this->{$params['security_check_function']}();

		$result = array();

		// loop through and deal with the files
		foreach($this->data as $file) {
			foreach($file['name'] as $file_index => $filename) {
				if((bool) $file['upload_this'][$file_index]) {
					// switch based on error code
					switch($file['error'][$file_index]) {
						case 0:
							$generated_filename = $this->genereateFileName($filename);
							// check filename already exists
							$path = $params['folder'].DS.$generated_filename;
							if(!file_exists($path)) {
								if(!$params['rewrite']) $generated_filename = $this->genereateFileName($filename);
							}
							$success = move_uploaded_file($file['tmp_name'][$file_index], $path);

							// if upload was successful
							if($success) {
								$result['result_urls'][] = $path;
							} else {
								$this->_addError("Error uploaded '{$file['tmp_name'][$file_index]}'. Please try again.", $file_index);
							}
							break;
						case 3:
							$this->_addError("Error uploading '{$file['tmp_name'][$file_index]}'. Please try again.", $file_index);
						break;
						default:
							$this->_addError("System error uploading '{$file['tmp_name'][$file_index]}'. Contact webmaster.", $file_index);
						break;
					}
				}
			}
		}
		$this->upload_result = $result;
		return count($result) > 0 ? true : false;
	}

	/*
	*	@about - function that return file extension
	*	@param :
	*		$filename string - file name
	*	@return string - return file extension string
	*/
	function getFileExtension($filename) {
		$a = pathinfo($filename);
		return $a['extension'];
	}

	function genereateFileName($filename) {
		return md5($filename.microtime()).".".$this->getFileExtension($filename);
	}

	/* -- Errors functions -- */
	public function getErrors() {
		return $this->errors;
	}

	public function isFailed() {
		return count($this->getErrors()) > 0 ? true : false;
	}

	public function getResult() {
		return $this->upload_result;
	}
}