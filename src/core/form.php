<?php
namespace Leaf\Core;

/**
 *  Leaf Forms
 *  --------
 *  This is an object to help validate a form and it's fields
 */
class Form {
	public $errors = array();
	/**
     * make sure that the form data is safe to work with
     *
     * @param string $data: The data gotten from the form field
     *
     * @return string, string: The parsed data
     */
	public function testInput($data) {
		// check for sql injection possibilities
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

	/**
     * Check if param is empty
     *
     * @param string $data: The data gotten from the form field
     * @param string $key: The name of the form field
     * @param string $message: The message to add to the errors array
     *
     * @return string, $message: The to add to the errors array
     */
	public function isEmpty($data, $key, $message="This field is required") {
		!isset($data) ? die(json_encode(array("message" => "isEmpty requires a param to test"))) : null;
		if (empty($data)) {
			$errors[$key] = $message;
			return;
		}
		return;
	}

	/**
     * Check if param is null
     *
     * @param string $data: The data gotten from the form field
     * @param string $key: The name of the form field
     * @param string $message: The message to add to the errors array
     *
     * @return string, $message: The to add to the errors array
     */
	public function isNull($data, $key, $message="This field is required") {
		!isset($data) ? die(json_encode(array("message" => "isEmpty requires a param to test"))) : null;
		if (is_null($data)) {
			$errors[$key] = $message;
			return;
		}
		return;
	}

	/**
     * Return the form errors
     *
     * @return string, $message: The to add to the errors array
     */
	public function returnErrors() {
		return $this->errors;
	}
}